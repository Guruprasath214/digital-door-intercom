<?php
/**
 * LoginController - Handles authentication with database
 */

class LoginController {
    private $pdo;

    public function __construct() {
        require_once SRC_PATH . '/config/db.php';
        global $pdo;
        $this->pdo = $pdo;
    }

    public function index() {
        $allow_admin_setup = $this->canSetupAdmin();
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['admin_login'])) {
                $this->handleAdminLogin();
            } elseif (isset($_POST['user_login'])) {
                $this->handleUserLogin();
            }
        }

        $active_tab = $_GET['tab'] ?? 'admin';
        require VIEWS_PATH . '/pages/login.php';
    }

    public function createFirstAdmin() {
        if (!$this->canSetupAdmin()) {
            $_SESSION['message'] = 'Admin already exists. Please log in.';
            $_SESSION['message_type'] = 'error';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_admin'])) {
            $email = trim($_POST['admin_email'] ?? '');
            $password = $_POST['admin_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (empty($email) || empty($password) || empty($confirm)) {
                $_SESSION['message'] = 'Please fill in all required fields';
                $_SESSION['message_type'] = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['message'] = 'Please enter a valid email address';
                $_SESSION['message_type'] = 'error';
            } elseif ($password !== $confirm) {
                $_SESSION['message'] = 'Passwords do not match';
                $_SESSION['message_type'] = 'error';
            } elseif (strlen($password) < 6) {
                $_SESSION['message'] = 'Password must be at least 6 characters';
                $_SESSION['message_type'] = 'error';
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO admins (email, password) VALUES (?, ?)');
                $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                $_SESSION['message'] = 'Admin created successfully. Please log in.';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }

        require VIEWS_PATH . '/pages/setup_admin.php';
    }

    private function canSetupAdmin() {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM admins');
        $count = (int)$stmt->fetchColumn();
        return $count === 0;
    }

    private function handleAdminLogin() {
        $email = $_POST['admin_email'] ?? '';
        $password = $_POST['admin_password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['message'] = 'Please enter email and password';
            $_SESSION['message_type'] = 'error';
            return;
        }

        // Query database for admin
        $stmt = $this->pdo->prepare("SELECT id, password FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_name'] = 'Admin';
            $_SESSION['message'] = 'Admin login successful!';
            $_SESSION['message_type'] = 'success';
            header('Location: ' . BASE_URL . '/admin/dashboard');
            exit;
        } else {
            $_SESSION['message'] = 'Invalid admin credentials';
            $_SESSION['message_type'] = 'error';
        }
    }

    private function handleUserLogin() {
        $mobile = $_POST['user_mobile'] ?? '';
        $password = $_POST['user_password'] ?? '';

        if (empty($mobile) || empty($password)) {
            $_SESSION['message'] = 'Please enter mobile and password';
            $_SESSION['message_type'] = 'error';
            return;
        }

        // Query database for user
        $stmt = $this->pdo->prepare("SELECT u.id, u.name, u.mobile, u.password, f.id as flat_id, f.number as flat_number, b.name as block_name 
                                     FROM users u 
                                     LEFT JOIN flats f ON u.flat_id = f.id 
                                     LEFT JOIN blocks b ON f.block_id = b.id 
                                     WHERE u.mobile = ?");
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = 'user';
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_mobile'] = $user['mobile'];
            $_SESSION['flat_id'] = $user['flat_id'];
            $_SESSION['flat_number'] = $user['flat_number'];
            $_SESSION['block_name'] = $user['block_name'];
            $_SESSION['message'] = 'Resident login successful!';
            $_SESSION['message_type'] = 'success';
            header('Location: ' . BASE_URL . '/user/dashboard');
            exit;
        } else {
            $_SESSION['message'] = 'Invalid resident credentials';
            $_SESSION['message_type'] = 'error';
        }
    }
}
?>
