<?php
/**
 * Router - URL routing logic
 */

class Router {
    private $routes = [];
    private $current_page = '';
    private $current_action = '';
    private $current_params = [];

    public function __construct() {
        $this->parseUrl();
    }

    private function parseUrl() {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Remove base path and query string
        $path = parse_url($request_uri, PHP_URL_PATH);
        $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $script_dir = rtrim($script_dir, '/');
        if ($script_dir !== '' && $script_dir !== '/' && strpos($path, $script_dir) === 0) {
            $path = substr($path, strlen($script_dir));
        }
        $path = trim($path, '/');

        if (empty($path)) {
            $path = 'login';
        }

        $parts = explode('/', $path);
        
        // Extract page and action
        $this->current_page = $parts[0] ?? 'login';
        $this->current_action = $parts[1] ?? 'index';
        $this->current_params = array_slice($parts, 2);
    }

    public function getPage() {
        return $this->current_page;
    }

    public function getAction() {
        return $this->current_action;
    }

    public function getParams() {
        return $this->current_params;
    }

    public function route() {
        $page = $this->current_page;
        $action = $this->current_action;

        // Check authentication for protected routes
        if (!in_array($page, ['login', 'logout', 'setup-admin', 'register'])) {
            if ($page === 'admin' && !isset($_SESSION['admin_id'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            if ($page === 'user' && !isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }

        // Route requests
        switch ($page) {
            case 'login':
                require CONTROLLERS_PATH . '/LoginController.php';
                $controller = new LoginController();
                $controller->$action();
                break;

            case 'setup-admin':
                require CONTROLLERS_PATH . '/LoginController.php';
                $controller = new LoginController();
                $controller->createFirstAdmin();
                break;

            case 'register':
                require CONTROLLERS_PATH . '/RegisterController.php';
                $controller = new RegisterController();
                $controller->$action();
                break;

            case 'logout':
                $logout_type = $_GET['type'] ?? ($_SESSION['user_type'] ?? 'user');
                if ($logout_type === 'admin') {
                    unset($_SESSION['admin_id']);
                } else {
                    unset($_SESSION['user_id']);
                }
                $_SESSION['message'] = 'You have been logged out successfully.';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . BASE_URL . '/login');
                exit;

            case 'admin':
                require CONTROLLERS_PATH . '/AdminController.php';
                $controller = new AdminController();
                // Convert hyphenated actions to camelCase (e.g., 'check-in' -> 'checkIn')
                $actionParts = explode('-', $action);
                $camelCaseAction = array_shift($actionParts);
                foreach ($actionParts as $part) {
                    $camelCaseAction .= ucfirst($part);
                }
                $method = 'action' . ucfirst($camelCaseAction);
                if (method_exists($controller, $method)) {
                    $controller->$method();
                } else {
                    $controller->actionDashboard();
                }
                break;

            case 'user':
                require CONTROLLERS_PATH . '/UserController.php';
                $controller = new UserController();
                // Convert hyphenated actions to camelCase (e.g., 'visitor-log' -> 'visitorLog')
                $actionParts = explode('-', $action);
                $camelCaseAction = array_shift($actionParts);
                foreach ($actionParts as $part) {
                    $camelCaseAction .= ucfirst($part);
                }
                $method = 'action' . ucfirst($camelCaseAction);
                if (method_exists($controller, $method)) {
                    $controller->$method();
                } else {
                    $controller->actionDashboard();
                }
                break;

            default:
                header('Location: ' . BASE_URL . '/login');
                exit;
        }
    }
}
?>
