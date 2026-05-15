<?php
class Router {
    private $routes = [];

    public function addRoute($url, $handler) {
        $this->routes[$url] = $handler;
    }

    public function run() {
    $url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
    $url = $url ?: '';

    foreach ($this->routes as $pattern => $handler) {
        // Ubah {param} menjadi regex group
        $patternRegex = preg_replace('/\{[a-zA-Z_]+\}/', '([a-zA-Z0-9_-]+)', $pattern);
        // Tambahkan delimiter dan anchor
        $patternRegex = '#^' . $patternRegex . '$#';

        // Debug (hapus setelah selesai)
        // echo "Trying pattern: $pattern -> Regex: $patternRegex<br>";

        // Coba cocokkan
        $matchResult = @preg_match($patternRegex, $url, $matches);
        if ($matchResult === false) {
            // Jika regex error, tampilkan pesan
            echo "Regex error on pattern: " . htmlspecialchars($pattern) . "<br>";
            continue;
        }
        if ($matchResult) {
            array_shift($matches);
            list($controllerName, $method) = explode('@', $handler);
            $controllerFile = 'controllers/' . $controllerName . '.php';
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controller = new $controllerName();
                call_user_func_array([$controller, $method], $matches);
            } else {
                echo "Controller not found: $controllerName";
            }
            return;
        }
    }
    echo "404 - Page not found";
}
}