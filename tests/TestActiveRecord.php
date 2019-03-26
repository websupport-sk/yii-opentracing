<?php

namespace Websupport\OpenTracing\Tests;

/**
 * @property int $id
 */
class TestActiveRecord extends \CActiveRecord
{
    /**
     * Static public array of behaviors to enable setting mocked behavior before running constructor
     * @var \IBehavior[]
     */
    public static $behaviors = [];

    public static function model($className = __CLASS__)
    {
        // use late static binding class name for anonymous testing classes
        return parent::model(static::class);
    }

    public function behaviors()
    {
        return static::$behaviors;
    }

    public function tableName()
    {
        return 'TestActiveRecord';
    }
}
