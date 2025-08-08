<?php

declare(strict_types=1);

namespace App\Tool\ExecuteSQL;

use App\Capability\ToolChain;
use App\Server\RequestHandler\InitializeHandler;
use Psr\Log\LoggerInterface;
use Symfony\AI\McpSdk\Capability\PromptChain;
use Symfony\AI\McpSdk\Capability\ResourceChain;
use Symfony\AI\McpSdk\Server\NotificationHandler\InitializedHandler;
use Symfony\AI\McpSdk\Server\NotificationHandlerInterface;
use Symfony\AI\McpSdk\Server\RequestHandler\PingHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\PromptGetHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\PromptListHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ResourceListHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ResourceReadHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ToolCallHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ToolListHandler;
use Symfony\AI\McpSdk\Server\RequestHandlerInterface;

class ExecuteSQLBuilder
{
    public function __construct(
        private readonly ExecuteSQLMetadata $metadata,
        private readonly ExecuteSQLExecutor $executor,
    ) {
    }

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
}
