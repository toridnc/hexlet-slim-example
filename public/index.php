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

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use App\Validator;

session_start();

// Connect to PHPRenderer templates
$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

// Connect to flash messages
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

// Route
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
// Method override support
$app->add(MethodOverrideMiddleware::class); 
$router = $app->getRouteCollector()->getRouteParser();

// WELCOME
$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('welcome');

// Form for login
$app->get('/login', function ($request, $response) {
    // Catch a message
    $flash = $this->get('flash')->getMessages();

    $params = [
        'currentUser' => $_SESSION ?? null,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, 'users/authentication.phtml', $params);
})->setName('login');

// LOGIN
$app->post('/login', function ($request, $response) use ($router) {
    $data = $request->getParsedBodyParam('user');
    $allUsers = json_decode($request->getCookieParam('user', json_encode([])), true);

    // Get user data on 'email'
    foreach ($allUsers as $user) {
        if ($user['email'] === $data['email']) {
            $_SESSION = $user;
            return $response->withRedirect($router->urlFor('users'));
        }
    }
    $this->get('flash')->addMessage('warrning', 'Wrong email');
    return $response->withRedirect($router->urlFor('login'));
});

// DELETE SESSION
$app->delete('/login', function ($request, $response) use ($router) {
    $_SESSION = [];
    session_destroy();
    return $response->withRedirect($router->urlFor('login'));
});

// Form for create new user
$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '']
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('newUserForm');

// CREATE NEW USER
$app->post('/users', function ($request, $response) use ($router) {
    // Extract data from the form.
    $user = $request->getParsedBodyParam('user');

    $validator = new App\Validator();
    // Check the correctness of data.
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $allUsers = json_decode($request->getCookieParam('user', json_encode([])), true);
        // Create user unique 'id' and to save users.
        $user['id'] = uniqid();
        $allUsers[] = $user;
        $allUsers = json_encode($allUsers);
        // If the data is correct, save, add a flush and redirect.
        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withHeader('Set-Cookie', "user={$allUsers};Path=/")->withRedirect($router->urlFor('users'), 302);
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
$app->get('/users', function ($request, $response) {
    // Catch a message.
    $messages = $this->get('flash')->getMessages();

    $allUsers = json_decode($request->getCookieParam('user', json_encode([])), true);
    // Extract the entered data.
    $term = $request->getQueryParam('term');
    // Filter users on request.
    $filteredUsers = array_filter($allUsers, fn($user) => str_contains(strtolower($user['name']), strtolower($term)) === true);

    var_dump($allUsers);

    $params = [
        'flash' => $messages,
        'users' => $filteredUsers
    ];
    return $this->get('renderer')->render($response, 'users/users.phtml', $params);

})->setName('users');

// GET ONE USER
$app->get('/users/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $allUsers = json_decode($request->getCookieParam('user', json_encode([])), true);

    // Get user data on 'id'
    $user;
    foreach ($allUsers as $element) {
        if ($element['id'] === $id) {
            $user = $element;
            break;
        }
        continue;
    }

    // 404 error if 'id' does not exist and drawing template if there is.
    if (empty($user)) {
        return $response->write('Page not found')->withStatus(404);
    }

    // Catch a message
    $messages = $this->get('flash')->getMessages();

    $params = [
        'user' => $user,
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);

})->setName('user');

// Form for edit user
$app->get('/users/{id}/edit', function ($request, $response, $args) {
    $id = $args['id'];
    $allUsers = json_decode($request->getCookieParam('user', json_encode([])), true);

    // Get user data on 'id'
    $user;
    foreach ($allUsers as $element) {
        if ($element['id'] === $id) {
            $user = $element;
            break;
        }
        continue;
    }
    var_dump($user);

    $params = [
        'user' => $user
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editForm');

// EDIT USER
$app->patch('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];

    // Extract new data from the form
    $editUser = $request->getParsedBodyParam('user');
    // Extract all users
    $allUsers = json_decode($request->getCookieParam('user', json_encode([])), true);

    // $newUsers = [];
    // foreach ($allUsers as $user) {
    //     if ($user['id'] === $id) {
    //         continue;
    //     }
    //     $newUsers[] = $user;
    // }

    // Get user data on 'id'
    $user;
    foreach ($allUsers as $element) {
        if ($element['id'] === $id) {
            $user = $element;
            break;
        }
        continue;
    }

    $validator = new Validator();
    $errors = $validator->validate($editUser);

    // If the data is correct, save, add a flush and redirect
    if (count($errors) === 0) {
        $user['name'] = $editUser['name'];
        $user['email'] = $editUser['email'];
        $allUsers[$user[$id]] = $user;
        // $newUsers[] = $user;
        // $users = json_encode($newUsers);
        // $user = json_encode($user); // + $user Заменяет все поля на первую букву // + $allUsers Всё стирает
        // $allUsers = json_encode($user); // + $user Всё стирает // + $allUsers Заменяет все поля на первую букву 
        $this->get('flash')->addMessage('success', 'User was update successfully');
        //return $response->withHeader('Set-Cookie', "user={$user};Path=/")->withRedirect($router->urlFor('users'), 302);
        return $response->withRedirect($router->urlFor('users'), 302);
    }

    // If the new data is uncorrect
    $params = ['user' => $user, 'errors' => $errors];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);

})->setName('edit');
// END

// DELETE USER
$app->delete('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $allUsers = json_decode($request->getCookieParam('user', json_encode([])), true);

    // Get user data on 'id'
    $newUsers = [];
    foreach ($allUsers as $user) {
        if ($user['id'] === $id) {
            continue;
        }
        $newUsers[] = $user;
    }
    $users = json_encode($newUsers);

    // Delete session
    $_SESSION = [];
    session_destroy();

    return $response->withHeader('Set-Cookie', "user={$users};Path=/")->withRedirect($router->urlFor('users'), 302);
})->setName('delete');

$app->run();