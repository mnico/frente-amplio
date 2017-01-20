<?php

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

/**
 * Get db configuration
 */
$yamlParser = new Parser();
$path = __DIR__ . '/../config';
$dbConfig = $yamlParser->parse(file_get_contents($path . '/db.yml'));


/* Register Doctrine*/
$app->register(new Silex\Provider\DoctrineServiceProvider(), $dbConfig);


/**
 * Inserts a pageview and click into the database
 */
$app->post('/insertar', function (Request $request) use ($app) {

    $nombre   = $request->get('nombre');
    $apellido = $request->get('apellido');
    $email    = $request->get('email');
    $ip       = $request->getClientIp();

    $sql = "
      INSERT INTO registro_usuario(nombre, apellido, email, ip)
      VALUES (?, ?, ?, ?)
    ";

    $app['db']->executeUpdate($sql, array($nombre, $apellido, $email, $ip));

    $response = new Response();
   // $session   = $request->get('session');
//    $response->setContent(json_encode($session));
    $response->setStatusCode(Response::HTTP_OK);
    $response->headers->set('Content-Type', 'text/html');
    $response->headers->set('access-control-allow-origin', '*');

    // prints the HTTP headers followed by the content

    return $response;
});



$app->run();