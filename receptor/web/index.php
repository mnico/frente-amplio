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


$app->get('/count', function(Request $request) use ($app) {
    $sql = "SELECT COUNT(id) as countRegisters FROM registro_usuario";
    $results = $app['db']->fetchAssoc($sql);
    $response = new JsonResponse();
    $response->setData(array(
        'count' => $results['countRegisters'],
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

    // Validamos los campos.
    if (!Validator::email()->validate($email)) {
        $errors['email'] = 'El email es inválido.';
        $isValid = false;
    }

    if (!RutUtil::validateRut($rut)) {
        $errors['rut'] = 'El rut es inválido.';
        $isValid = false;
    }
    else {
        $rut = RutUtil::formatterRut($rut);
    }

    if (!Validator::notBlank()->validate($nombre)) {
        $errors['nombres'] = 'El nombre es requerido.';
        $isValid = false;
    }

    if (!Validator::notBlank()->validate($apellido)) {
        $errors['apellidos'] = 'El apellido es requerido.';
        $isValid = false;
    }

    // Revisa si ya existe el rut.
    $sql = "SELECT * FROM registro_usuario WHERE rut = ? OR email = ?";
    $results = $app['db']->fetchAll($sql, array($rut, $email));
    foreach($results as $result) {
        if ($result['email'] == $email) {
            $errors['email'] = 'El correo ya existe.';
            $isValid = false;
        }
        if ($result['rut'] == $rut) {
            $errors['rut'] = 'El rut ya existe.';
            $isValid = false;
        }
    }


    $response = new JsonResponse();

    if ($isValid) {
        $sql = "
          INSERT INTO registro_usuario(nombres, apellidos, email, rut, ip, created)
          VALUES (?, ?, ?, ?, ?, ?)
        ";

        try {
            $app['db']->executeUpdate($sql, array($nombre, $apellido, $email, $rut, $ip, time()));
            $response->setData(array(
                'status' => true,
            ));

            return $response;
        }
        catch (\Exception $exp) {
            $errors['db'] = 'Problemas en la base de datos.' . $exp->getMessage();
        }
    }

    $response->setData(array(
        'status' => false,
        'errors' => $errors,
    ));


    return $response;
});



$app->run();