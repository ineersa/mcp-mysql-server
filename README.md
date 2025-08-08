# MCP SQL Server

I've tried to integrate popular MCP servers for MySQL to Jetbrains AI Assistant, and all of them failed, so I decided to create simple demo to test it. 

**WARNING** this is not production ready, mostly it's a demo of how to use [Symfony MCP SDK](https://github.com/symfony/mcp-sdk) to create your own [MCP server](https://modelcontextprotocol.io/overview) using.

You can find more documentation/examples in [Symfony Model Context Protocol SDK](https://github.com/symfony/mcp-sdk/blob/main/doc/index.rst) and [Symfony AI](https://symfony.com/blog/kicking-off-the-symfony-ai-initiative)

## Installation

Just do `git clone` and run `composer install`.

Set it up in your MCP client/host
```json
{
    "mcpServers": {
        "mysql-server": {
            "command": "php",
            "args": [
                "{DIR}/bin/console",
                "app:query-server"
            ],
            "env": {
                "DATABASE_URL": "mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
            }
        }
    }
}
```
That's it, as easy as that. 

## Concepts
`app:query-server` is simple Symfony console command that runs MCP server.

To debug it, you can use verbosity flags like `-v` or `-vv`.
`APP_ENV` set to production, but if you need to debug you can pass `APP_ENV=dev` and `APP_DEBUG` to command.

This application tries to minimize dependencies, only a few required packages are installed.

This could be used to run more than one server or as a template.

The same goes for databases, I've tested it with MySQL, but it could be used for all databases supported by `doctrine/dbal`.

## Some code explanation

Inside our server command we build and start a server.
```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $logger = new JsonConsoleLogger($output);

    // Configure the JsonRpcHandler and build the functionality
    $jsonRpcHandler = new JsonRpcHandler(
        new Factory(),
        $this->builder->buildRequestHandlers($logger),
        $this->builder->buildNotificationHandlers(),
        $logger,
    );

    // Set up the server
    $server = new Server($jsonRpcHandler, $logger);

    // Create the transport layer using Symfony Console
    $transport = new SymfonyConsoleTransport($input, $output);

    // Start MCP server
    $server->connect($transport);

    return Command::FAILURE;
}
```
You can use available transport layers, for Jetbrains AI Assistant only STDIO transport supported.

Inside `Builder` we are registering our:
 - [Tools](https://modelcontextprotocol.io/specification/2025-06-18/server/tools)
 - [Resources](https://modelcontextprotocol.io/specification/2025-06-18/server/resources)
 - [Prompts](https://modelcontextprotocol.io/specification/2025-06-18/server/prompts)
 - [Notifications](https://modelcontextprotocol.io/docs/learn/architecture#notifications)

```php
/**
 * @return list<RequestHandlerInterface>
 */
public function buildRequestHandlers(LoggerInterface $logger): array
{
    $promptManager = new PromptChain([
        // ... Prompts
    ]);

    $resourceManager = new ResourceChain([
        // ... Resources
    ]);

    $toolManager = new ToolChain([
        $this->metadata,
        $this->executor,
    ], $logger);

    return [
        new InitializeHandler('mysql-server', 'dev', '2024-11-05'),
        new PingHandler(),
        new PromptListHandler($promptManager),
        new PromptGetHandler($promptManager),
        new ResourceListHandler($resourceManager),
        new ResourceReadHandler($resourceManager),
        new ToolCallHandler($toolManager),
        new ToolListHandler($toolManager),
    ];
}

/**
 * @return list<NotificationHandlerInterface>
 */
public function buildNotificationHandlers(): array
{
    return [
        new InitializedHandler(),
    ];
}
```

## Notable changes from the original SDK classes/demo, TODOs

1. I had to extend `\Symfony\AI\McpSdk\Capability\ToolChain` to inject logger inside tools. It's not the best solution, better would be to autowire it or set it up in `services.yaml`
2. While testing in MCP inspector, I've noticed that `STDERR` logs wasn't parsed, since it's not JSON. I've added a simple JSON logger that writes to `STDERR` instead of usage of `ConsoleLogger`. This is not ideal solution, better way would be to set up `Monolog`.
3. Writing to `STDERR` breaks some clients (for example, Jetbrains AI Assistant), need to investigate how to log using [Notifications](https://modelcontextprotocol.io/specification/2025-06-18/server/utilities/logging)
4. Jetbrains AI Assistant runs with protocol version `2024-11-05` so I had to replace `InitializeHandler` to accept a protocol version. It's dirty, but it works.
5. Add tests
6. Add Docker and docker-compose support

## MCP Server functionality

For now server has only one tool `execute_sql` which allows executing SQL queries.

For now, it's only possible to fetch data in readonly mode, under the hood it uses `doctrine/dbal` `->fetchAllAssociative()` method.

Resources are not supported yet, but it's on the roadmap.


## MCP inspector
You can run MCP inspector to test your server.
```bash
npx @modelcontextprotocol/inspector -e DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4" \
  php ./bin/console app:query-server -vv
```
Logger will write output to `STDERR` so you can see it inside MCP inspector.
