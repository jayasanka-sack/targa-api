<?php
session_start();
require '../vendor/autoload.php';

use Targa\Middleware\Authentication as TargaAuth;

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);

$app->add(new TargaAuth());


try {
    $app->run();
} catch (\Slim\Exception\MethodNotAllowedException $e) {
} catch (\Slim\Exception\NotFoundException $e) {
} catch (Exception $e) {
    echo 'error';
}