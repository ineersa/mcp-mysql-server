<?php

declare(strict_types=1);

namespace App\Tool\ExecuteSQL;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\AI\McpSdk\Capability\Tool\IdentifierInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\AI\McpSdk\Capability\Tool\ToolCallResult;
use Symfony\AI\McpSdk\Capability\Tool\ToolExecutorInterface;

final class ExecuteSQLExecutor implements ToolExecutorInterface, IdentifierInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getName(): string
    {
        return ExecuteSQLMetadata::NAME;
    }

    public function call(ToolCall $input): ToolCallResult
    {
        $sql = $input->arguments['sql'];

        try {
            $this->logger->info('Executing SQL: '.$sql);
            $rows = $this->connection->fetchAllAssociative($sql);
            $this->logger->info('SQL executed. Results count: '.\count($rows));
        } catch (\Throwable $throwable) {
            return new ToolCallResult($throwable->getMessage(), 'text', 'text/plain', true);
        }

        $json = json_encode($rows, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        if (false === $json) {
            $json = '[]';
        }

        return new ToolCallResult($json);
    }
}
