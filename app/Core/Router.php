<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private static array   $routes     = [];
    private static array   $middleware = [];
    private static string  $prefix     = '';
    private static array   $groupMiddleware = [];

    public static function get(string $uri, array|callable $action): Route
    {
        return self::addRoute('GET', $uri, $action);
    }

    public static function post(string $uri, array|callable $action): Route
    {
        return self::addRoute('POST', $uri, $action);
    }

    public static function put(string $uri, array|callable $action): Route
    {
        return self::addRoute('PUT', $uri, $action);
    }

    public static function patch(string $uri, array|callable $action): Route
    {
        return self::addRoute('PATCH', $uri, $action);
    }

    public static function delete(string $uri, array|callable $action): Route
    {
        return self::addRoute('DELETE', $uri, $action);
    }

    public static function any(string $uri, array|callable $action): Route
    {
        return self::addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $action);
    }

    public static function group(array $attributes, callable $callback): void
    {
        $previousPrefix     = self::$prefix;
        $previousMiddleware = self::$groupMiddleware;

        if (isset($attributes['prefix'])) {
            self::$prefix .= '/' . trim($attributes['prefix'], '/');
        }

        if (isset($attributes['middleware'])) {
            $mw = (array) $attributes['middleware'];
            self::$groupMiddleware = array_merge(self::$groupMiddleware, $mw);
        }

        $callback();

        self::$prefix           = $previousPrefix;
        self::$groupMiddleware  = $previousMiddleware;
    }

    private static function addRoute(array|string $methods, string $uri, array|callable $action): Route
    {
        $uri  = self::$prefix . '/' . ltrim($uri, '/');
        $uri  = rtrim($uri, '/') ?: '/';

        $route = new Route(
            (array) $methods,
            $uri,
            $action,
            self::$groupMiddleware
        );

        self::$routes[] = $route;
        return $route;
    }

    public static function dispatch(Request $request, Response $response): Response
    {
        $method = $request->method();
        $uri    = $request->uri();

        // Normalize URI: remove base path if running in a subdirectory
        $basePath = parse_url(Config::get('app.url'), PHP_URL_PATH) ?? '';
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . ltrim($uri, '/');

        foreach (self::$routes as $route) {
            if ($route->matches($method, $uri)) {
                $params = $route->extractParams($uri);
                $request->setRouteParams($params);

                return self::runMiddleware(
                    $route->getMiddleware(),
                    $request,
                    $response,
                    fn() => self::callAction($route->getAction(), $request, $response, $params)
                );
            }
        }

        // Check if URI matches but method doesn't (405)
        foreach (self::$routes as $route) {
            if ($route->matchesUri($uri)) {
                return self::methodNotAllowed($response);
            }
        }

        return self::notFound($response);
    }

    private static function callAction(array|callable $action, Request $request, Response $response, array $params): Response
    {
        if (is_callable($action)) {
            $result = $action($request, $response, ...$params);
            return self::normalizeResult($result, $response);
        }

        [$controllerClass, $method] = $action;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller [{$controllerClass}] not found.");
        }

        $controller = new $controllerClass();
        $result     = $controller->$method($request, $response, ...$params);

        return self::normalizeResult($result, $response);
    }

    private static function normalizeResult(mixed $result, Response $response): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_string($result)) {
            return $response->html($result);
        }
        if (is_array($result)) {
            return $response->json($result);
        }
        return $response;
    }

    private static function runMiddleware(array $middleware, Request $request, Response $response, callable $final): Response
    {
        if (empty($middleware)) {
            return $final();
        }

        $stack = array_reverse($middleware);
        $next  = $final;

        foreach ($stack as $mw) {
            $instance = self::resolveMiddleware($mw);
            $nextFn   = $next;
            $next     = fn() => $instance->handle($request, $response, $nextFn);
        }

        return $next();
    }

    private static function resolveMiddleware(string $name): object
    {
        $map = [
            'auth'     => \App\Middleware\AuthMiddleware::class,
            'guest'    => \App\Middleware\GuestMiddleware::class,
            'csrf'     => \App\Middleware\CsrfMiddleware::class,
            'throttle' => \App\Middleware\ThrottleMiddleware::class,
            'apikey'   => \App\Middleware\ApiKeyMiddleware::class,
        ];

        $class = $map[$name] ?? $name;

        if (!class_exists($class)) {
            throw new \RuntimeException("Middleware [{$class}] not found.");
        }

        return new $class();
    }

    private static function notFound(Response $response): Response
    {
        http_response_code(404);
        if (file_exists(BASE_PATH . '/app/Views/errors/404.php')) {
            ob_start();
            require BASE_PATH . '/app/Views/errors/404.php';
            return $response->html(ob_get_clean(), 404);
        }
        return $response->html('<h1>404 Not Found</h1>', 404);
    }

    private static function methodNotAllowed(Response $response): Response
    {
        return $response->html('<h1>405 Method Not Allowed</h1>', 405);
    }

    public static function getRoutes(): array
    {
        return self::$routes;
    }
}
