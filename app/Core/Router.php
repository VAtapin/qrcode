<?php

declare(strict_types=1);

/**
 * Q to me - moderated short link and QR code service.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 * @link https://bible-media.de/
 * @copyright 2026 Atapin Vladimir / Bible Media
 * @version 1.0.0
 */

namespace App\Core;

/**
 * Minimal HTTP router with named path parameters.
 */
final class Router
{
    private array $routes = [];

    /**
     * Registers a GET route.
     *
     * @param string $path Route path.
     * @param array{class-string, string} $handler Controller class and action.
     */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * Registers a POST route.
     *
     * @param string $path Route path.
     * @param array{class-string, string} $handler Controller class and action.
     */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * Adds a route to the internal route table.
     *
     * @param string $method HTTP method.
     * @param string $path Route path.
     * @param array{class-string, string} $handler Controller class and action.
     */
    private function add(string $method, string $path, array $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * Dispatches the current request to the matching route handler.
     *
     * @param string $method HTTP method.
     * @param string $path Request path.
     */
    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $route['handler'];
                (new $class())->$action(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        view('errors/message', [
            'title' => __('error.link_not_found'),
            'message' => __('error.code_not_found'),
        ]);
    }
}
