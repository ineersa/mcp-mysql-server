<?php

declare(strict_types=1);

namespace App\Tool\ExecuteSQL;

use App\Capability\ToolChain;
use Psr\Log\LoggerInterface;
use Symfony\AI\McpSdk\Capability\Prompt\PromptCapability;
use Symfony\AI\McpSdk\Capability\PromptChain;
use Symfony\AI\McpSdk\Capability\Resource\ResourceCapability;
use Symfony\AI\McpSdk\Capability\ResourceChain;
use Symfony\AI\McpSdk\Capability\Server\Implementation;
use Symfony\AI\McpSdk\Capability\Server\ProtocolVersionEnum;
use Symfony\AI\McpSdk\Capability\Server\ServerCapabilities;
use Symfony\AI\McpSdk\Capability\Tool\ToolCapability;
use Symfony\AI\McpSdk\Server\NotificationHandler\InitializedHandler;
use Symfony\AI\McpSdk\Server\NotificationHandlerInterface;
use Symfony\AI\McpSdk\Server\RequestHandler\InitializeHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\PingHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\PromptGetHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\PromptListHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ResourceListHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ResourceReadHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ToolCallHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ToolListHandler;
use Symfony\AI\McpSdk\Server\RequestHandlerInterface;

readonly class ExecuteSQLBuilder
{
    public function __construct(
        private ExecuteSQLMetadata $metadata,
        private ExecuteSQLExecutor $executor,
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

        $implementation = new Implementation();
        $serverCapabilities = new ServerCapabilities(
            prompts: new PromptCapability(listChanged: false),
            resources: new ResourceCapability(subscribe: false, listChanged: false),
            tools: new ToolCapability(listChanged: false),
        );

        return [
            new InitializeHandler(
                implementation: $implementation,
                serverCapabilities: $serverCapabilities,
                protocolVersion: ProtocolVersionEnum::V2024_11_05,
            ),
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
