<?php
session_start();
require '../vendor/autoload.php';
include 'config/connection.php';

use Targa\Middleware\Authentication as TargaAuth;

$serverAddress = '/projects/agni-targa';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);

$app->add(new TargaAuth());


// login

$app->post('/login', function ($request, $response, $args) {
    global $con;

    $username = $request->getParsedBodyParam('username', '');
    $password = md5($request->getParsedBodyParam('password', ''));


    //check for matching admin
    $_admin = $con->query("SELECT id,username FROM admin WHERE username='$username' AND password='$password' AND status='1'");

    if (mysqli_num_rows($_admin) == 1) {
        $admin = mysqli_fetch_assoc($_admin);

        // save new data in session
        setSession($admin['id'], $admin['username']);

        return $response->withStatus(200)->withJson(['logged' => true]);
    }
    return $response->withStatus(401)->withJson(['logged' => false]);
});

// check user status

$app->get('/user-status', function ($request, $response, $args) {
    if ($request->getAttribute('logged') == true) {
        return $response->withStatus(200);
    } else {
        return $response->withStatus(403);
    }
});


// logout
$app->get('/logout', function ($request, $response, $args) {
    session_destroy();
    return $response->withStatus(200)->withJson(['logged' => false]);
});

// adding a new employee

$app->post('/employee', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $firstName = $request->getParsedBodyParam('first_name');
    $lastName = $request->getParsedBodyParam('last_name');

    $res = $con->query("INSERT INTO employee(first_name, last_name) VALUES ('$firstName','$lastName')");
    if($res){
        return $response->withStatus(201);
    }else{
        return $response->withStatus(400);
    }

});

// edting an employee



try {
    $app->run();
} catch (\Slim\Exception\MethodNotAllowedException $e) {
} catch (\Slim\Exception\NotFoundException $e) {
} catch (Exception $e) {
    echo 'error';
}


function setSession($id, $displayName)
{
    $_SESSION['logged'] = true;
    $_SESSION['id'] = $id;
    $_SESSION['displayName'] = $displayName;
}
