<?php

/**
 * Hexlet slim example
 *
 * PHP version 7.4
 *
 * @category hexlet-slim-example
 * @package  hexlet-slim-example
 * @author   toridnc <riadev@inbox.ru>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     https://github.com/toridnc/hexlet-slim-example
 */

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use App\Validator;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

// FILE TO SAVE USERS
$file = 'vendor/users.json';

// WELCOME
$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('welcome');

// BEGIN (create new user)
// Form for post new user
$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'id' => $id]
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('createNewUser');

// Post new user
$app->post('/users', function ($request, $response) use ($file, $router) {
    // Extract data from the form.
    $user = $request->getParsedBodyParam('user');

    $validator = new App\Validator();
    // Check the correctness of data.
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $fileUser = json_decode(file_get_contents($file));
        // Create user unique id and add user to file to save users.
        $user['id'] = uniqid();
        $fileUser[] = $user;
        file_put_contents($file, json_encode($fileUser));
        // If the data is correct, save, add a flush and redirect.
        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withRedirect($router->urlFor('users'), 302);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    // If there are errors, we set the response code to 422 and render the form with errors.
    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);

})->setName('postNewUser');
// END

// GET ALL USERS AND FILTER
$app->get('/users', function ($request, $response) use ($file) {
    $allUsers = json_decode(file_get_contents($file));
    // Get a column with names from a set of records.
    $names = array_column($allUsers, "name");
    // Extract the entered data.
    $term = $request->getQueryParam('term');
    // Filter users on request.
    $filteredUsers = array_filter($names, fn($name) => str_contains(strtolower($name), strtolower($term)) === true);
    // Add a message that the user was added successfully.
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
    $emails = array_column($allUsers, "email", "id");

    // 404 error if id does not exist and drawing template if there is.
    if (!array_key_exists($id, $ids)) {
        return $response->write('Page not found')->withStatus(404);
    }

    $params = [
        'id' => $id,
        'username' => $ids[$id],
        'email' => $emails[$id]
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);

})->setName('user');

// BEGIN (edit user)
// Form for edit user
$app->get('/users/{id}/edit', function ($request, $response, $args) use ($file) {
    $id = $args['id'];
    $allUsers = json_decode(file_get_contents($file));
    // Get column with names, and as keys of the return array we use values from column "id".
    $ids = array_column($allUsers, "name", "id");
    $emails = array_column($allUsers, "email", "id");
    $params = [
        'id' => $id,
        'username' => $ids[$id],
        'email' => $emails[$id],
        'user' => ['name' => '', 'email' => '', 'id' => $id]
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');
// END

$app->run();