<?php

namespace Websupport\OpenTracing\Tests;

use Jaeger\Tracer;
use Websupport\OpenTracing\JaegerOpenTracing;

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
