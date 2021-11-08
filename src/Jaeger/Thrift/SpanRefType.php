<?php

declare(strict_types=1);

namespace Jaeger\Thrift;

final class SpanRefType
{
    const CHILD_OF = 0;

    const FOLLOWS_FROM = 1;
}
