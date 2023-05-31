<?php

declare(strict_types=1);

namespace Excalibur\Framework\Route;

use Excalibur\Framework\Route\Router;

class Route
{
    public static function get(string $path, callable|string|array $handle): object
    {
        $result = null;
        if (gettype($handle) === "string") {
           $result = self::getControllerAndActionFromString($path, $handle, "GET");
        } elseif (is_callable($handle)) {
            $result = Router::setRoute("GET", "/api".($path === "/" ? "" : $path), null, $handle);
        } elseif (gettype($handle === "array") && count($handle) === 2) {
            $result = Router::setRoute("GET", "/api".($path === "/" ? "" : $path), $handle[0], $handle[1]);
        } else {
            throw (new \Exception("Error! Route is invalid"));
        }

        return $result;
    }

    public static function post(string $path, callable|string|array $handle): object
    {
        if (gettype($handle) === "string") {
            return self::getControllerAndActionFromString($path, $handle, "POST");
        } elseif (is_callable($handle)) {
            return Router::setRoute("POST", "/api".($path === "/" ? "" : $path), null, $handle);
        } elseif (gettype($handle === "array")) {
            if (count($handle) === 2) {
                return Router::setRoute("POST", "/api".($path === "/" ? "" : $path), $handle[0], $handle[1]);
            }
        } else {
            throw (new \Exception("Error! Route is invalid"));
        }
    }

    public static function put(string $path, callable|string|array $handle): object
    {
        if (gettype($handle) === "string") {
            return self::getControllerAndActionFromString($path, $handle, "PUT");
        } elseif (is_callable($handle)) {
            return Router::setRoute("PUT", "/api".($path === "/" ? "" : $path), null, $handle);
        } elseif (gettype($handle === "array")) {
            if (count($handle) === 2) {
                return Router::setRoute("PUT", "/api".($path === "/" ? "" : $path), $handle[0], $handle[1]);
            }
        } else {
            throw (new \Exception("Error! Route is invalid"));
        }
    }

    public static function patch(string $path, callable|string|array $handle): object
    {
        if (gettype($handle) === "string") {
            return self::getControllerAndActionFromString($path, $handle, "PATCH");
        } elseif (is_callable($handle)) {
            return Router::setRoute("PATCH", "/api".($path === "/" ? "" : $path), null, $handle);
        } elseif (gettype($handle === "array")) {
            if (count($handle) === 2) {
                return Router::setRoute("PATCH", "/api".($path === "/" ? "" : $path), $handle[0], $handle[1]);
            }
        } else {
            throw (new \Exception("Error! Route is invalid"));
        }
    }

    public static function delete(string $path, callable|string|array $handle): object
    {
        if (gettype($handle) === "string") {
            return self::getControllerAndActionFromString($path, $handle, "DELETE");
        } elseif (is_callable($handle)) {
            return Router::setRoute("DELETE", "/api".($path === "/" ? "" : $path), null, $handle);
        } elseif (gettype($handle === "array")) {
            if (count($handle) === 2) {
                return Router::setRoute("DELETE", "/api".($path === "/" ? "" : $path), $handle[0], $handle[1]);
            }
        } else {
            throw (new \Exception("Error! Route is invalid"));
        }
    }

    public static function apiResource(string $path, callable|string|array $handle): object
    {
        if (gettype($handle) === "string") {
            $controller = "Application\\Controllers\\".explode("@", $handle)[0];
            $action = explode("@", $handle)[1];

            return Router::setRoute("", "/api".($path === "/" ? "" : $path), $controller, $action);
        }
    }

    public static function resource(string $path, callable|string|array $handle): object
    {
        if (gettype($handle) === "string") {
            $controller = "Application\\Controllers\\".explode("@", $handle)[0];
            $action = explode("@", $handle)[1];

            return Router::setRoute("", "/api".($path === "/" ? "" : $path), $controller, $action);
        }
    }

    private static function getControllerAndActionFromString(string $path, string $handle, string $method): object
    {
        if ($handle !== "" && str_contains($handle, "@")) {
            $controller = "Application\\Controllers\\".explode("@", $handle)[0];
            $action = explode("@", $handle)[1];
            $handleObj = (object) ["controller" => $controller, "action" => $action];
            return Router::setRoute("$method", "/api".($path === "/" ? "" : $path),
                                $handleObj->controller, $handleObj->action);
        } else {
            return Router::setWebRoute("$method", $path, $handle);
        }
    }
}
