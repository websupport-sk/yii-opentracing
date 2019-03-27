<?php

namespace Websupport\OpenTracing;

use CEvent;
use CModelEvent;
use OpenTracing\Scope;
use Yii;

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
     * Also, in case, you are retrieving multiple ActiveRecords, beforeFind is called just once, but afterFind is called
     * for every found record.
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

    /**
     * Associative array of active scopes [classname => scope]
     * @var Scope[]
     */
    private static $activeScopes = [];

    public function attach($owner)
    {
        parent::attach($owner);

        if (!Yii::app()->hasComponent($this->opentracingId)) {
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

        $this->startActiveScope(
            $this->spanName($this->getOwner()->getIsNewRecord() ? 'INSERT' : 'UPDATE'),
            $this->spanTagsFromActiveRecord(),
            ['attributes' => $this->getOwner()->getAttributes()]
        );
    }

    /**
     * Responds to {@link CActiveRecord::onAfterSave} event.
     * Override this method and make it public if you want to handle the corresponding event
     * of the {@link CBehavior::owner owner}.
     * @param CEvent $event event parameter
     */
    public function afterSave($event)
    {
        if (!$this->traceSave) {
            return;
        }

        $this->closeActiveScope(['db.active_record.primary_key' => $this->getPrimaryKeyValue()]);
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

        $this->startActiveScope(
            $this->spanName('DELETE'),
            array_merge(
                $this->spanTagsFromActiveRecord(),
                ['db.active_record.primary_key' => $this->getPrimaryKeyValue()]
            )
        );
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

        $this->closeActiveScope();
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

        $this->startActiveScope($this->spanName('FIND'), $this->spanTagsFromActiveRecord());
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

        $this->closeActiveScope([], ['criteria' => $this->getOwner()->getDbCriteria()->toArray()]);
    }

    private function startActiveScope(string $operationName, array $tags = [], array $log = [])
    {
        $className = get_class($this->getOwner());
        if (isset(self::$activeScopes[$className])) {
            $logMessage = sprintf('Active span for class %s found, closing it!', $className);
            $this->closeActiveScope([], ['event' => 'error', 'message' => $logMessage]);
            Yii::log($logMessage, \CLogger::LEVEL_WARNING, 'opentracing');
        }

        $scope = Yii::app()->getComponent($this->opentracingId)->startActiveSpan($operationName, [
            'tags' => $tags,
        ]);

        if (!empty($log)) {
            $scope->getSpan()->log($log);
        }

        self::$activeScopes[$className] = $scope;
    }

    private function closeActiveScope(array $tags = [], array $log = [])
    {
        $className = get_class($this->getOwner());
        if (!isset(self::$activeScopes[$className])) {
            return;
        }

        $scope = self::$activeScopes[$className];
        foreach ($tags as $key => $value) {
            $scope->getSpan()->setTag($key, $value);
        }

        if (!empty($log)) {
            $scope->getSpan()->log($log);
        }

        $scope->close();

        unset(self::$activeScopes[$className]);
    }

    private function spanName(string $action)
    {
        return sprintf('db.active_record.%s', strtolower($action));
    }

    /**
     * @return array
     */
    private function spanTagsFromActiveRecord()
    {
        return [
            \OpenTracing\Tags\COMPONENT => 'yii-opentracing.activerecord',
            \OpenTracing\Tags\DATABASE_TYPE => $this->getDatabaseType(),
            \OpenTracing\Tags\DATABASE_USER => $this->getOwner()->getDbConnection()->username,
            'db.active_record.class' => get_class($this->getOwner()),
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
