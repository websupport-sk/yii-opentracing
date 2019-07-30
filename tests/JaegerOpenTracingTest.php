<?php

namespace Websupport\OpenTracing\Tests;

use Jaeger\Tracer;
use Websupport\OpenTracing\JaegerOpenTracing;
use Websupport\OpenTracing\Tests\Support\TestCase\TestCase;

class JaegerOpenTracingTest extends TestCase
{
    public function testInitializedTracer()
    {
        /** @var JaegerOpenTracing $component */
        $component = $this->createApplicationComponent(JaegerOpenTracing::class, [
            'serviceName' => 'test-application',
        ]);
        $this->assertInstanceOf(Tracer::class, $component->getTracer());
    }
}
