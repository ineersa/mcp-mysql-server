<?php

declare(strict_types=1);

namespace App\Tool\ExecuteSQL;

use App\Utils\VersionResolver;
use Symfony\AI\McpSdk\Capability\Tool\MetadataInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolAnnotationsInterface;

readonly final class ExecuteSQLMetadata implements MetadataInterface
{
    public const string TITLE = 'MySQL MCP Server to execute SQL queries.';
    public const string NAME = 'execute_sql';

    public function __construct(
        private VersionResolver $versionResolver
    ) {
    }

    public function getName(): string
    {
        return ExecuteSQLMetadata::NAME;
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
            'required' => ["sql"],
        ];
    }

    public function getOutputSchema(): ?array
    {
        return null;
    }

    public function getTitle(): ?string
    {
        return ExecuteSQLMetadata::TITLE;
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
                return ExecuteSQLMetadata::TITLE;
            }
        };
    }
}
