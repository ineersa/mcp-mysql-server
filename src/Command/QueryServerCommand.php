<?php

declare(strict_types=1);

namespace App\Command;

use App\Logger\JsonConsoleLogger;
use App\Tool\ExecuteSQL\ExecuteSQLBuilder;
use Symfony\AI\McpSdk\Message\Factory;
use Symfony\AI\McpSdk\Server;
use Symfony\AI\McpSdk\Server\JsonRpcHandler;
use Symfony\AI\McpSdk\Server\Transport\Stdio\SymfonyConsoleTransport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:query-server',
    description: 'Starts and runs query server for MySQL MCP. Queries only readonly',
)]
class QueryServerCommand extends Command
{
    public function __construct(
        private readonly ExecuteSQLBuilder $builder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

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
}
