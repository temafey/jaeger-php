<?php

declare(strict_types=1);

namespace Jaeger;

use Exception;
use Jaeger\Propagator\JaegerPropagator;
use Jaeger\Propagator\ZipkinPropagator;
use Jaeger\Reporter\RemoteReporter;
use Jaeger\Reporter\Reporter as ReporterInterface;
use Jaeger\Sampler\ConstSampler;
use Jaeger\Sampler\Sampler as SamplerInterface;
use Jaeger\Transport\Transport as TransportInterface;
use Jaeger\Transport\TransportUdp;
use OpenTracing\NoopTracer;
use OpenTracing\Tracer as TracerInterface;
use const Jaeger\Constants\PROPAGATOR_JAEGER;
use const Jaeger\Constants\PROPAGATOR_ZIPKIN;

class Config
{
    private $transport = null;

    private $reporter = null;

    private $sampler = null;

    private $scopeManager = null;

    private $gen128bit = false;

    public static $tracer = null;

    public static $span = null;

    public static $instance = null;

    public static $disabled = false;

    public static $propagator = PROPAGATOR_JAEGER;

    public static function getInstance(): self
    {
        if (false === (self::$instance instanceof self)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Init jaeger, return can use flush buffers
     *
     * @throws Exception
     */
    public function initTracer(string $serverName, string $agentHostPort = ''): ?TracerInterface
    {
        if (self::$disabled) {
            return new NoopTracer();
        }
        if ($serverName == '') {
            throw new Exception("serverName require");
        }
        if (isset(self::$tracer[$serverName]) && !empty(self::$tracer[$serverName])) {
            return self::$tracer[$serverName];
        }
        if ($this->transport == null) {
            $this->transport = new TransportUdp($agentHostPort);
        }
        if ($this->reporter == null) {
            $this->reporter = new RemoteReporter($this->transport);
        }
        if ($this->sampler == null) {
            $this->sampler = new ConstSampler(true);
        }
        if ($this->scopeManager == null) {
            $this->scopeManager = new ScopeManager();
        }

        $tracer = new Jaeger($serverName, $this->reporter, $this->sampler, $this->scopeManager);
        if ($this->gen128bit == true) {
            $tracer->gen128bit();
        }
        if (self::$propagator == PROPAGATOR_ZIPKIN) {
            $tracer->setPropagator(new ZipkinPropagator());
        } else {
            $tracer->setPropagator(new JaegerPropagator());
        }

        self::$tracer[$serverName] = $tracer;

        return $tracer;
    }


    /**
     * Close tracer
     */
    public function setDisabled(bool $disabled): self
    {
        self::$disabled = $disabled;

        return $this;
    }

    public function setTransport(TransportInterface $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    public function setReporter(ReporterInterface $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function setSampler(SamplerInterface $sampler): self
    {
        $this->sampler = $sampler;

        return $this;
    }

    public function gen128bit(): self
    {
        $this->gen128bit = true;

        return $this;
    }

    public function flush(): bool
    {
        if (count(self::$tracer) > 0) {
            foreach (self::$tracer as $tracer) {
                $tracer->reportSpan();
            }
            $this->reporter->close();
        }

        return true;
    }
}
