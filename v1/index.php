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

$app->post('/employees', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $firstName = $request->getParsedBodyParam('first_name');
    $lastName = $request->getParsedBodyParam('last_name');

    $res = $con->query("INSERT INTO employee(first_name, last_name) VALUES ('$firstName','$lastName')");
    if ($res) {
        $employee = [
            'id' => $con->insert_id,
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
        return $response->withStatus(201)->withJson($employee);
    } else {
        return $response->withStatus(400);
    }

});

// edting an employee
$app->post('/employees/{id}', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }
    $id = $args["id"];
    $firstName = $request->getParsedBodyParam('first_name');
    $lastName = $request->getParsedBodyParam('last_name');

    $res = $con->query("UPDATE employee SET first_name='$firstName', last_name='$lastName' WHERE id='$id'");
    if ($res) {
        $employee = [
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
        return $response->withStatus(200)->withJson($employee);
    } else {
        return $response->withStatus(400);
    }
});

// deleting an employee
$app->delete('/employees/{id}', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }
    $id = $args["id"];

    $res = $con->query("UPDATE employee SET status='0' WHERE id='$id'");
    if ($res) {
        return $response->withStatus(204);
    } else {
        return $response->withStatus(400);
    }
});

// list Employees
$app->get('/employees', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $_employees = $con->query("SELECT * FROM employee WHERE status='1'");
    $employees = [];
    while ($employee = $_employees->fetch_assoc())
        $employees[] = $employee;

    return $response->withStatus(200)->withJson(['employees' => $employees]);

});

// add new task
$app->post('/tasks', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $date = $request->getParsedBodyParam('date');
    $title = $request->getParsedBodyParam('title');
    $pcsCount = $request->getParsedBodyParam('pcs_count');

    $res = $con->query("INSERT INTO task(date, title, pcs_count) VALUES ('$date', '$title', '$pcsCount')");
    if ($res) {
        $task = [
            'id' => $con->insert_id,
            'date' => $date,
            'title' => $title,
            'pcs_count' => $pcsCount
        ];
        return $response->withStatus(201)->withJson($task);
    } else {
        return $response->withStatus(400);
    }
});

// list all tasks

$app->get('/tasks', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $_tasks = $con->query("SELECT id,date,title,pcs_count FROM task WHERE status='1'");
    $tasks = [];

    while ($task = $_tasks->fetch_assoc())
        $tasks[] = $task;

    return $response->withStatus(200)->withJson(['tasks' => $tasks]);

});

// get task detail

$app->get('/tasks/{id}', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];

    $_task = $con->query("SELECT id,date,title,pcs_count FROM task WHERE status='1' AND id='$id'");

    if ($_task->num_rows == 1)
        return $response->withStatus(200)->withJson($_task->fetch_assoc());
    else
        return $response->withStatus(204);
});

// update task
$app->post('/tasks/{id}', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];

    $date = $request->getParsedBodyParam('date');
    $title = $request->getParsedBodyParam('title');
    $pcsCount = $request->getParsedBodyParam('pcs_count');

    $res = $con->query("UPDATE task SET date = '$date', title = '$title', pcs_count='$pcsCount' WHERE id='$id' AND status='1'");

    if ($res) {
        $task = [
            'id' => $id,
            'date' => $date,
            'title' => $title,
            'pcs_count' => $pcsCount
        ];
        return $response->withStatus(200)->withJson($task);
    } else {
        return $response->withStatus(400);
    }


});

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
