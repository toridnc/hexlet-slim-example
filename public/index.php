<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];
$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $filteredUsers = array_filter($users, fn($user) => str_contains($user, $term) === true);
    $params = ['users' => $filteredUsers];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    $user['id'] = uniqid();
    $params = [
        'user' => ['id' => $id, 'name' => '', 'email' => '']
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $fileUser = json_decode(file_get_contents('src/users.txt'));
    $fileUser[] = $user;
    file_put_contents('src/users.txt', json_encode($fileUser));
    return $response->withRedirect('/users', 302);

    $params = ['user' => $user];
    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->run();