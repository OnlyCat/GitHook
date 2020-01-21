<?php
ini_set('display_errors',1);            //错误信息
ini_set('display_startup_errors',1);    //php启动错误信息
error_reporting(-1);

require_once dirname(__FILE__) . "/action/BootStrap.php";
BootStrap::init()->run();