<?php

namespace Websupport\OpenTracing\Tests\Support\TestCase;

use Yii;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function tearDown()
    {
        $this->destroyApplication();

        parent::tearDown();
    }

    /**
     * @param array $config
     * @return \CApplication
     */
    protected function createApplication(array $config = [])
    {
        return Yii::createApplication(\CWebApplication::class, \CMap::mergeArray([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'components' => [],
        ], $config));
    }

    /**
     * @param string $class
     * @param array $config
     * @return \CApplicationComponent
     */
    protected function createApplicationComponent($class, array $config = [])
    {
        if (Yii::app() === null) {
            $this->createApplication();
        }

        $component = Yii::createComponent(\CMap::mergeArray([
            'class' => $class,
        ], $config));
        $component->init();

        return $component;
    }

    protected function destroyApplication()
    {
        Yii::setApplication(null);
    }
}
