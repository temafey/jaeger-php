<?php

declare(strict_types=1);

namespace Jaeger\Propagator;

use Jaeger\SpanContext;
use const Jaeger\Constants\Jaeger_Baggage_Header;
use const Jaeger\Constants\Jaeger_Debug_Header;
use const Jaeger\Constants\Trace_Baggage_Header_Prefix;
use const Jaeger\Constants\Tracer_State_Header_Name;

class JaegerPropagator implements Propagator
{
    public function inject(
        SpanContext $spanContext,
        string $format,
        &$carrier
    ): void {
        $carrier[strtoupper(Tracer_State_Header_Name)] = $spanContext->buildString();
        if ($spanContext->baggage) {
            foreach ($spanContext->baggage as $k => $v) {
                $carrier[strtoupper(Trace_Baggage_Header_Prefix . $k)] = $v;
            }
        }
    }

    public function extract(string $format, $carrier): ?SpanContext
    {
        $spanContext = null;

        $carrier = array_change_key_case($carrier, CASE_LOWER);

        foreach ($carrier as $k => $v) {
            if (
                false === in_array($k, [Tracer_State_Header_Name, Jaeger_Debug_Header, Jaeger_Baggage_Header,])
                && false === stripos((string)$k, Trace_Baggage_Header_Prefix))
            {
                continue;
            }

            if ($spanContext === null) {
                $spanContext = new SpanContext(0, 0, 0, null, 0);
            }

            if (is_array($v)) {
                $v = urldecode(current($v));
            } else {
                $v = urldecode((string)$v);
            }
            if ($k == Tracer_State_Header_Name) {
                list($traceId, $spanId, $parentId, $flags) = explode(':', $v);

                $spanContext->spanId = $spanContext->hexToSignedInt($spanId);
                $spanContext->parentId = $spanContext->hexToSignedInt($parentId);
                $spanContext->flags = $flags;
                $spanContext->traceIdToString($traceId);

            } elseif (stripos($k, Trace_Baggage_Header_Prefix) !== false) {
                $safeKey = str_replace(Trace_Baggage_Header_Prefix, "", $k);
                if ($safeKey != "") {
                    $spanContext->withBaggageItem($safeKey, $v);
                }
            } elseif ($k == Jaeger_Debug_Header) {
                $spanContext->debugId = $v;
            } elseif ($k == Jaeger_Baggage_Header) {
                // Converts a comma separated key value pair list into a map
                // e.g. key1=value1, key2=value2, key3 = value3
                // is converted to array { "key1" : "value1",
                //                                     "key2" : "value2",
                //                                     "key3" : "value3" }
                $parseVal = explode(',', $v);
                foreach ($parseVal as $val) {
                    if (stripos($v, '=') !== false) {
                        $kv = explode('=', trim($val));
                        if (count($kv) == 2) {
                            $spanContext->withBaggageItem($kv[0], $kv[1]);
                        }
                    }
                }

            }
        }

        return $spanContext;
    }
}
