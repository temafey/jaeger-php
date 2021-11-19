<?php

declare(strict_types=1);

namespace Jaeger;

use OpenTracing\Span as SpanInterface;
use OpenTracing\SpanContext as SpanContextInterface;

class Span implements SpanInterface
{
    private string $operationName;

    public ?int $startTime;

    public ?int $finishTime;

    public SpanContextInterface $spanContext;

    public int $duration = 0;

    public array $logs = [];

    public array $tags = [];

    public array $references = [];

    public function __construct(
        string $operationName,
        SpanContextInterface $spanContext,
        array $references,
        ?int $startTime = null
    ) {
        $this->operationName = $operationName;
        $this->spanContext = $spanContext;
        $this->references = $references;
        $this->startTime = $startTime == null ? $this->microtimeToInt() : $startTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperationName(): string
    {
        return $this->operationName;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): SpanContextInterface
    {
        return $this->spanContext;
    }

    /**
     * {@inheritdoc}
     */
    public function finish($finishTime = null): void
    {
        $this->finishTime = $finishTime === null ? $this->microtimeToInt() : $finishTime;
        $this->duration = $this->finishTime - $this->startTime;
    }

    /**
     * {@inheritdoc}
     */
    public function overwriteOperationName(string $newOperationName): void
    {
        $this->operationName = $newOperationName;
    }

    /**
     * {@inheritdoc}
     */
    public function setTag(string $key, $value): void
    {
        $this->tags[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function log(array $fields = [], $timestamp = null): void
    {
        $log['timestamp'] = $timestamp ?? $this->microtimeToInt();
        $log['fields'] = $fields;
        $this->logs[] = $log;
    }

    /**
     * {@inheritdoc}
     */
    public function addBaggageItem(string $key, string $value): void
    {
        $this->log([
            'event' => 'baggage',
            'key' => $key,
            'value' => $value,
        ]);

        $this->spanContext = $this->spanContext->withBaggageItem($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem(string $key): ?string
    {
        return $this->spanContext->getBaggageItem($key);
    }

    private function microtimeToInt(): int
    {
        return intval(microtime(true) * 1000000);
    }
}