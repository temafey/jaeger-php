<?php

declare(strict_types=1);

namespace JagerPhp\Tests;

use Jaeger\Config;
use OpenTracing\NoopTracer;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testSetDisabled(): void
    {
        $config = Config::getInstance();
        $config->setDisabled(true);

        self::assertTrue($config::$disabled == true);
    }

    public function testNoopTracer(): void
    {
        $config = Config::getInstance();
        $config->setDisabled(true);
        $trace = $config->initTracer('test');

        self::assertTrue($trace instanceof NoopTracer);
    }
}