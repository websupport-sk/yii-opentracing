<?php

namespace Websupport\OpenTracing\Tests;

use OpenTracing\Scope;
use OpenTracing\Span;
use PHPUnit\Framework\MockObject\MockObject;
use Websupport\OpenTracing\OpenTracing;
use Websupport\OpenTracing\Tests\Support\ActiveRecord\TestActiveRecord;
use Websupport\OpenTracing\Tests\Support\TestCase\DatabaseIntegrationTestCase;

class ActiveRecordBehaviorIntegrationTest extends DatabaseIntegrationTestCase
{
    public function testBehaviorIsEnabledWithOpentracingApplicationComponent()
    {
        $this->createApplication([
            'components' => [
                'opentracing' => [
                    'class' => OpenTracing::class,
                ],
            ],
        ]);

        $model = new TestActiveRecord();

        $this->assertTrue($model->opentracingBehavior->enabled);
    }

    public function testBehaviorIsDisabledWithoutOpentracingApplicationComponent()
    {
        $this->createApplication();

        $model = new TestActiveRecord();

        $this->assertFalse($model->opentracingBehavior->enabled);
    }

    public function testTraceFindDisabled()
    {
        $opentracingMock = $this->createApplicationWithMockedOpentracingComponent();
        $opentracingMock->expects($this->never())->method('startActiveSpan');

        $activeRecord = TestActiveRecord::model();
        $activeRecord->opentracingBehavior->traceFind = false;
        $activeRecord->find('id=1');
        $activeRecord->opentracingBehavior->traceFind = true;
    }

    public function testTraceFind()
    {
        $scopeMock = $this->mockScope();
        $scopeMock->expects($this->once())->method('close');

        $opentracingMock = $this->createApplicationWithMockedOpentracingComponent();
        $opentracingMock->expects($this->once())
            ->method('startActiveSpan')
            ->with($this->stringContains('FIND'))
            ->willReturn($scopeMock);

        TestActiveRecord::model()->find('id=1');
    }

    public function testTraceFindAll()
    {
        $scopeMock = $this->mockScope();
        $scopeMock->expects($this->once())->method('close');

        $opentracingMock = $this->createApplicationWithMockedOpentracingComponent();
        $opentracingMock->expects($this->once())
            ->method('startActiveSpan')
            ->with($this->stringContains('FIND'))
            ->willReturn($scopeMock);

        TestActiveRecord::model()->findAll();
    }

    public function testTraceSave()
    {
        $scopeMock = $this->mockScope();
        $scopeMock->expects($this->once())->method('close');

        $opentracingMock = $this->createApplicationWithMockedOpentracingComponent();
        $opentracingMock->expects($this->once())
            ->method('startActiveSpan')
            ->with($this->stringContains('INSERT'))
            ->willReturn($scopeMock);

        $activeRecord = new TestActiveRecord();
        $activeRecord->id = 100;
        $activeRecord->save();
    }

    public function testTraceDelete()
    {
        $findScopeMock = $this->mockScope();
        $findScopeMock->expects($this->once())->method('close');

        $deleteScopeMock = $this->mockScope();
        $deleteScopeMock->expects($this->once())->method('close');

        $opentracingMock = $this->createApplicationWithMockedOpentracingComponent();
        $opentracingMock->expects($this->at(0))
            ->method('startActiveSpan')
            ->with($this->stringContains('FIND'))
            ->willReturn($findScopeMock);
        $opentracingMock->expects($this->at(1))
            ->method('startActiveSpan')
            ->with($this->stringContains('DELETE'))
            ->willReturn($deleteScopeMock);

        $activeRecord = TestActiveRecord::model()->findByPk(1);
        $activeRecord->delete();
    }

    /**
     * @return MockObject|OpenTracing
     */
    private function createApplicationWithMockedOpentracingComponent()
    {
        $mock = $this->getMockBuilder(OpenTracing::class)
            ->setMethods(['startActiveSpan'])
            ->getMock();

        $this->createApplication([
            'components' => [
                'opentracing' => $mock,
            ],
        ]);

        return $mock;
    }

    /**
     * @return MockObject|Scope
     */
    private function mockScope()
    {
        $spanMock = $this->createMock(Span::class);
        $scopeMock = $this->createPartialMock(Scope::class, ['getSpan', 'close']);
        $scopeMock->method('getSpan')->willReturn($spanMock);

        return $scopeMock;
    }
}
