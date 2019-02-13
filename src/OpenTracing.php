<?php

namespace Websupport\OpenTracing;

use OpenTracing\Formats;
use OpenTracing\GlobalTracer;
use OpenTracing\Scope;
use OpenTracing\Tracer;
use Yii;

class OpenTracing extends \CApplicationComponent
{
    /** @var string */
    public $serviceName;

    /** @var Scope */
    private $rootScope;

    /** @var Tracer */
    private $tracer;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->serviceName === null) {
            $this->serviceName = Yii::app()->getId();
        }

        $this->initTracer();
        $this->attachEventHandlers();
    }

    /**
     * @return void
     */
    protected function initTracer()
    {
        $this->tracer = GlobalTracer::get();
    }

    /**
     * @throws \CException
     */
    protected function attachEventHandlers()
    {
        Yii::app()->attachEventHandler('onBeginRequest', [$this, 'handleBeginRequestEvent']);
        Yii::app()->attachEventHandler('onEndRequest', [$this, 'handleEndRequestEvent']);
        Yii::app()->attachEventHandler('onException', [$this, 'handleExceptionEvent']);
        Yii::app()->attachEventHandler('onError', [$this, 'handleErrorEvent']);
    }

    /**
     * @return Tracer
     */
    public function getTracer()
    {
        return $this->tracer;
    }

    /**
     * @param Tracer $tracer
     * @return $this
     */
    public function setTracer(Tracer $tracer)
    {
        $this->tracer = $tracer;
        GlobalTracer::set($this->tracer);

        return $this;
    }

    /**
     * @param string $operationName
     * @param array $options
     * @return Scope
     */
    public function startActiveSpan(string $operationName, array $options = [])
    {
        if (!isset($options['tags'][\OpenTracing\Tags\COMPONENT])) {
            $options['tags'][\OpenTracing\Tags\COMPONENT] = 'yii-opentracing';
        }

        return $this->tracer->startActiveSpan($operationName, $options);
    }

    /**
     * @return void
     */
    public function closeActiveSpan()
    {
        if (($span = $this->tracer->getActiveSpan()) !== null) {
            $span->finish();
        }
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->tracer->flush();
    }

    //region Events

    /**
     * @param \CEvent $event
     */
    public function handleBeginRequestEvent(\CEvent $event)
    {
        $spanOptions = [
            'tags' => [
                \OpenTracing\Tags\COMPONENT => str_replace('\\', '.', get_class($event->sender)),
            ],
        ];

        // Mark application as child of another
        if (($spanContext = $this->tracer->extract(Formats\HTTP_HEADERS, getallheaders())) !== null) {
            $spanOptions['child_of'] = $spanContext;
        }

        $this->rootScope = $this->startActiveSpan('application', $spanOptions);
    }

    /**
     * @param \CEvent $event
     */
    public function handleEndRequestEvent(\CEvent $event)
    {
        $this->rootScope->close();
        $this->tracer->flush();
    }

    /**
     * @param \CExceptionEvent $event
     */
    public function handleExceptionEvent(\CExceptionEvent $event)
    {
        if (($span = $this->tracer->getActiveSpan()) !== null) {
            $span->setTag(\OpenTracing\Tags\ERROR, true);
            $span->log([
                'event' => 'error',
                'error.kind' => 'Exception',
                'error.object' => $event->exception,
                'message' => $event->exception->getMessage(),
                'stack' => $event->exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * @param \CErrorEvent $event
     */
    public function handleErrorEvent(\CErrorEvent $event)
    {
        if (($span = $this->tracer->getActiveSpan()) !== null) {
            $span->setTag(\OpenTracing\Tags\ERROR, true);
            $span->log([
                'event' => 'error',
                'error.kind' => 'Error',
                'message' => $event->message,
                'stack' => sprintf('%s:%d', $event->file, $event->line),
            ]);
        }
    }

    //endregion
}
