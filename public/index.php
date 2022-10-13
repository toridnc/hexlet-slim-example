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

$router = $app->getRouteCollector()->getRouteParser();

// WELCOME
$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
})->setName('welcome');

// GET ONE COURSE
$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');

// GET FORM FOR POST NEW USER
$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'id' => $id]
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('createNewUser');

$file = 'src/users.json'; // FILE TO SAVE USERS

// POST NEW USER
$app->post('/users', function ($request, $response) use ($file, $router) {
    $user = $request->getParsedBodyParam('user');
    $fileUser = json_decode(file_get_contents($file));
    $user['id'] = uniqid();
    $fileUser[] = $user;
    file_put_contents($file, json_encode($fileUser));
    $this->get('flash')->addMessage('success', 'User was added successfully');
    return $response->withRedirect($router->urlFor('users'), 302);

    $params = ['user' => $user];
    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
})->setName('postNewUser');

// GET ALL USERS
$users = ['Mike', 'Mishel', 'Adel', 'Keks', 'Kamila'];
$users[] = json_decode(file_get_contents(array_values($file)));
$app->get('/users', function ($request, $response) use ($users) {
    $messages = $this->get('flash')->getMessages();
    $term = $request->getQueryParam('term');
    $filteredUsers = array_filter($users, fn($user) => str_contains($user, $term) === true);
    $params = [
        'users' => $filteredUsers,
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

// GET ONE USER
$app->get('/users/{id}', function ($request, $response, $args) use ($file) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    if (str_contains($file, $args['id'])) {
        return $response->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->run();