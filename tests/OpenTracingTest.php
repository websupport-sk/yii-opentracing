<?php

namespace Websupport\OpenTracing\Tests;

use Websupport\OpenTracing\OpenTracing;

class OpenTracingTest extends TestCase
{
    /**
     * @dataProvider eventNameDataProvider
     *
     * @param string $eventName
     */
    public function testAttachedHandlers($eventName)
    {
        $app = $this->mockApplication([
            'preload' => ['opentracing'],
            'components' => [
                'opentracing' => [
                    'class' => OpenTracing::class,
                ],
            ],
        ]);

        $onBeginRequestHandlers = $app->getEventHandlers($eventName);
        $this->assertNotEmpty($onBeginRequestHandlers, sprintf('There should be handler attached to "%s"', $eventName));
        $this->assertInstanceOf(
            OpenTracing::class,
            $onBeginRequestHandlers[0][0],
            'Event handler should be OpenTracing component'
        );
    }

    /**
     * @return array
     */
    public function eventNameDataProvider()
    {
        return [
            ['onBeginRequest'],
            ['onEndRequest'],
            ['onException'],
            ['onError'],
        ];
    }
}
