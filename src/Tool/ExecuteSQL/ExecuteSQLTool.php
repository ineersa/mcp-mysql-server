<?php

declare(strict_types=1);

namespace App\Tool\ExecuteSQL;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\AI\McpSdk\Capability\Tool\IdentifierInterface;
use Symfony\AI\McpSdk\Capability\Tool\MetadataInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolAnnotationsInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\AI\McpSdk\Capability\Tool\ToolCallResult;
use Symfony\AI\McpSdk\Capability\Tool\ToolExecutorInterface;

final class ExecuteSQLTool implements ToolExecutorInterface, IdentifierInterface, MetadataInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string TITLE = 'MySQL MCP Server to execute SQL queries.';
    public const string NAME = 'execute_sql';

    public function __construct(
        private readonly Connection $connection,
        private readonly \App\Utils\VersionResolver $versionResolver,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDescription(): ?string
    {
        return <<<DESC
            MySQL MCP Server v{$this->versionResolver->getVersion()}. Run SQL queries against MySQL database.
            Currently only READ only mode is available, SELECT and SHOW/DESCRIBE statements are supported.
        DESC;
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sql' => [
                    'type' => 'string',
                    'description' => 'The SQL query to execute.',
                ],
            ],
            'required' => ['sql'],
        ];
    }

    public function getOutputSchema(): ?array
    {
        return null;
    }

    public function getTitle(): ?string
    {
        return self::TITLE;
    }

    public function getAnnotations(): ?ToolAnnotationsInterface
    {
        return new class implements ToolAnnotationsInterface {
            public function getDestructiveHint(): ?bool
            {
                return false;
            }

            public function getIdempotentHint(): ?bool
            {
                return true;
            }

            public function getOpenWorldHint(): ?bool
            {
                return false;
            }

            public function getReadOnlyHint(): ?bool
            {
                return true;
            }

            public function getTitle(): ?string
            {
                return ExecuteSQLTool::TITLE;
            }
        };
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
