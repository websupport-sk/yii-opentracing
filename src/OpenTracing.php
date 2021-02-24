<?php

namespace Websupport\OpenTracing;

use OpenTracing\Formats;
use OpenTracing\GlobalTracer;
use OpenTracing\NoopTracer;
use OpenTracing\Scope;
use OpenTracing\Span;
use OpenTracing\Tracer;
use OpenTracing\UnsupportedFormatException;
use Yii;

class OpenTracing extends \CApplicationComponent
{
    /** @var string */
    public $serviceName;

    /** @var string id of sentry component */
    public $sentryId;

    /** @var bool if root node should be closed on end request */
    public $closeRootNode = true;

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
        $this->setTracer(new NoopTracer());
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
     *
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
     *
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
     * @param string $format
     * @param mixed $carrier
     *
     * @throws UnsupportedFormatException
     */
    public function injectActiveSpan(string $format, &$carrier)
    {
        if (($span = $this->tracer->getActiveSpan()) !== null) {
            $this->tracer->inject($span->getContext(), $format, $carrier);
        }
    }

    /**
     * @return void
     */
    public function closeActiveSpan()
    {
        if (($scope = $this->tracer->getScopeManager()->getActive()) !== null) {
            $scope->close();
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
        $this->rootScope = $this->startActiveSpan(
            $this->operationNameFromBeginRequestEvent($event),
            $this->spanOptionsFromBeginRequestEvent($event)
        );
    }

    /**
     * @param \CEvent $event
     */
    public function handleEndRequestEvent(\CEvent $event)
    {
        if ($this->closeRootNode) {
            $this->rootScope->close();
        }
        $this->tracer->flush();
    }

    /**
     * @param \CExceptionEvent $event
     */
    public function handleExceptionEvent(\CExceptionEvent $event)
    {
        if (($span = $this->tracer->getActiveSpan()) !== null) {
            $span->setTag(\OpenTracing\Tags\ERROR, true);
            $this->setSentryTag($span);
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
            $this->setSentryTag($span);
            $span->log([
                'event' => 'error',
                'error.kind' => 'Error',
                'message' => $event->message,
                'stack' => sprintf('%s:%d', $event->file, $event->line),
            ]);
        }
    }

    private function setSentryTag(Span $span)
    {
        if ($this->sentryId && Yii::app()->hasComponent($this->sentryId)) {
            $span->setTag(\OpenTracing\Tags\ERROR . '.sentry_id', Yii::app()->{$this->sentryId}->getLastEventId());
        }
    }

    //endregion

    /**
     * @param \CEvent $event
     *
     * @return string
     */
    private function operationNameFromBeginRequestEvent(\CEvent $event)
    {
        /** @var \CApplication $application */
        $application = $event->sender;

        if ($application instanceof \CConsoleApplication) {
            return implode(' ', $_SERVER['argv']);
        }

        if ($application instanceof \CWebApplication) {
            return sprintf('application.%s', strtolower($application->request->getRequestType()));
        }

        return 'application';
    }

    /**
     * @param \CEvent $event
     *
     * @return array
     */
    private function spanOptionsFromBeginRequestEvent(\CEvent $event)
    {
        /** @var \CApplication $application */
        $application = $event->sender;

        $options = [
            'tags' => [
                \OpenTracing\Tags\COMPONENT => get_class($application),
            ],
        ];

        // Add HTTP related tags
        if ($application instanceof \CWebApplication) {
            try {
                $requestUri = $application->request->getRequestUri();
            } catch (\CException $exception) {
                $requestUri = '';
            }

            $options['tags'][\OpenTracing\Tags\HTTP_METHOD] = $application->request->getRequestType();
            $options['tags'][\OpenTracing\Tags\HTTP_URL] = sprintf(
                '%s%s',
                $application->request->getHostInfo(),
                $requestUri
            );
            $options['tags']['http.host'] = parse_url($application->request->getUrl(), PHP_URL_HOST);
            $options['tags']['http.uri'] = $requestUri;
        }

        // Mark application as child of another
        if (($spanContext = $this->tracer->extract(Formats\HTTP_HEADERS, getallheaders())) !== null) {
            $options['child_of'] = $spanContext;
        }

        return $options;
    }
}
