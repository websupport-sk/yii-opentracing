<?php

namespace Websupport\OpenTracing\Tests;

use OpenTracing\NoopTracer;
use Websupport\OpenTracing\OpenTracing;

class OpenTracingTest extends TestCase
{
    public function testAttachedHandlers()
    {
        $app = $this->createApplication([
            'preload' => ['opentracing'],
            'components' => [
                'opentracing' => [
                    'class' => OpenTracing::class,
                ],
            ],
        ]);

        $this->assertAttachedEventHandlerIs(OpenTracing::class, 'onBeginRequest', $app);
        $this->assertAttachedEventHandlerIs(OpenTracing::class, 'onEndRequest', $app);
        $this->assertAttachedEventHandlerIs(OpenTracing::class, 'onException', $app);
        $this->assertAttachedEventHandlerIs(OpenTracing::class, 'onError', $app);
    }

    public function testInitializedNoopTracer()
    {
        /** @var OpenTracing $component */
        $component = $this->createApplicationComponent(OpenTracing::class, [
            'serviceName' => 'test-application',
        ]);
        $this->assertInstanceOf(NoopTracer::class, $component->getTracer());
    }

    private function assertAttachedEventHandlerIs($expected, string $eventName, \CComponent $component)
    {
        $eventHandlers = $component->getEventHandlers($eventName);
        $this->assertNotEmpty($eventHandlers, sprintf('There should be handler attached to "%s"', $eventName));

        list($object,) = $eventHandlers->itemAt(0);
        $this->assertInstanceOf(
            $expected,
            $object,
            'Event handler should be OpenTracing component'
        );
    }
}
