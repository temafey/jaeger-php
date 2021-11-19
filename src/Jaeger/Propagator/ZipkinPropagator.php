<?php

declare(strict_types=1);

namespace Jaeger\Propagator;

use Jaeger\SpanContext;
use const Jaeger\Constants\X_B3_PARENT_SPANID;
use const Jaeger\Constants\X_B3_SAMPLED;
use const Jaeger\Constants\X_B3_SPANID;
use const Jaeger\Constants\X_B3_TRACEID;

class ZipkinPropagator implements Propagator
{
    public function inject(SpanContext $spanContext, string $format, &$carrier): void
    {
        $carrier[X_B3_TRACEID] = $spanContext->traceIdLowToString();
        $carrier[X_B3_PARENT_SPANID] = $spanContext->parentIdToString();
        $carrier[X_B3_SPANID] = $spanContext->spanIdToString();
        $carrier[X_B3_SAMPLED] = $spanContext->flagsToString();
    }

    public function extract(string $format, $carrier): ?SpanContext
    {
        $spanContext = null;

        foreach ($carrier as $k => $val) {
            if (in_array($k, [
                X_B3_TRACEID,
                X_B3_PARENT_SPANID,
                X_B3_SPANID,
                X_B3_SAMPLED
            ])) {
                if ($spanContext === null) {
                    $spanContext = new SpanContext(0, 0, 0, null, 0);
                }
            }
        }
        if (isset($carrier[X_B3_TRACEID]) && $carrier[X_B3_TRACEID]) {
            $spanContext->traceIdToString($carrier[X_B3_TRACEID]);
        }
        if (isset($carrier[X_B3_PARENT_SPANID]) && $carrier[X_B3_PARENT_SPANID]) {
            $spanContext->parentId = $spanContext->hexToSignedInt($carrier[X_B3_PARENT_SPANID]);
        }
        if (isset($carrier[X_B3_SPANID]) && $carrier[X_B3_SPANID]) {
            $spanContext->spanId = $spanContext->hexToSignedInt($carrier[X_B3_SPANID]);
        }
        if (isset($carrier[X_B3_SAMPLED]) && $carrier[X_B3_SAMPLED]) {
            $spanContext->flags = $carrier[X_B3_SAMPLED];
        }

        return $spanContext;
    }
}
