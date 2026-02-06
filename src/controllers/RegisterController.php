<?php
/**
 * RegisterController - Handle resident registration
 */

class RegisterController {
    private $pdo;

    public function __construct() {
        global $pdo;
        if (!$pdo) {
            require_once SRC_PATH . '/config/db.php';
        }
        $this->pdo = $pdo;
    }

    public function index() {
        $page_title = 'Resident Registration';

        // Fetch blocks for the registration form
        $blocks = $this->pdo->query("SELECT * FROM blocks ORDER BY name ASC")->fetchAll();

        // Fetch floors grouped by block
        $stmt = $this->pdo->query("SELECT f.block_id, f.floor_no FROM floors f ORDER BY f.block_id, f.floor_no ASC");
        $floors_by_block = [];
        while ($row = $stmt->fetch()) {
            $floors_by_block[$row['block_id']][] = $row['floor_no'];
        }

        require VIEWS_PATH . '/layouts/user_layout.php';
    }

    public function getFlats() {
        header('Content-Type: application/json');

        $block_id = $_GET['block_id'] ?? null;
        $floor = $_GET['floor'] ?? null;

        if (!$block_id || !$floor) {
            echo json_encode(['error' => 'Block ID and floor are required']);
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT f.id, f.number,
                       CASE WHEN f.occupied = 1 THEN 'occupied' ELSE 'vacant' END as status
                FROM flats f
                WHERE f.block_id = ? AND f.floor = ?
                ORDER BY f.number ASC
            ");
            $stmt->execute([$block_id, $floor]);
            $flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['flats' => $flats]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to fetch flats']);
        }
        exit;
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/register');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $flat_id = $_POST['flat_id'] ?? null;

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required';
        }

        if (empty($mobile)) {
            $errors[] = 'Mobile number is required';
        } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $errors[] = 'Mobile number must be 10 digits';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }

        if (empty($flat_id)) {
            $errors[] = 'Please select a flat';
        }

        // Check if mobile already exists
        if (!empty($mobile)) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE mobile = ?");
            $stmt->execute([$mobile]);
            if ($stmt->fetch()) {
                $errors[] = 'Mobile number already registered';
            }
        }

        // Check if flat is available
        if (!empty($flat_id)) {
            $stmt = $this->pdo->prepare("SELECT occupied FROM flats WHERE id = ?");
            $stmt->execute([$flat_id]);
            $flat = $stmt->fetch();
            if (!$flat) {
                $errors[] = 'Selected flat does not exist';
            } elseif ($flat['occupied']) {
                $errors[] = 'Selected flat is already occupied';
            }
        }

        if (!empty($errors)) {
            $_SESSION['registration_errors'] = $errors;
            $_SESSION['registration_data'] = $_POST;
            header('Location: ' . BASE_URL . '/register');
            exit;
        }

        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $this->pdo->prepare("INSERT INTO users (name, mobile, password, flat_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $mobile, $hashed_password, $flat_id]);
            $new_user_id = (int)$this->pdo->lastInsertId();

            // Mark flat as occupied
            $stmt = $this->pdo->prepare("UPDATE flats SET occupied = 1 WHERE id = ?");
            $stmt->execute([$flat_id]);

            if ($new_user_id > 0) {
                $message = 'Welcome! Your resident account has been created. You can now log in and view your notifications.';
                $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, FALSE, NOW())");
                $stmt->execute([$new_user_id, $message]);
            }

            // Commit transaction
            $this->pdo->commit();

            $_SESSION['registration_success'] = 'Registration successful! Please login with your mobile number and password.';
            header('Location: ' . BASE_URL . '/login');
            exit;

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            $_SESSION['registration_errors'] = ['Registration failed. Please try again.'];
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
    }
}
?>