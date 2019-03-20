<?php

namespace Websupport\OpenTracing;

use CEvent;
use CModelEvent;
use OpenTracing\Scope;

/**
 * Class OpenTracingActiveRecordBehavior
 * @package Websupport\OpenTracing
 *
 * @method \CActiveRecord getOwner()
 */
class OpenTracingActiveRecordBehavior extends \CActiveRecordBehavior
{
    /** @var string */
    public $opentracingId = 'opentracing';

    /**
     * Whether we should hook to before and after find methods.
     * Be careful with this option. There is option, where you can disable calling afterFind method
     * directly in the code.
     * @var bool
     */
    public $traceFind = false;

    /**
     * Whether we should hook to before and after save methods.
     * @var bool
     */
    public $traceSave = true;

    /**
     * Whether we should hook to before and after delete methods.
     * @var bool
     */
    public $traceDelete = true;

    /** @var ?Scope */
    private $activeScope;

    public function attach($owner)
    {
        parent::attach($owner);

        if (!\Yii::app()->hasComponent($this->opentracingId)) {
            $this->setEnabled(false);
        }
    }

    /**
     * Responds to {@link CActiveRecord::onBeforeSave} event.
     * Override this method and make it public if you want to handle the corresponding
     * event of the {@link CBehavior::owner owner}.
     * You may set {@link CModelEvent::isValid} to be false to quit the saving process.
     * @param CModelEvent $event event parameter
     * @throws
     */
    public function beforeSave($event)
    {
        if (!$this->traceSave) {
            return;
        }

        $this->activeScope = \Yii::app()->getComponent($this->opentracingId)->startActiveSpan(
            $this->spanName($this->getOwner()->getIsNewRecord() ? 'INSERT' : 'UPDATE'),
            $this->spanOptionsFromActiveRecordClass()
        );
        $this->activeScope->getSpan()->log([
            'attributes' => $this->getOwner()->getAttributes()
        ]);
    }

    /**
     * Responds to {@link CActiveRecord::onAfterSave} event.
     * Override this method and make it public if you want to handle the corresponding event
     * of the {@link CBehavior::owner owner}.
     * @param CEvent $event event parameter
     */
    public function afterSave($event)
    {
        if (!$this->traceSave && $this->activeScope) {
            return;
        }
        $this->activeScope->getSpan()->setTag('db.active_record.primary_key', $this->getPrimaryKeyValue());
        $this->activeScope->close();
        $this->activeScope = null;
    }

    /**
     * Responds to {@link CActiveRecord::onBeforeDelete} event.
     * Override this method and make it public if you want to handle the corresponding event
     * of the {@link CBehavior::owner owner}.
     * You may set {@link CModelEvent::isValid} to be false to quit the deletion process.
     * @param CEvent $event event parameter
     */
    public function beforeDelete($event)
    {
        if (!$this->traceDelete) {
            return;
        }

        $options = $this->spanOptionsFromActiveRecordClass();
        $options['tags']['db.active_record.primary_key'] = $this->getPrimaryKeyValue();

        $this->activeScope = \Yii::app()->getComponent($this->opentracingId)
            ->startActiveSpan($this->spanName('DELETE'), $options);
    }

    /**
     * Responds to {@link CActiveRecord::onAfterDelete} event.
     * Override this method and make it public if you want to handle the corresponding event
     * of the {@link CBehavior::owner owner}.
     * @param CEvent $event event parameter
     */
    public function afterDelete($event)
    {
        if (!$this->traceDelete) {
            return;
        }
        $this->activeScope->close();
        $this->activeScope = null;
    }

    /**
     * Responds to {@link CActiveRecord::onBeforeFind} event.
     * Override this method and make it public if you want to handle the corresponding event
     * of the {@link CBehavior::owner owner}.
     * @param CEvent $event event parameter
     */
    public function beforeFind($event)
    {
        if (!$this->traceFind) {
            return;
        }

        $this->activeScope = \Yii::app()->getComponent($this->opentracingId)
            ->startActiveSpan($this->spanName('FIND'), $this->spanOptionsFromActiveRecordClass());
    }

    /**
     * Responds to {@link CActiveRecord::onAfterFind} event.
     * Override this method and make it public if you want to handle the corresponding event
     * of the {@link CBehavior::owner owner}.
     * @param CEvent $event event parameter
     */
    public function afterFind($event)
    {
        if (!$this->traceFind) {
            return;
        }

        $this->activeScope->getSpan()->log(['criteria' => $this->getOwner()->getDbCriteria()->toArray()]);
        $this->activeScope->close();
        $this->activeScope = null;
    }

    private function spanName($action)
    {
        return sprintf('db.active_record.%s', strtolower($action));
    }

    private function spanOptionsFromActiveRecordClass()
    {
        return [
            'tags' => [
                \OpenTracing\Tags\COMPONENT => 'yii-opentracing.activerecord',
                \OpenTracing\Tags\DATABASE_TYPE => $this > $this->getDatabaseType(),
                \OpenTracing\Tags\DATABASE_USER => $this->getOwner()->getDbConnection()->username,
                'db.active_record.class' => get_class($this->getOwner())
            ]
        ];
    }

    private function getDatabaseType()
    {
        $dbConnection = $this->getOwner()->getDbConnection();
        if (isset($dbConnection->driverMap[$dbConnection->getDriverName()])) {
            return 'sql';
        }
        return '';
    }

    private function getPrimaryKeyValue()
    {
        return implode(',', (array) $this->getOwner()->getPrimaryKey());
    }
}
