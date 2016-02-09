<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
/** @var Application $app */
$app = require_once dirname(__DIR__).'/app.php';
$app->handle(Request::createFromGlobals());
