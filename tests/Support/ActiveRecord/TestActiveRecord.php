<?php

namespace Websupport\OpenTracing\Tests\Support\ActiveRecord;

use Websupport\OpenTracing\OpenTracingActiveRecordBehavior;

/**
 * @property int $id
 *
 * @property-read OpenTracingActiveRecordBehavior $opentracingBehavior
 */
final class TestActiveRecord extends \CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function behaviors()
    {
        return [
            'opentracingBehavior' => [
                'class' => OpenTracingActiveRecordBehavior::class,
                'traceFind' => true,
                'traceSave' => true,
                'traceDelete' => true,
            ],
        ];
    }

    public function tableName()
    {
        return 'test_table';
    }
}
