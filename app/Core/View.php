<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $viewPath   = '';
    private static array  $shared     = [];
    private static array  $composers  = [];

    public static function init(string $viewPath): void
    {
        self::$viewPath = rtrim($viewPath, '/');
    }

    public static function render(string $view, array $data = []): string
    {
        $file = self::resolvePath($view);

        if (!file_exists($file)) {
            throw new \RuntimeException("View [{$view}] not found at [{$file}].");
        }

        // Merge shared data
        $data = array_merge(self::$shared, $data);

        // Run composer callbacks
        if (isset(self::$composers[$view])) {
            foreach (self::$composers[$view] as $composer) {
                $composer($data);
            }
        }

        return self::isolatedRender($file, $data);
    }

    private static function isolatedRender(string $file, array $data): string
    {
        // Expose helpers inside views
        $data['auth']    = fn() => Auth::user();
        $data['session'] = Session::class;
        $data['config']  = Config::class;
        $data['csrf']    = Session::csrfToken();

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return ob_get_clean();
    }

    public static function component(string $name, array $data = []): string
    {
        return self::render("partials.{$name}", $data);
    }

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shareMany(array $data): void
    {
        self::$shared = array_merge(self::$shared, $data);
    }

    public static function composer(string $view, callable $callback): void
    {
        self::$composers[$view][] = $callback;
    }

    private static function resolvePath(string $view): string
    {
        $path = self::$viewPath ?: BASE_PATH . '/app/Views';
        return $path . '/' . str_replace('.', '/', $view) . '.php';
    }

    public static function exists(string $view): bool
    {
        return file_exists(self::resolvePath($view));
    }
}
