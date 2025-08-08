<?php

declare(strict_types=1);

namespace App\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PSR-3 compliant JSON console logger.
 *
 * Outputs log messages as JSON to stderr while respecting console verbosity.
 *
 * @see ConsoleLogger
 *
 * @TODO consider switching to monolog instead
 */
class JsonConsoleLogger extends AbstractLogger
{
    /** @var array<string, int> */
    private array $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
    ];

    /**
     * @param array<string, int> $verbosityLevelMap
     */
    public function __construct(
        private OutputInterface $output,
        array $verbosityLevelMap = [],
    ) {
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
    }

    /**
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (!isset($this->verbosityLevelMap[$level])) {
            throw new InvalidArgumentException(\sprintf('The log level "%s" does not exist.', $level));
        }

        // Always use error output
        $output = $this->output instanceof ConsoleOutputInterface
            ? $this->output->getErrorOutput()
            : $this->output;

        // Check if the current verbosity level allows this message to be displayed
        if ($output->getVerbosity() >= $this->verbosityLevelMap[$level]) {
            $logData = [
                'level' => $level,
                'message' => $this->interpolate((string) $message, $context),
                'context' => $context,
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
            ];

            // Output as JSON
            $json = json_encode($logData, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
            if (false === $json) {
                $json = '{"error":"Failed to encode log message"}';
            }
            $output->writeln($json, $this->verbosityLevelMap[$level]);
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        if (!str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if (null === $val || \is_scalar($val) || $val instanceof \Stringable) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(\DateTimeInterface::RFC3339);
            } elseif (\is_object($val)) {
                $replacements["{{$key}}"] = '[object '.$val::class.']';
            } else {
                $replacements["{{$key}}"] = '['.\gettype($val).']';
            }
        }

        return strtr($message, $replacements);
    }
}
