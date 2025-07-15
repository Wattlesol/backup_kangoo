<?php

// Suppress deprecation warnings for production
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
ini_set("display_errors", "0");
ini_set("log_errors", "1");

// Original bootstrap
$app = require_once __DIR__."/bootstrap/app.php";

return $app;
