<?php

declare(strict_types=1);

namespace App\Builder;


use Symfony\AI\McpSdk\Capability\PromptChain;
use Symfony\AI\McpSdk\Capability\ResourceChain;
use Symfony\AI\McpSdk\Capability\ToolChain;
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

class QueryServerBuilder
{
    /**
     * @return list<RequestHandlerInterface>
     */
    public static function buildRequestHandlers(): array
    {
        $promptManager = new PromptChain([
            // ... Prompts
        ]);

        $resourceManager = new ResourceChain([
            // ... Resources
        ]);

        $toolManager = new ToolChain([
            // ... Tools
        ]);

        return [
            new InitializeHandler(),
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
    public static function buildNotificationHandlers(): array
    {
        return [
            new InitializedHandler(),
        ];
    }
}
