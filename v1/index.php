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

// delete a task

$app->delete('/tasks/{id}', function ($request, $response, $args) {

    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];

    $res = $con->query("UPDATE task SET status='0' WHERE id='$id'");

    if ($res) {
        return $response->withStatus(204);
    } else {
        return $response->withStatus(400);
    }

});

// insert a job

$app->post('/tasks/{task_id}/jobs', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $task_id = $args['task_id'];
    $title = $request->getParsedBodyParam('title');
    $pph = $request->getParsedBodyParam('pph');

    $res = $con->query("INSERT INTO job(task_id, title, pph) VALUES ('$task_id','$title','$pph')");
    if ($res) {
        $job = [
            'id' => $con->insert_id,
            'task_id' => $task_id,
            'title' => $title,
            'pph' => $pph
        ];
        return $response->withStatus(201)->withJson($job);
    } else {
        return $response->withStatus(400);
    }
});

// edit a job

$app->post('/jobs/{id}', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];
    $title = $request->getParsedBodyParam('title');
    $pph = $request->getParsedBodyParam('pph');

    $res = $con->query("UPDATE job SET title='$title', pph='$pph' WHERE id='$id' AND status='1'");
    if ($res) {
        $job = [
            'id' => $id,
            'title' => $title,
            'pph' => $pph
        ];
        return $response->withStatus(200)->withJson($job);
    } else {
        return $response->withStatus(400);
    }
});

// list all jobs in a task

$app->get('/tasks/{task_id}/jobs', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $task_id = $args['task_id'];

    $_jobs = $con->query("SELECT id,task_id,title,pph FROM job WHERE status='1' AND task_id='$task_id'");
    $jobs = [];
    while ($job = $_jobs->fetch_assoc())
        $jobs[] = $job;

    return $response->withStatus(200)->withJson($jobs);
});

// delete a job

$app->delete('/jobs/{id}', function ($request, $response, $args) {

    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];

    $res = $con->query("UPDATE job SET status='0' WHERE id='$id'");

    if ($res) {
        return $response->withStatus(204);
    } else {
        return $response->withStatus(400);
    }
});

// insert count

$app->post('/jobs/{job_id}/counts', function ($request, $response, $args) {
    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $job_id = $args['job_id'];
    $employee_id = $request->getParsedBodyParam('employee_id');
    $jobHour = $request->getParsedBodyParam('job_hour');
    $pcs = $request->getParsedBodyParam('pcs');

    $res = $con->query("INSERT INTO hour_count(employee_id, job_id, job_hour,pcs) VALUES ('$employee_id', '$job_id', '$jobHour', '$pcs')");
    if ($res) {
        $count = [
            'id' => $con->insert_id,
            'employee_id' => $employee_id,
            'job_id' => $job_id,
            'job_hour' => $jobHour,
            'pcs' => $pcs
        ];
        return $response->withStatus(201)->withJson($count);
    } else {
        return $response->withStatus(400);
    }
});

// delete a hour count

$app->delete('/counts/{id}', function ($request, $response, $args) {

    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];

    $res = $con->query("DELETE FROM hour_count WHERE id='$id'");

    if ($res) {
        return $response->withStatus(204);
    } else {
        return $response->withStatus(400);
    }
});

// get basic stats of a task

$app->get('/tasks/{id}/percentage', function ($request, $response, $args) {

    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];

    $_result = $con->query("SELECT SUM(t.pcs_count) as total_pcs, SUM(h.pcs) as pcs, COUNT(j.id) AS job_count FROM task t INNER JOIN job j on t.id = j.task_id INNER JOIN hour_count h ON j.id = h.job_id INNER JOIN employee e on h.employee_id = e.id WHERE t.id='$id' AND t.status='1' AND j.status='1' AND e.status='1'");

    $total = 0;
    $completed = 0;

    $completedPercentage = 0;

    if ($_result->num_rows == 1) {
        $result = $_result->fetch_assoc();
        $total = $result['total_pcs'] * $result['job_count'];
        $completed = $result['pcs'];

        $completedPercentage = $completed / $total * 100;
    }

    $payload = [
        'numeric' => [
            'total' => $total,
            'completed' => intval($completed)
        ],
        'percentage' => [
            'completed' => $completedPercentage,
            'remaining' => 100 - $completedPercentage
        ]
    ];

    return $response->withStatus(200)->withJson($payload);

});

// get hour counts by employee
$app->get('/tasks/{id}/hour-count-by-employee', function ($request, $response, $args) {

    global $con;

    if ($request->getAttribute('logged') == false) {
        return $response->withStatus(403);
    }

    $id = $args['id'];

    $employees = $con->query("SELECT employee_id, first_name, last_name FROM task t INNER JOIN job j on t.id = j.task_id INNER JOIN hour_count h ON j.id = h.job_id INNER JOIN employee e on h.employee_id = e.id WHERE t.id='$id' AND t.status='1' AND j.status='1' AND e.status='1' GROUP BY e.id");
    $hourCounts = [];
    while ($_employee = $employees->fetch_assoc()){
        $employee = $_employee;
        $employeeId = $_employee["employee_id"];
        $totalPcs = 0;
        $totalPoints = 0;
        $_counts = $con->query("SELECT j.id, j.title, j.pph, h.job_hour, h.pcs FROM task t INNER JOIN job j on t.id = j.task_id INNER JOIN hour_count h ON j.id = h.job_id INNER JOIN employee e on h.employee_id = e.id WHERE t.id='$id' AND t.status='1' AND j.status='1' AND e.id='$employeeId'");

        while ($count = $_counts->fetch_assoc()){
            $counts = $count;
            $points = $counts["pph"]*$counts["pcs"]/100;
            $counts["pph"] = intval($count["pph"]);
            $counts["pcs"] = intval($count["pcs"]);
            $counts["points"] = $points;
            $employee["counts"][] = $counts;

            $totalPcs+= $count["pcs"];
            $totalPoints+= $points;

        }
        $employee["total_pcs"] = $totalPcs;
        $employee["total_points"] = $totalPoints;
        $hourCounts[] = $employee;
    }
    return $response->withStatus(200)->withJson(['hour_counts'=>$hourCounts]);

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
