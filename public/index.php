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

// FILE TO SAVE USERS
$file = 'src/users.json';

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

// POST NEW USER
$app->post('/users', function ($request, $response) use ($file, $router) {
    $user = $request->getParsedBodyParam('user');
    $fileUser = json_decode(file_get_contents($file));
    // Create user unique id and add user to file to save users.
    $user['id'] = uniqid();
    $fileUser[] = $user;
    file_put_contents($file, json_encode($fileUser));

    $this->get('flash')->addMessage('success', 'User was added successfully');
    return $response->withRedirect($router->urlFor('users'), 302);

    $params = ['user' => $user];
    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);

})->setName('postNewUser');

// GET ALL USERS AND FILTER
$app->get('/users', function ($request, $response) use ($file) {
    $allUsers = json_decode(file_get_contents($file));
    // Get a column with names from a set of records.
    $names = array_column($allUsers, "name");
    $term = $request->getQueryParam('term');
    // Filter users on request.
    $filteredUsers = array_filter($names, fn($name) => str_contains(strtolower($name), strtolower($term)) === true);
    $messages = $this->get('flash')->getMessages();

    $params = [
        'users' => $filteredUsers,
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);

})->setName('users');

// GET ONE USER
$app->get('/users/{id}', function ($request, $response, $args) use ($file) {
    $id = $args['id'];
    $allUsers = json_decode(file_get_contents($file));
    // Get column with names, and as keys of the return array we use values from column "id".
    $ids = array_column($allUsers, "name", "id");

    // 404 error if id does not exist and drawing template if there is.
    if (!array_key_exists($id, $ids)) {
        return $response->write('Page not found')->withStatus(404);
    }

    $params = [
        'id' => $id,
        'username' => $ids[$id]
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);

})->setName('user');

$app->run();