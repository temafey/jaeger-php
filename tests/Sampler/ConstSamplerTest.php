<?php

declare(strict_types=1);

namespace JagerPhp\Tests\Sampler;

use Jaeger\Sampler\ConstSampler;
use PHPUnit\Framework\TestCase;
use const Jaeger\Constants\SAMPLER_TYPE_TAG_KEY;

class ConstSamplerTest extends TestCase
{
    public function testConstSampler(): void
    {
        $sample = new ConstSampler(true);
        self::assertTrue($sample->IsSampled());
    }

    public function testConstSamplerGetTag(): void
    {
        $sample = new ConstSampler(true);
        $tags = $sample->getTags();
        self::assertTrue($tags[SAMPLER_TYPE_TAG_KEY] == 'const');
    }
}