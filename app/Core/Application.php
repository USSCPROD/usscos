<?php

declare(strict_types=1);

namespace App\Core;

class Application
{
    private static ?Application $instance = null;

    private string   $basePath;
    private Request  $request;
    private Response $response;
    private array    $bindings = [];

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->request  = new Request();
        $this->response = new Response();

        View::init($basePath . '/app/Views');

        self::$instance = $this;
    }

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application has not been initialized.');
        }
        return self::$instance;
    }

    public function run(): void
    {
        $response = Router::dispatch($this->request, $this->response);
        $response->send();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \RuntimeException("Cannot resolve [{$abstract}] from the container.");
    }
}
