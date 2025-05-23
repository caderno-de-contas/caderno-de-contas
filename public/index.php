<?php

require_once 'vendor/autoload.php';

use DI\Container;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

define('ENV_PRODUCTION', $_ENV['APP_ENV'] == 'production');

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$twig = Twig::create('src/templates', ['cache' => ENV_PRODUCTION]);

$app->add(TwigMiddleware::create($app, $twig));

$db = new SQLite3('db.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
$db->enableExceptions(true);

$app->addRoutingMiddleware();

$logger = new Logger('error');
$logger->pushHandler(new RotatingFileHandler('error.log'));

$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app, $logger) {
    if ($logger) {
        $logger->error($exception->getMessage());
    }

    $payload = [
        'error' => $exception->getMessage(),
    ];

    if (!ENV_PRODUCTION) {
        $payload['exception'] = [
            'in' => $exception->getFile().':'.$exception->getLine(),
            'trace' => $exception->getTrace(),
        ];
    }

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response->withHeader('Content-Type', 'application/json');
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true, $logger);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$app->get('/', function ($request, $response) {
    $view = Twig::fromRequest($request);
    
    return $view->render($response, 'index.html.twig', [
        'name' => 'John',
    ]);
});

$app->run();