<?php
// ensure we get report on all possible php errors
error_reporting(-1);

defined('YII_DEBUG') or define('YII_DEBUG', true);

$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/yiisoft/yii/framework/yiit.php';

// make sure non existing PHPUnit classes do not break with Yii autoloader
Yii::$enableIncludePath = false;
Yii::setPathOfAlias('tests', __DIR__);
Yii::import('tests.*');
