<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

if (!function_exists('app')) {
    function app(): Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = []): string
    {
        return View::render($view, $data);
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return app()->getRequest();
    }
}

if (!function_exists('response')) {
    function response(): Response
    {
        return app()->getResponse();
    }
}

if (!function_exists('session')) {
    function session(string $key, mixed $default = null): mixed
    {
        return Session::get($key, $default);
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::oldInput($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value): void
    {
        Session::flash($key, $value);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $url = rtrim(Config::get('app.url', ''), '/');
        return $url . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(Config::get('app.url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        foreach (\App\Core\Router::getRoutes() as $route) {
            if ($route->getName() === $name) {
                $uri = $route->getUri();
                foreach ($params as $key => $value) {
                    $uri = str_replace('{' . $key . '}', (string) $value, $uri);
                    $uri = str_replace('{' . $key . '?}', (string) $value, $uri);
                }
                return url($uri);
            }
        }
        throw new \RuntimeException("Named route [{$name}] not found.");
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): never
    {
        header("Location: {$url}", true, $status);
        exit;
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        $file = BASE_PATH . "/app/Views/errors/{$code}.php";
        if (file_exists($file)) {
            require $file;
        } else {
            echo "<h1>HTTP {$code}</h1><p>{$message}</p>";
        }
        exit;
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        exit;
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('now')) {
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('money')) {
    function money(float|int|string $amount, string $currency = 'USD'): string
    {
        $formatter = new NumberFormatter(Config::get('app.locale', 'en_US'), NumberFormatter::CURRENCY);
        return $formatter->formatCurrency((float) $amount, $currency);
    }
}

if (!function_exists('percentage')) {
    function percentage(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals) . '%';
    }
}

if (!function_exists('str_limit')) {
    function str_limit(string $str, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($str) <= $limit) {
            return $str;
        }
        return mb_substr($str, 0, $limit) . $end;
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $time = strtotime($datetime);
        $diff = time() - $time;

        return match (true) {
            $diff < 60      => 'just now',
            $diff < 3600    => floor($diff / 60) . 'm ago',
            $diff < 86400   => floor($diff / 3600) . 'h ago',
            $diff < 604800  => floor($diff / 86400) . 'd ago',
            $diff < 2592000 => floor($diff / 604800) . 'w ago',
            $diff < 31536000 => floor($diff / 2592000) . 'mo ago',
            default         => floor($diff / 31536000) . 'y ago',
        };
    }
}

if (!function_exists('class_names')) {
    function class_names(array $classes): string
    {
        return implode(' ', array_keys(array_filter($classes)));
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return BASE_PATH . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('uuid')) {
    function uuid(): string
    {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
}
