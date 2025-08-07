<?php

namespace App\Server\RequestHandler;

use Symfony\AI\McpSdk\Message\Request;
use Symfony\AI\McpSdk\Message\Response;
use Symfony\AI\McpSdk\Server\RequestHandler\BaseRequestHandler;

final class InitializeHandler extends BaseRequestHandler
{
    public function __construct(
        private readonly string $name = 'app',
        private readonly string $version = 'dev',
        private readonly string $protocolVersion = '2025-03-26',
    ) {
    }

    public function createResponse(Request $message): Response
    {
        return new Response($message->id, [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => [
                'prompts' => ['listChanged' => false],
                'tools' => ['listChanged' => false],
                'resources' => ['listChanged' => false, 'subscribe' => false],
            ],
            'serverInfo' => ['name' => $this->name, 'version' => $this->version],
        ]);
    }

    protected function supportedMethod(): string
    {
        return 'initialize';
    }
}
