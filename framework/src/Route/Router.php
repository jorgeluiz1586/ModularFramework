<?php

declare(strict_types=1);

namespace Excalibur\Framework\Route;

use Infrastructure\Helpers\View;

class Router
{
    private static $routes = [];
    private static $webRoutes = [];

    public static function searchRoute(string $method, object $handle)
    {
        $routeObject = (object) self::getParamsInRoute("api", $method, $handle->route);

        return (object) [
            "route" => $routeObject->route[0],
            "params" => (object) [...$routeObject->params, ...$handle->params],
        ];
    }

    
    public static function searchWebRoute(string $method, object $handle, object $options)
    {
        $routeObject = (object) self::getParamsInRoute("web", $method, $handle->route);
        
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        if (isset($_SESSION["token"]) && (strlen($_SESSION["token"]) > 0)) {
            $handle->params['token'] = $_SESSION["token"];
            $handle->params['userId'] = $_SESSION["user"]->id;
            $handle->params['userUuid'] = $_SESSION["user"]->uuid;
            $handle->params['userName'] = $_SESSION["user"]->first_name;
            $handle->params['userLastName'] = $_SESSION["user"]->last_name;
            $handle->params['userEmail'] = $_SESSION["user"]->email;
        }
        if (count($routeObject->route) <= 0) {
            header("HTTP/1.1 404 Not Found");
            return print_r("Page do not found");
        } else {
            if ($options->isBot || !$options->isSPA) {
                header("HTTP/1.1 200 OK");
               
                header("Content-Type: text/html");
                $result = [];
                View::setView([...$routeObject->route][0]['view']);
                View::$isBot = $options->isBot ? "true" : "false";
                View::$params = (object) [...$routeObject->params, ...$handle->params];
                $result = View::render();
                return print_r(implode("", $result));
            }
            header("HTTP/1.1 200 OK");
            header("Content-Type: text/html");
            $pages = [];
            foreach (self::$webRoutes as $item) {
                View::setView(explode("/", $item['view'])[1]);
                View::$isBot = $options->isBot ? "true" : "false";

                View::$params = (object) [...$routeObject->params, ...$handle->params];
                $result = View::render();
                $pages[] = [
                    "path" => $item['uri'],
                    "page" => explode("<!---->", explode("<div id=\"app\">", implode("", $result))[1])[0],
                ];
            }
            return print_r(json_encode($pages));
        }
    }

    private static function getParamsInRoute(string $type, string $method, string $path)
    {
        $params = [];
        return [
            "route" => [
                ...array_filter($type === "web" ? self::$webRoutes : self::$routes,
                function ($route) use ($method, $path, &$params) {
                    return self::checkParamsInRoute(["route" => $route, "path" => $path], $method, $params);
                })
            ],
            "params" => &$params
        ];
    }


    private static function checkParamsInRoute(array $routeAndPath, string $method, array &$params)
    {
        $route = $routeAndPath['route'];
        $path = $routeAndPath['path'];
        if ($route['method'] === $method) {
            $routePathArray = explode("/", $route['uri']);
            $requestPathArray = explode("/", $path);
            $pathArray = [];
            if (count($routePathArray) === count($requestPathArray)) {
                foreach ($routePathArray as $routeIndex => $routePathSlice) {
                    if (
                        strpos($routePathSlice, "{") === 0 &&
                        strpos($routePathSlice, "}") === (strlen($routePathSlice) - 1)
                    ) {
                        $params["".str_replace("}", "", str_replace("{", "", $routePathSlice)).""]
                            = $requestPathArray[$routeIndex];
                        $pathArray[] = true;
                    } else {
                        $pathArray[] = $requestPathArray[$routeIndex] === $routePathSlice;
                    }
                }
                if (!in_array(false, $pathArray)) {
                    return $route;
                }
            }
        }
    }


    public static function getDefaultFrontendFiles(string $path, array $pathArray)
    {
        header("HTTP/1.1 200 OK");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        if (str_contains($path, "/scripts")) {
            return self::getScript($pathArray);
        }

        if (str_contains($path, "/css")) {
            return self::getCSS($pathArray);
        }

        if (str_contains($path, "/favicon")) {
            return self::getFavicon();
        }

    }


    private static function getScript(array $pathArray)
    {
        header("Content-Type: application/javascript");
        return print_r(file_get_contents("./src/WebUI/Assets/Scripts/".$pathArray[count($pathArray) - 1]));
    }


    private static function getCSS(array $pathArray)
    {
        header("Content-Type: text/css");
        return print_r(file_get_contents("./src/WebUI/Assets/Styles/CSS/".$pathArray[count($pathArray) - 1]));
    }


    private static function getFavicon()
    {
        header("Content-Type: image/x-icon");
        header("Content-Disposition:attachment; filename=\"favicon.icon\"");
        return readfile("./src/WebUI/Assets/Icons/favicon.ico");
    }


    public static function setRoute(string $method, string $path, string|null $controller, string|callable $action)
    {
        $controllerObject = null;
        if ($controller !== null) {
            $service = "Application\\Services\\".
                        str_replace("Controller", "Service", array_reverse(explode("\\", $controller))[0]);
            $entity = "Domain\\Entities\\".str_replace("Controller", "", array_reverse(explode("\\", $controller))[0]);
            $controllerObject = new $controller(new $service(new $entity()));
        }

        array_push(self::$routes,
            ["method" => $method, "uri" => $path, "controller" => $controllerObject,
                                    "action" => $action, "middleware" => ""]);
    }


    public static function setWebRoute(string $method, string $path, string $viewPath)
    {
        $view = "";
        if (str_contains($path, '_')) {
            $view = implode("", array_map(function ($item)
            {
                return ucfirst(strtolower($item));
            }, explode("_", $path)));
        } elseif (str_contains($path, '-')) {
            $view = implode("", array_map(function ($item)
            {
                return ucfirst(strtolower($item));
            }, explode("-", $path)));
        } else {
            if ($path === "/") {
                $view = "/".ucfirst(strtolower("Home"));
            } else {
                $view = "/".ucfirst(implode("", (explode("/", strtolower($path)))));
            }
        }

        if ($viewPath !== "" && explode("/", $viewPath)[1] === "Views") {
            array_push(self::$webRoutes, ["uri" => $path, "method" => $method,
            "view" => "/".array_reverse(explode("/", $viewPath))[0]]);
        } else {
            array_push(self::$webRoutes, ["uri" => $path, "method" => $method, "view" => $view."View"]);
        }
    }
}
