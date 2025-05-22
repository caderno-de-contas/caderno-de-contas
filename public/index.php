<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

define('ENV_PRODUCTION', $_ENV['APP_ENV'] == 'production');

$builder = new \DI\ContainerBuilder();
if (ENV_PRODUCTION) {
    $builder->enableCompilation('.tmp');
    $builder->writeProxiesToFile(true, '.tmp/proxies');
}
$container = $builder->build();

$db = new SQLite3('db.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
$db->enableExceptions(true);

$loader = new \Twig\Loader\FilesystemLoader('src/templates');
$twig_options = [];
if (ENV_PRODUCTION) {
    $twig_options['cache'] = '.twig-cache';
}
$twig = new \Twig\Environment($loader, $twig_options);
echo $twig->render('index.html.twig', []);