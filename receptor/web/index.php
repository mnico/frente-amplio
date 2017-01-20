<?php

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;
use Respect\Validation\Validator;
use Tifon\Rut\RutUtil;

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


$app->get('/test', function(Request $request) use ($app) {


    $response = new JsonResponse();
    $response->setData(array(
        'data' => 123
    ));

    return $response;
});

/**
 * Inserts a pageview and click into the database
 */
$app->post('/insertar', function (Request $request) use ($app) {

    $nombre   = $request->get('nombres');
    $apellido = $request->get('apellidos');
    $email    = $request->get('email');
    $rut      = $request->get('rut');
    $ip       = $request->getClientIp();

    $isValid = true;
    $errors = array();

    if (!Validator::email()->validate($email)) {
        $errors['email'] = 'El email es inválido.';
        $isValid = false;
    }

    if (!RutUtil::validateRut($rut)) {
        $errors['rut'] = 'El rut es inválido.';
        $isValid = false;
    }

    $response = new JsonResponse();

    if ($isValid) {
        $sql = "
          INSERT INTO registro_usuario(nombres, apellidos, email, rut, ip)
          VALUES (?, ?, ?, ?)
        ";

        $app['db']->executeUpdate($sql, array($nombre, $apellido, $email, $rut, $ip));
        $response->setData(array(
            'status' => true,
        ));
    }
    else {
        $response->setData(array(
            'status' => false,
            'errors' => $errors,
        ));
    }


    return $response;
});



$app->run();