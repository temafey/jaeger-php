<?php

declare(strict_types=1);

namespace Jaeger\Thrift;

use Thrift\Protocol\TProtocol;

interface TStruct
{
    public function write(TProtocol $t);

    public function read(TProtocol $t);
}