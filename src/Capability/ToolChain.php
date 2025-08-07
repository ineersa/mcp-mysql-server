<?php

declare(strict_types=1);

namespace App\Capability;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\McpSdk\Capability\Tool\IdentifierInterface;

final class ToolChain extends \Symfony\AI\McpSdk\Capability\ToolChain
{
    public function __construct(
        /**
         * @var IdentifierInterface[] $items
         */
        private iterable $items,
        LoggerInterface $logger,
    ) {
        foreach ($this->items as $item) {
            if ($item instanceof LoggerAwareInterface) {
                $item->setLogger($logger);
            }
        }

        parent::__construct($this->items);
    }
}
