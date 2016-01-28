<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 16.10.12
 * Time: 15:57
 * To change this template use File | Settings | File Templates.
 */


function getRoute()
{
    // if rewrite now working
    if (isset($_GET['r'])) {
        return $_GET['r'];
    }
    $requestUrl = $_SERVER['REQUEST_URI'];
    if (($pos = strpos($requestUrl, '?')) !== false) {
        $requestUrl = substr($requestUrl, 0, $pos);
    }

    return $requestUrl;
}

function routeResolve($route)
{
    global $Routes;
    foreach ($Routes as $routeItem) {
        $route_match = $routeItem['match'];


        if (preg_match_all("/\{(\w+)\}/i", $route_match, $match)) {
            $match = $match[1];

            // prepare regular expression
            $route_match = preg_replace("/\{(\w+)}/i", "([a-z0-9%+]+)", $route_match);
            $route_match = '/^' . str_replace('/', '\/', $route_match) . '$/i';


            if (preg_match($route_match, $route, $match_value)) {
                array_shift($match_value);
                $params = array_combine($match, $match_value);

                return array(
                    $routeItem['controller'],
                    $params,
                    (isset($routeItem['permission']) ? $routeItem['permission'] : '')
                );
            }
        } else {
            if (strcasecmp($route_match, $route) == 0) {
                return array(
                    $routeItem['controller'],
                    array(),
                    (isset($routeItem['permission']) ? $routeItem['permission'] : '')
                );
            }
        }
    }
}

function runController($route)
{
    global $twig;

    list($controller, $params, $permission) = routeResolve($route);
    if (is_null($controller)) {
        throw new Exception("Error: Route not found - " . $route);
    }
    list($controllerClassName, $actionID) = explode(':', $controller);
    if (is_authorized()) {
        $main = new $controllerClassName($actionID, $twig, $params);
    } else {
        if ($actionID == 'registration' or $actionID == 'setRegistration') {
            $main = new mainController($actionID, $twig, $params);
        } elseif ($actionID == 'getPrices') {
            $main = new mainController($actionID, $twig, $params);
        } else {
            if (is_authorized() === false and $route != '/' and $route != '/login/') {
                header("Location: /");
                exit();
            }
            $main = new mainController('login', $twig, $params);
        }
    }
}