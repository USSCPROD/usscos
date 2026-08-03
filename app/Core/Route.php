<?php

declare(strict_types=1);

namespace App\Core;

class Route
{
    private array   $methods;
    private string  $uri;
    private mixed   $action;
    private array   $middleware;
    private string  $pattern;
    private array   $paramNames = [];
    private ?string $name       = null;

    public function __construct(array $methods, string $uri, array|callable $action, array $middleware = [])
    {
        $this->methods    = $methods;
        $this->uri        = $uri;
        $this->action     = $action;
        $this->middleware = $middleware;
        $this->pattern    = $this->buildPattern($uri);
    }

    private function buildPattern(string $uri): string
    {
        $pattern = preg_replace_callback('/\{(\w+)(\?)?\}/', function (array $m) {
            $this->paramNames[] = $m[1];
            return isset($m[2]) ? '([^/]*)' : '([^/]+)';
        }, $uri);

        return '#^' . $pattern . '$#';
    }

    public function matches(string $method, string $uri): bool
    {
        return in_array($method, $this->methods) && $this->matchesUri($uri);
    }

    public function matchesUri(string $uri): bool
    {
        return (bool) preg_match($this->pattern, $uri);
    }

    public function extractParams(string $uri): array
    {
        preg_match($this->pattern, $uri, $matches);
        array_shift($matches);

        $params = [];
        foreach ($this->paramNames as $i => $name) {
            $params[$name] = $matches[$i] ?? null;
        }

        return $params;
    }

    public function middleware(string|array $middleware): static
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getAction(): array|callable  { return $this->action; }
    public function getMiddleware(): array        { return $this->middleware; }
    public function getMethods(): array           { return $this->methods; }
    public function getUri(): string              { return $this->uri; }
    public function getName(): ?string            { return $this->name; }
}
