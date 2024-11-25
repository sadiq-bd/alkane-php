<?php

namespace Core;

/**
 * Class Router
 *
 * A simple, flexible routing system.
 *
 * @category  Router
 * @package   Core
 * @author    Sadiq <sadiq.dev.bd@gmail.com>
 * @version   2.2
 */
class Router
{
    private array $routes = [];
    private string $defaultMethod = 'main';
    private string $routeParamPattern = '\{((int|string|str)\:)?([a-z0-9]+)\}';
    private array $parameters = [];
    private $errCallback;

    public function route(string $requestMethod, string $route, string|callable $controller, ?string $method = null): void
    {
        $method = $method ?? $this->extractMethod($controller);
        $this->routes[] = [
            'route' => $route,
            'requestMethod' => strtoupper($requestMethod),
            'controller' => $controller,
            'method' => $method,
        ];
    }

    public function get(string $route, string|callable $controller, ?string $method = null): void
    {
        $this->route('GET', $route, $controller, $method);
    }

    public function post(string $route, string|callable $controller, ?string $method = null): void
    {
        $this->route('POST', $route, $controller, $method);
    }

    public function put(string $route, string|callable $controller, ?string $method = null): void
    {
        $this->route('PUT', $route, $controller, $method);
    }

    public function delete(string $route, string|callable $controller, ?string $method = null): void
    {
        $this->route('DELETE', $route, $controller, $method);
    }

    public function run(?string $basepath = null): void
    {
        $basepath = trim($basepath ?? $this->basepath(), '/');
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $basepath)) {
                $this->dispatch($route);
                return;
            }
        }
        $this->handleDefault();
    }

    public function getParams(): array
    {
        return $this->parameters;
    }

    public function default(string|callable $callback, ?string $method = null): void
    {
        $this->errCallback = is_callable($callback) ? $callback : "{$callback}::{$method ?? $this->defaultMethod}";
    }

    private function basepath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        return $path ? urldecode(trim($path, '/')) : '';
    }

    private function extractMethod(string|callable $controller): string
    {
        if (is_callable($controller)) {
            return $this->defaultMethod;
        }

        [$controller, $method] = array_pad(explode('::', $controller), 2, $this->defaultMethod);
        return str_replace('()', '', $method);
    }

    private function matchRoute(array $route, string $basepath): bool
    {
        $paramInfo = $this->parseRouteParams($route['route']);
        $routePattern = $this->generateRoutePattern($route['route'], $paramInfo);

        if (preg_match($routePattern, $basepath, $matches) && $this->isValidRequestMethod($route['requestMethod'])) {
            $this->parameters = $this->extractParameters($paramInfo, array_slice($matches, 1));
            return true;
        }
        return false;
    }

    private function parseRouteParams(string $route): array
    {
        preg_match_all("/{$this->routeParamPattern}/i", $route, $matches);
        $params = [];
        foreach ($matches[0] as $index => $match) {
            $params[] = [
                'type' => strtolower($matches[1][$index]) ?: null,
                'name' => $matches[2][$index],
                'pattern' => match (strtolower($matches[1][$index] ?? '')) {
                    'int' => '[0-9]+',
                    'string', 'str' => '.+',
                    default => '.+',
                },
            ];
        }
        return $params;
    }

    private function generateRoutePattern(string $route, array $paramInfo): string
    {
        foreach ($paramInfo as $param) {
            $route = str_replace(
                "{{$param['type']}:{$param['name']}}",
                "({$param['pattern']})",
                $route
            );
        }
        return '/^' . str_replace('/', '\/', trim($route, '/')) . '$/i';
    }

    private function extractParameters(array $paramInfo, array $matches): array
    {
        $parameters = [];
        foreach ($matches as $index => $match) {
            $parameters[$paramInfo[$index]['name']] = $match;
        }
        return $parameters;
    }

    private function isValidRequestMethod(string $method): bool
    {
        return strtoupper($method) === strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
    }

    private function dispatch(array $route): void
    {
        $controller = $route['controller'];
        $method = $route['method'];
        $params = [$this->getParams()];

        if (is_callable($controller)) {
            echo (string)call_user_func_array($controller, $params);
        } elseif (class_exists($controller)) {
            $instance = new $controller();
            echo (string)call_user_func_array([$instance, $method], $params);
        }
    }

    private function handleDefault(): void
    {
        if (is_callable($this->errCallback)) {
            echo (string)call_user_func($this->errCallback, [
                'basepath' => $this->basepath(),
                'requestMethod' => $this->getRequestMethod(),
            ]);
        } elseif (is_string($this->errCallback)) {
            [$controller, $method] = explode('::', $this->errCallback);
            if (class_exists($controller)) {
                $instance = new $controller();
                $instance->$method();
            }
        }
    }

    private function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
}
