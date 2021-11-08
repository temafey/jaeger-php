<?php

declare(strict_types=1);

namespace Jaeger\Thrift;

use Jaeger\Jaeger;
use Jaeger\Span;
use OpenTracing\Reference;

class JaegerThriftSpan
{
    public function buildJaegerProcessThrift(Jaeger $jaeger): array
    {
        $tags = [];
        $ip = $_SERVER['SERVER_ADDR'] ?? '0.0.0.0';
        $tags['peer.ipv4'] = $ip;

        $port = $_SERVER['SERVER_PORT'] ?? '80';
        $tags['peer.port'] = $port;

        $tags = array_merge($tags, $jaeger->tags);
        $tagsObj = Tags::getInstance();
        $tagsObj->setTags($tags);
        $thriftTags = $tagsObj->buildTags();

        return [
            'serverName' => $jaeger->serverName,
            'tags' => $thriftTags,
        ];
    }

    public function buildJaegerSpanThrift(Span $span)
    {
        $spContext = $span->spanContext;

        return [
            'traceIdLow' => $spContext->traceIdLow,
            'traceIdHigh' => $spContext->traceIdHigh,
            'spanId' => $spContext->spanId,
            'parentSpanId' => $spContext->parentId,
            'operationName' => $span->getOperationName(),
            'flags' => intval($spContext->flags),
            'startTime' => $span->startTime,
            'duration' => $span->duration,
            'tags' => $this->buildTags($span->tags),
            'logs' => $this->buildLogs($span->logs),
            'references' => $this->buildReferences($span->references)
        ];
    }

    private function buildTags($tags)
    {
        $tagsObj = Tags::getInstance();
        $tagsObj->setTags($tags);

        return $tagsObj->buildTags();
    }

    private function buildLogs($logs)
    {
        $resultLogs = [];
        $tagsObj = Tags::getInstance();

        foreach ($logs as $log) {
            $tagsObj->setTags($log['fields']);
            $fields = $tagsObj->buildTags();
            $resultLogs[] = [
                "timestamp" => $log['timestamp'],
                "fields" => $fields,
            ];
        }

        return $resultLogs;
    }

    private function buildReferences($references)
    {
        $spanRef = [];

        foreach ($references as $ref) {
            $type = SpanRefType::CHILD_OF;
            if ($ref->isType(Reference::FOLLOWS_FROM)) {
                $type = SpanRefType::FOLLOWS_FROM;
            }

            $ctx = $ref->getContext();
            $spanRef[] = [
                'refType' => $type,
                'traceIdLow' => $ctx->traceIdLow,
                'traceIdHigh' => $ctx->traceIdHigh,
                'spanId' => $ctx->spanId,
            ];
        }

        return $spanRef;
    }
}