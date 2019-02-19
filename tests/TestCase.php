<?php

namespace Websupport\OpenTracing\Tests;

use Yii;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * @param array $config
     * @param string $appClass
     * @return \CApplication
     */
    protected function mockApplication($config = [], $appClass = \CWebApplication::class)
    {
        return Yii::createApplication($appClass, \CMap::mergeArray([
            'id' => 'testapp',
            'basePath' => __DIR__,
        ], $config));
    }

    protected function destroyApplication()
    {
        Yii::setApplication(null);
    }
}
