<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    /**
     * @var array<string, array<string, callable|array{class-string, string}>>
     */
    private array $routes = [];

    /**
     * @param callable|array{class-string, string} $handler
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * @param callable|array{class-string, string} $handler
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * @param callable|array{class-string, string} $handler
     */
    public function put(string $path, callable|array $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    /**
     * @param callable|array{class-string, string} $handler
     */
    public function delete(string $path, callable|array $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): ?JsonResponse
    {
        $match = $this->match($method, $path);

        if ($match === null) {
            return null;
        }

        [$handler, $params] = $match;

        if (is_array($handler)) {
            [$className, $methodName] = $handler;
            $controller = new $className();

            return $controller->{$methodName}(...$params);
        }

        return $handler(...$params);
    }

    /**
     * @return array{callable|array{class-string, string}, list<string>}|null
     */
    private function match(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $paramNames = [];
            $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];

                return '([^/]+)';
            }, $route);

            if ($pattern === null) {
                continue;
            }

            if (preg_match('#^' . $pattern . '$#', $path, $matches) === 1) {
                array_shift($matches);

                return [$handler, array_map('urldecode', $matches)];
            }
        }

        return null;
    }
}
