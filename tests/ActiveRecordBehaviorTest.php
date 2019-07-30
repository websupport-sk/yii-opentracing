<?php

namespace Websupport\OpenTracing\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Websupport\OpenTracing\OpenTracing;
use Websupport\OpenTracing\OpenTracingActiveRecordBehavior;
use Websupport\OpenTracing\Tests\Support\ActiveRecord\MockableBehaviorActiveRecord;
use Websupport\OpenTracing\Tests\Support\TestCase\DatabaseIntegrationTestCase;

class ActiveRecordBehaviorTest extends DatabaseIntegrationTestCase
{
    public function testTraceFind()
    {
        $behaviorMock = $this->mockBehavior(['beforeFind', 'afterFind']);
        $behaviorMock->expects($this->once())->method('beforeFind');
        $behaviorMock->expects($this->once())->method('afterFind');

        // use anonymous class to bust private ::model() cache
        $activeRecordClass = new class extends MockableBehaviorActiveRecord {
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
        $activeRecordClass = new class extends MockableBehaviorActiveRecord {
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

        MockableBehaviorActiveRecord::$behaviors = [
            $behaviorMock,
        ];

        $activeRecord = new MockableBehaviorActiveRecord();
        $activeRecord->id = 100;
        $activeRecord->save();
    }

    public function testTraceDelete()
    {
        $behaviorMock = $this->mockBehavior(['beforeDelete', 'afterDelete']);
        $behaviorMock->expects($this->once())->method('beforeDelete');
        $behaviorMock->expects($this->once())->method('afterDelete');

        // use anonymous class to bust private ::model() cache
        $activeRecordClass = new class extends MockableBehaviorActiveRecord {
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

        $this->createApplication([
            'components' => [
                'opentracing' => [
                    'class' => OpenTracing::class,
                ],
            ],
        ]);
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
