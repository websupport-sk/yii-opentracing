[![Code Climate coverage](https://img.shields.io/codeclimate/coverage/websupport-sk/yii-opentracing.svg)](https://codeclimate.com/github/websupport-sk/yii-opentracing)
[![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/websupport-sk/yii-opentracing.svg)](https://codeclimate.com/github/websupport-sk/yii-opentracing)
[![Travis](https://img.shields.io/travis/com/websupport-sk/yii-opentracing.svg)](https://travis-ci.com/websupport-sk/yii-opentracing)

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

### Sentry integration
If you are using [Sentry](https://sentry.io) to track errors and want to store Sentry Event ID within current trace, 
you can achieve this in conjunction with [websupport/yii-sentry](https://github.com/websupport-sk/yii-sentry) component.

After installing and configuring this component, each trace, where any error occurred will have its `error.sentry_id` tag filled with Sentry Event ID.

```php
    'components' => [
        'opentracing' => [
            'class' => \Websupport\OpenTracing\JaegerOpenTracing::class,
            'sentry' => 'sentry' // or name of your yii-sentry component
            ...
        ],
        'sentry' => [ // yii-sentry component
            'class' => \Websupport\YiiSentry\Client::class
            ...
        ]
    ],
```
