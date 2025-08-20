<?php

declare(strict_types=1);

namespace App\Command;

use App\Tool\ExecuteSQL\ExecuteSQLBuilder;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\AI\McpSdk\Message\Factory;
use Symfony\AI\McpSdk\Server;
use Symfony\AI\McpSdk\Server\JsonRpcHandler;
use Symfony\AI\McpSdk\Server\Transport\Stdio\SymfonyConsoleTransport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:query-server',
    description: 'Starts and runs query server for MySQL MCP. Queries only readonly',
)]
class QueryServerCommand extends Command
{
    public function __construct(
        private readonly ExecuteSQLBuilder $builder,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output destination (file or stderr)', 'stderr')
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'Filename for file output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $handler = $this->createHandler($input->getOption('output'), $input->getOption('filename'), $output->getVerbosity());
        if (null === $handler) {
            $output->writeln((string) json_encode(['error' => 'Invalid output configuration']));

            return Command::FAILURE;
        }

        $logger = new Logger('mcp', [$handler]);
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

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/');
    }

    private function mapVerbosityToLogLevel(int $verbosity): Level
    {
        // Map Symfony console verbosity to Monolog log levels
        // No verbosity (normal) -> WARNING
        // -v -> NOTICE
        // -vv -> INFO
        // -vvv -> DEBUG
        if ($verbosity >= OutputInterface::VERBOSITY_DEBUG) {
            return Level::Debug;
        }
        if ($verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            return Level::Info;
        }
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            return Level::Notice;
        }

        return Level::Warning;
    }

    private function createHandler(string $outputOption, ?string $filename, int $verbosity = OutputInterface::VERBOSITY_NORMAL): ?StreamHandler
    {
        if ('stderr' === $outputOption) {
            $handler = new StreamHandler('php://stderr', $this->mapVerbosityToLogLevel($verbosity));
            $handler->setFormatter(new JsonFormatter());

            return $handler;
        }
        if ('file' === $outputOption) {
            if (null === $filename) {
                return null;
            }
            $path = $filename;
            if (!$this->isAbsolutePath($filename)) {
                $projectDir = $this->projectDir;
                $path = rtrim($projectDir, '/').'/'.$filename;
            }
            $handler = new StreamHandler($path, $this->mapVerbosityToLogLevel($verbosity));
            $handler->setFormatter(new JsonFormatter());

            return $handler;
        }

        return null;
    }
}
