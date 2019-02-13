<?php

namespace Websupport\OpenTracing;

use Jaeger\Config;
use Psr\Log\LoggerInterface;

class JaegerOpenTracing extends OpenTracing
{
    /** @var ?string */
    public $agentHost;

    /** @var ?int */
    public $agentPort;

    /** @var ?string */
    public $baggageHeaderPrefix;

    /** @var ?string */
    public $debugIdHeaderKey;

    /** @var ?bool */
    public $logging;

    /** @var ?array */
    public $sampler;

    /** @var ?string */
    public $traceIdHeader;

    /** @var ?LoggerInterface */
    private $logger;

    protected function initTracer()
    {
        $tracer = (new Config(
            [
                'baggage_header_prefix' => $this->baggageHeaderPrefix,
                'debug_id_header_key' => $this->debugIdHeaderKey,
                'local_agent' => [
                    'reporting_host' => $this->agentHost,
                    'reporting_port' => $this->agentPort,
                ],
                'logging' => $this->logging,
                'sampler' => $this->sampler,
                'trace_id_header' => $this->traceIdHeader,
            ],
            $this->serviceName,
            $this->logger
        ))->initializeTracer();
        $this->setTracer($tracer);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
