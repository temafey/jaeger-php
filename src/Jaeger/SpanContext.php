<?php

declare(strict_types=1);

namespace Jaeger;

use OpenTracing\SpanContext as SpanContextInterface;

class SpanContext implements SpanContextInterface
{
    // traceID represents globally unique ID of the trace.
    // Usually generated as a random number.
    public $traceIdLow;

    public $traceIdHigh;

    // spanID represents span ID that must be unique within its trace,
    // but does not have to be globally unique.
    public $spanId;

    // parentID refers to the ID of the parent span.
    // Should be 0 if the current span is a root span.
    public $parentId;

    // flags is a bitmap containing such bits as 'sampled' and 'debug'.
    public $flags;

    // Distributed Context baggage. The is a snapshot in time.
    // key => val
    public $baggage;

    // debugID can be set to some correlation ID when the context is being
    // extracted from a TextMap carrier.
    public $debugId;

    public function __construct($spanId, $parentId, $flags, $baggage = null, $debugId = 0)
    {
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->flags = $flags;
        $this->baggage = $baggage;
        $this->debugId = $debugId;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem(string $key): ?string
    {
        return $this->baggage[$key] ?? null;
    }

    public function withBaggageItem(string $key, string $value): SpanContext
    {
        $this->baggage[$key] = $value;

        return $this;
    }

    public function getIterator()
    {
        // TODO: Implement getIterator() method.
    }

    public function buildString(): string
    {
        if ($this->traceIdHigh) {
            return sprintf(
                "%x%016x:%x:%x:%x", $this->traceIdHigh, $this->traceIdLow,
                $this->spanId, $this->parentId, $this->flags
            );
        }

        return sprintf("%x:%x:%x:%x", $this->traceIdLow, $this->spanId, $this->parentId, $this->flags);
    }

    public function spanIdToString(): string
    {
        return sprintf("%x", $this->spanId);
    }

    public function parentIdToString(): string
    {
        return sprintf("%x", $this->parentId);
    }

    public function traceIdLowToString(): string
    {
        if ($this->traceIdHigh) {
            return sprintf("%x%016x", $this->traceIdHigh, $this->traceIdLow);
        }

        return sprintf("%x", $this->traceIdLow);
    }

    public function flagsToString(): string
    {
        return sprintf("%x", $this->flags);
    }

    /**
     * @return mixed
     */
    public function isSampled()
    {
        return $this->flags;
    }

    public function hexToSignedInt($hex): int
    {
        //Avoid pure Arabic numerals eg:1
        if (gettype($hex) != "string") {
            $hex .= '';
        }

        $hexStrLen = strlen($hex);
        $dec = 0;

        for ($i = 0; $i < $hexStrLen; $i++) {
            $hexByteStr = $hex[$i];
            if (ctype_xdigit($hexByteStr)) {
                $decByte = hexdec($hex[$i]);
                $dec = ($dec << 4) | $decByte;
            }
        }

        return $dec;
    }

    public function traceIdToString($traceId): void
    {
        $len = strlen($traceId);

        if ($len > 16) {
            $this->traceIdHigh = $this->hexToSignedInt(substr($traceId, 0, 16));
            $this->traceIdLow = $this->hexToSignedInt(substr($traceId, 16));
        } else {
            $this->traceIdLow = $this->hexToSignedInt($traceId);
        }
    }

    public function isValid(): bool
    {
        return $this->isTraceIdValid() && $this->spanId;
    }

    public function isTraceIdValid(): bool
    {
        return $this->traceIdLow || $this->traceIdHigh;
    }
}
