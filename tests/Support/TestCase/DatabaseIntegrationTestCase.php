<?php

namespace Websupport\OpenTracing\Tests\Support\TestCase;

use Yii;

abstract class DatabaseIntegrationTestCase extends TestCase
{
    protected function setUp():void
    {
        parent::setUp();

        $sourceDb = sprintf('%s/resources/fixtures.sqlite', Yii::getPathOfAlias('tests'));
        $runtimeDb = sprintf('%s/runtime/db.sqlite', Yii::getPathOfAlias('tests'));
        copy($sourceDb, $runtimeDb);
    }

    protected function createApplication(array $config = [])
    {
        return parent::createApplication(\CMap::mergeArray([
            'components' => [
                'db' => [
                    'connectionString' => sprintf('sqlite:%s/runtime/db.sqlite', Yii::getPathOfAlias('tests')),
                ],
            ],
        ], $config));
    }
}
