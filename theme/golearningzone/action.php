<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/lib/Actions.php';

$action = isset($_GET['Action']) ? ucfirst(strtolower($_GET['Action'])) : '';
$target = isset($_GET['Target']) ? ucfirst(strtolower($_GET['Target'])) : '';

$actionFullName = "\GoLearningZone\Actions\\$target\\$action";
if (class_exists($actionFullName)) {
	unset($_GET['Action']);
	unset($_GET['Target']);
	$params = $_GET + $_POST;
	$actionFullName::create()->run($params);
}

