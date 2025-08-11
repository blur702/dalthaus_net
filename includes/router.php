<?php
declare(strict_types=1);

class Router {
    private array $routes = [];
    
    public function add(string $pattern, string $handler, array $methods = ['GET']): void {
        $this->routes[] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'methods' => $methods
        ];
    }
    
    public function dispatch(string $uri, string $method): void {
        $route = $this->getRoute($uri, $method);
        if ($route) {
            $_GET['params'] = $route['params'];
            $_GET = array_merge($_GET, $route['params']);
            require_once ROOT_PATH . '/' . $route['handler'];
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }
    
    public function getRoute(string $uri, string $method): ?array {
        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'])) {
                continue;
            }
            
            $pattern = '#^' . preg_replace('/\([^)]+\)/', '([^/]+)', $route['pattern']) . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return [
                    'handler' => $route['handler'],
                    'params' => $matches
                ];
            }
        }
        return null;
    }
}