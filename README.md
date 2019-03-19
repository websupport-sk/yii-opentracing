![Code Climate coverage](https://img.shields.io/codeclimate/coverage/websupport-sk/yii-opentracing.svg)
![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/websupport-sk/yii-opentracing.svg)
![Travis](https://img.shields.io/travis/com/websupport-sk/yii-opentracing.svg)

# Yii OpenTracing extension

OpenTracing extension for Yii 1

## Installation

Install Yii extension with composer

```bash
composer require websupport/yii-opentracing
```

Install client library (depends on your tracing system)

```bash
composer require jonahgeorge/jaeger-client-php
``` 

## Configuration

Default (NoopTracer) configuration without client library

```php
    # opentracing component must be preloaded
    'preload' => ['opentracing'],
    ...
    'components' => [
        'opentracing' => [
            'class' => \Websupport\OpenTracing\OpenTracing::class,
        ],
    ],
```

Jaeger client configuration

```php
    # opentracing component must be preloaded
    'preload' => ['opentracing'],
    ...
    'components' => [
        'opentracing' => [
            'class' => \Websupport\OpenTracing\JaegerOpenTracing::class,
            'agentHost' => 'localhost',
            'agentPort' => 5775,
            'sampler' => [
                'type' => \Jaeger\SAMPLER_TYPE_CONST,
                'param' => true,
            ],
            'traceIdHeader' => 'x-trace-id',
            'baggageHeaderPrefix' => 'x-ctx-trace-',
        ],
    ],
```

### OpenTracing in `CActiveRecord`
OpenTracing can be enabled in `CActiveRecord` using `behaviors`. 
```php
<?php

use Websupport\OpenTracing\OpenTracingActiveRecordBehavior;

class Model extends CActiveRecord
{
    public function behaviors()
    {
        return [
            'OpenTracingActiveRecordBehavior' => [
                'class' => OpenTracingActiveRecordBehavior::class,
                'opentracingId' => 'opentracing' // string opentracing component name
            ]
        ];
    }
}
```