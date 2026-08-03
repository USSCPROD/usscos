<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Request  $request;
    protected Response $response;

    public function __construct()
    {
        $this->request  = app()->getRequest();
        $this->response = app()->getResponse();
    }

    protected function view(string $view, array $data = []): Response
    {
        $html = View::render($view, $data);
        return $this->response->html($html);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return $this->response->json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return $this->response->redirect($url, $status);
    }

    protected function redirectBack(string $fallback = '/'): Response
    {
        return $this->response->redirectBack($fallback);
    }

    protected function redirectRoute(string $name, array $params = []): Response
    {
        // Resolve named route
        $url = route($name, $params);
        return $this->redirect($url);
    }

    protected function validate(array $rules, ?array $input = null): array
    {
        $input     = $input ?? $this->request->all();
        $validator = new Validator($input, $rules);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flashInput($input);
            // Return validation errors as exception or redirect
            throw new \App\Exceptions\ValidationException($validator->errors());
        }

        return $validator->validated();
    }

    protected function auth(): ?array
    {
        return Auth::user();
    }

    protected function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        if ($this->request->isAjax()) {
            $this->response->json(['error' => $message ?: "HTTP {$code}"], $code)->send();
        } else {
            $viewFile = BASE_PATH . "/app/Views/errors/{$code}.php";
            if (file_exists($viewFile)) {
                require $viewFile;
            } else {
                echo "<h1>HTTP {$code}</h1><p>{$message}</p>";
            }
        }
        exit;
    }

    protected function success(string $message, array $data = []): Response
    {
        if ($this->request->isAjax()) {
            return $this->json(array_merge(['success' => true, 'message' => $message], $data));
        }
        Session::flash('success', $message);
        return $this->redirectBack();
    }

    protected function error(string $message, array $data = []): Response
    {
        if ($this->request->isAjax()) {
            return $this->json(array_merge(['success' => false, 'message' => $message], $data), 422);
        }
        Session::flash('error', $message);
        return $this->redirectBack();
    }
}
