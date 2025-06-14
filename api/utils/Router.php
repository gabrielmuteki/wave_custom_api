<?php
class Router {
    private $routes = [];

    public function addRoute($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function route() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Détecter le sous-dossier d'installation automatiquement
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $baseDir = dirname($scriptName);
        if ($baseDir !== '/' && $baseDir !== '\\') {
            // Retirer le préfixe du chemin d'installation
            if (strpos($path, $baseDir) === 0) {
                $path = substr($path, strlen($baseDir));
                if ($path === '') $path = '/';
            }
        }
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                $this->executeRoute($route, $path);
                return;
            }
        }
        
        Response::error(404, 'Route not found');
    }

    private function matchRoute($route, $method, $path) {
        if ($route['method'] !== $method) {
            return false;
        }
        
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route['path']);
        return preg_match('#^' . $pattern . '$#', $path);
    }

    private function executeRoute($route, $path) {
        $database = new Database();
        $db = $database->connect();
        
        $controllerClass = $route['controller'];
        $controller = new $controllerClass($db);
        
        // Extraire les paramètres de l'URL
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route['path']);
        preg_match('#^' . $pattern . '$#', $path, $matches);
        array_shift($matches); // Supprimer le match complet
        
        call_user_func_array([$controller, $route['action']], $matches);
    }
}
?>
