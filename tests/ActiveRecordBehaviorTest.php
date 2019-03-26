<?php

namespace Websupport\OpenTracing\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Websupport\OpenTracing\OpenTracing;
use Websupport\OpenTracing\OpenTracingActiveRecordBehavior;
use Yii;

class ActiveRecordBehaviorTest extends TestCase
{
    public function testBehaviorEnabledWithOpentracingComponent()
    {
        $activeRecord = new TestActiveRecord();
        $activeRecord->attachBehavior('opentracing', OpenTracingActiveRecordBehavior::class);

        $this->assertTrue($activeRecord->asa('opentracing')->getEnabled());
    }

    public function testBehaviorDisabledWithoutOpentracingComponent()
    {
        $this->destroyApplication();
        $this->createApplication();

        $activeRecord = new TestActiveRecord();
        $activeRecord->attachBehavior('opentracing', OpenTracingActiveRecordBehavior::class);

        $this->assertFalse($activeRecord->asa('opentracing')->getEnabled());
    }

    public function testTraceFind()
    {
        $behaviorMock = $this->mockBehavior(['beforeFind', 'afterFind']);
        $behaviorMock->expects($this->once())->method('beforeFind');
        $behaviorMock->expects($this->once())->method('afterFind');

        // use anonymous class to bust private ::model() cache
        $activeRecordClass = new class extends TestActiveRecord {
        };
        $activeRecordClass::$behaviors = [
            $behaviorMock,
        ];

        $activeRecordClass::model()->find('id=1');
    }

    public function testTraceFindAll()
    {
        $behaviorMock = $this->mockBehavior(['beforeFind', 'afterFind']);
        $behaviorMock->expects($this->once())->method('beforeFind');
        $behaviorMock->expects($this->atLeast(2))->method('afterFind');

        // use anonymous class to bust private ::model() cache
        $activeRecordClass = new class extends TestActiveRecord {
        };
        $activeRecordClass::$behaviors = [
            $behaviorMock,
        ];

        $activeRecordClass::model()->findAll();
    }

    public function testTraceSave()
    {
        $behaviorMock = $this->mockBehavior(['beforeSave', 'afterSave']);
        $behaviorMock->expects($this->once())->method('beforeSave');
        $behaviorMock->expects($this->once())->method('afterSave');

        TestActiveRecord::$behaviors = [
            $behaviorMock,
        ];

        $activeRecord = new TestActiveRecord();
        $activeRecord->id = 100;
        $activeRecord->save();
    }

    public function testTraceDelete()
    {
        $behaviorMock = $this->mockBehavior(['beforeDelete', 'afterDelete']);
        $behaviorMock->expects($this->once())->method('beforeDelete');
        $behaviorMock->expects($this->once())->method('afterDelete');

        // use anonymous class to bust private ::model() cache
        $activeRecordClass = new class extends TestActiveRecord {
        };
        $activeRecordClass::$behaviors = [
            $behaviorMock,
        ];

        $activeRecord = $activeRecordClass::model()->findByPk(1);
        $activeRecord->delete();
    }

    protected function setUp()
    {
        parent::setUp();

        $sourceDb = sprintf('%s/resources/fixtures.sqlite', Yii::getPathOfAlias('tests'));
        $runtimeDb = sprintf('%s/runtime/db.sqlite', Yii::getPathOfAlias('tests'));
        copy($sourceDb, $runtimeDb);

        $this->createApplication([
            'components' => [
                'opentracing' => [
                    'class' => OpenTracing::class,
                ],
            ],
        ]);
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

    /**
     * @param array $methods
     * @return MockObject|OpenTracingActiveRecordBehavior
     */
    private function mockBehavior(array $methods)
    {
        $mock = $this->getMockBuilder(OpenTracingActiveRecordBehavior::class)
            ->setMethods($methods)
            ->getMock();

        return $mock;
    }
}
