<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

// WELCOME
$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

// GET ONE COURSE
$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

// GET FORM FOR POST NEW USER
$app->get('/users/new', function ($request, $response) {
    $user['id'] = uniqid;
    $params = [
        'user' => ['id' => $id, 'name' => '', 'email' => '']
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$file = 'src/users.json'; // FILE TO SAVE USERS

// POST NEW USER
$app->post('/users', function ($request, $response) use ($file) {
    $user = $request->getParsedBodyParam('user');
    $fileUser = json_decode(file_get_contents($file));
    $fileUser[] = $user;
    file_put_contents($file, json_encode($fileUser));
    return $response->withRedirect('/users', 302);

    $params = ['user' => $user];
    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
});

// GET ALL USERS
$users = ['Mike', 'Mishel', 'Adel', 'Keks', 'Kamila'];
$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $filteredUsers = array_filter($users, fn($user) => str_contains($user, $term) === true);
    $params = ['users' => $filteredUsers];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

// GET ONE USER
$app->get('/users/{id}', function ($request, $response, $args) use ($file) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    if (str_contains($file, $args['id'])) {
        return $response->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->run();