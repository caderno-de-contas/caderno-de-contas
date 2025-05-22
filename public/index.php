<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$db = new SQLite3('db.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
$db->enableExceptions(true);

$loader = new \Twig\Loader\FilesystemLoader('src/templates');

$twig_options = [];

if ($_ENV['APP_ENV'] != 'local') {
    $twig_options['cache'] = '.twig-cache';
}

$twig = new \Twig\Environment($loader, $twig_options);

echo $twig->render('index.html.twig', []);