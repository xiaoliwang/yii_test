<?php

$frameworkPath = dirname(__FILE__) . '/../../vendor/yiisoft/yii2';

if (!is_dir($frameworkPath)) {
    echo '<h1>Error</h1>';
    echo '<p><strong>The path to yii framework seems to be incorrect.</strong></p>';
    echo '<p>You need to install Yii framework via composer or adjust the framework path in file <abbr title="' . __FILE__ . '">' . basename(__FILE__) . '</abbr>.</p>';
    echo '<p>Please refer to the <abbr title="' . dirname(__FILE__) . '/README.md">README</abbr> on how to install Yii.</p>';
}

require_once($frameworkPath . '/requirements/YiiRequirementChecker.php');
$requirementsChecker = new YiiRequirementChecker();

$redisMemo = '';
$redisOk = false;

$requirements = [
    [
        'name' => 'Redis extension',
        'mandatory' => false,
        'condition' => extension_loaded('redis'),
        'by' => 'All Redis components',
        'memo' => 'Redis extension should be installed'
    ], [
        'name' => 'PHP version',
        'mandatory' => true,
        'condition' => version_compare(phpversion(), '7.1', '>='),
        'by' => 'All Redis components',
        'memo' => 'PHP 7.1.0 or higher is required.'
    ]
];


$requirementsChecker->check($requirements)->render();