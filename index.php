<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 09.10.12
 * Time: 9:58
 * To change this template use File | Settings | File Templates.
 * mainController управляет шагами и выводит нужный шаблон
 */
session_start();
#Включаем шаблонизатор
require_once 'twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

#Подключаем файлы конфига
require_once 'config/routes.php';
require_once 'config/mysql.php';
//
//#Подключаем контроллеры
require_once 'includes/routesController.php';
require_once 'includes/paypal.php';
require_once 'includes/onpay.php';
require_once 'mainBundle/mainController.php';
require_once 'mainBundle/ajaxController.php';
require_once 'mainBundle/apiController.php';
require_once 'mainBundle/userController.php';
require_once 'mainBundle/groupController.php';
require_once 'mainBundle/logsController.php';
require_once 'mainBundle/firmwareController.php';

#и функции
$loader = new Twig_Loader_Filesystem('templates/');
$twig = new Twig_Environment($loader, array(
        'cache' => 'cache/',
        'debug' => true
    ));

function is_authorized() {
    if (!empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    } else {

        return false;
    }
}

$route = getRoute();
runController($route);

