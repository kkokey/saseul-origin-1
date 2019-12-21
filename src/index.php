<?php

# const
define('ROOT_DIR', dirname(__DIR__));

# autoload;
require_once("autoload.php");

# set ini;
session_start();
date_default_timezone_set('Asia/Seoul');
ini_set('memory_limit','128M');
header('Access-Control-Allow-Origin: *');

# execute script;
$apiLoader = new Saseul\Common\ApiLoader();
$apiLoader->main();

# TODO: Modifying getRandomValidator
# TODO: Making debug mode
# TODO: Testing mongoDB injection

# TODO: 두 Node가 같은 Address로 다른 의견 내는 경우의 문제.
# TODO: Arbiter 기능 공개.
