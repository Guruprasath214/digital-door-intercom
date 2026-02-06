<?php
/**
 * UserController - Resident panel functionality with database integration
 */

class UserController {
    private $pdo;
    private $user_nav;
    private $notifications;

    public function __construct() {
        require_once SRC_PATH . '/config/db.php';
        global $pdo, $user_nav;
        $this->pdo = $pdo;
        $this->user_nav = $user_nav;
        
        // Fetch notifications for current user
        $user_id = $_SESSION['user_id'] ?? null;
        if ($user_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$user_id]);
            $this->notifications = $stmt->fetchAll();
        } else {
            $this->notifications = [];
        }
    }

    public function actionDashboard() {
        $page_title = 'Dashboard';
        
        // Get current user ID from session
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Fetch current resident info from database
        $stmt = $this->pdo->prepare("SELECT u.*, f.number as flat_number, f.floor, f.occupied, b.name as block_name 
                                     FROM users u 
                                     LEFT JOIN flats f ON u.flat_id = f.id 
                                     LEFT JOIN blocks b ON f.block_id = b.id 
                                     WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();

        $resident_info = [
            'name' => $user_data['name'] ?? '',
            'mobile' => $user_data['mobile'] ?? '',
            'email' => $user_data['email'] ?? '',
            'block' => $user_data['block_name'] ?? '',
            'flat' => $user_data['flat_number'] ?? '',
            'floor' => $user_data['floor'] ?? '',
            'occupancy_type' => isset($user_data['occupied']) ? ($user_data['occupied'] ? 'Occupied' : 'Vacant') : 'N/A',
            'since' => isset($user_data['created_at']) ? date('M d, Y', strtotime($user_data['created_at'])) : '',
        ];

        // Fetch today's visitors for this resident
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM visitors 
                                     WHERE flat_id = ? AND DATE(check_in) = CURRENT_DATE");
        $stmt->execute([$user_data['flat_id'] ?? 0]);
        $today_count = $stmt->fetchColumn();

        // Fetch this week's visitors
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM visitors 
                                     WHERE flat_id = ? AND EXTRACT(WEEK FROM check_in) = EXTRACT(WEEK FROM CURRENT_DATE) AND EXTRACT(YEAR FROM check_in) = EXTRACT(YEAR FROM CURRENT_DATE)");
        $stmt->execute([$user_data['flat_id'] ?? 0]);
        $week_count = $stmt->fetchColumn();

        // Fetch this month's visitors
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM visitors 
                                     WHERE flat_id = ? AND EXTRACT(MONTH FROM check_in) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM check_in) = EXTRACT(YEAR FROM CURRENT_DATE)");
        $stmt->execute([$user_data['flat_id'] ?? 0]);
        $month_count = $stmt->fetchColumn();

        $stats = [
            ['name' => "Today's Visitors", 'value' => $today_count, 'icon' => 'UserCheck', 'color' => 'from-blue-500 to-blue-600'],
            ['name' => 'This Week', 'value' => $week_count, 'icon' => 'Calendar', 'color' => 'from-emerald-500 to-emerald-600'],
            ['name' => 'This Month', 'value' => $month_count, 'icon' => 'Users', 'color' => 'from-purple-500 to-purple-600'],
        ];

        // Fetch recent visitors for this resident from database
        $stmt = $this->pdo->prepare("SELECT v.* FROM visitors v 
                                     WHERE v.flat_id = ? 
                                     ORDER BY v.check_in DESC LIMIT 5");
        $stmt->execute([$user_data['flat_id'] ?? 0]);
        $recent_visitors = $stmt->fetchAll();

        require VIEWS_PATH . '/layouts/user_layout.php';
    }

    public function actionAppointments() {
        $page_title = 'Appointments';
        $user_id = $_SESSION['user_id'] ?? null;

        if (!$user_id) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Ensure user_id is an integer
        $user_id = (int)$user_id;
        
        // Verify user exists in database
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            session_destroy();
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Handle appointment creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
            $visitor_name = trim($_POST['visitor_name'] ?? '');
            $visitor_contact = trim($_POST['visitor_contact'] ?? '');
            $appointment_date = trim($_POST['date'] ?? '');
            $appointment_time = trim($_POST['time'] ?? '');
            $purpose = trim($_POST['purpose'] ?? '');

            // Validate all required fields are filled
            if (!empty($visitor_name) && !empty($visitor_contact) && !empty($appointment_date) && !empty($appointment_time) && !empty($purpose)) {
                try {
                    // Combine date and time into appointment_time
                    $appointment_datetime = $appointment_date . ' ' . $appointment_time;

                    // Verify user exists in database before creating appointment
                    $stmt = $this->pdo->prepare("SELECT id, flat_id FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        $_SESSION['message'] = 'User session invalid. Please log in again.';
                        $_SESSION['message_type'] = 'error';
                        header('Location: ' . BASE_URL . '/login');
                        exit;
                    }
                    
                    // Insert appointment directly into appointments table with all visitor information
                    $stmt = $this->pdo->prepare("INSERT INTO appointments 
                                                (user_id, visitor_name, visitor_contact, purpose, appointment_time, status, created_at) 
                                                VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                    
                    if ($stmt->execute([$user_id, $visitor_name, $visitor_contact, $purpose, $appointment_datetime])) {
                        // Get user details for notification
                        $stmt_user = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
                        $stmt_user->execute([$user_id]);
                        $user = $stmt_user->fetch();
                        $resident_name = $user['name'] ?? 'A resident';
                        
                        // Create notification for admins
                        $notification_msg = "New appointment created by " . $resident_name . " for " . $visitor_name . " on " . date('M d, Y', strtotime($appointment_datetime)) . " at " . date('H:i', strtotime($appointment_datetime));
                        $stmt_notif = $this->pdo->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (NULL, ?, FALSE, NOW())");
                        $stmt_notif->execute([$notification_msg]);
                        
                        $_SESSION['message'] = 'Appointment created successfully';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Error creating appointment';
                        $_SESSION['message_type'] = 'error';
                    }
                } catch (PDOException $e) {
                    // Log the error for debugging
                    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
                    $_SESSION['message_type'] = 'error';
                }
                
                header('Location: ' . BASE_URL . '/user/appointments');
                exit;
            } else {
                $_SESSION['message'] = 'Please fill in all required fields';
                $_SESSION['message_type'] = 'error';
                header('Location: ' . BASE_URL . '/user/appointments');
                exit;
            }
        }

        // Fetch appointments for current user from database
        $stmt = $this->pdo->prepare("SELECT a.*, u.name as resident_name, f.number as flat_number, b.name as block_name
                                     FROM appointments a 
                                     LEFT JOIN users u ON a.user_id = u.id
                                     LEFT JOIN flats f ON u.flat_id = f.id
                                     LEFT JOIN blocks b ON f.block_id = b.id
                                     WHERE a.user_id = ? 
                                     ORDER BY a.appointment_time DESC");
        $stmt->execute([$user_id]);
        $appointments = $stmt->fetchAll();

        // Get user details for display
        $stmt = $this->pdo->prepare("SELECT u.*, f.number as flat_number, f.floor, b.name as block_name 
                                     FROM users u 
                                     LEFT JOIN flats f ON u.flat_id = f.id 
                                     LEFT JOIN blocks b ON f.block_id = b.id 
                                     WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();

        $resident_info = [
            'name' => $user_data['name'] ?? '',
            'block' => $user_data['block_name'] ?? '',
            'flat' => $user_data['flat_number'] ?? '',
        ];

        require VIEWS_PATH . '/layouts/user_layout.php';
    }

    public function actionVisitorLog() {
        $page_title = 'Visitor Log';
        $user_id = $_SESSION['user_id'] ?? null;

        if (!$user_id) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Fetch flat_id for current user
        $stmt = $this->pdo->prepare("SELECT flat_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $flat_id = $user['flat_id'] ?? null;

        // Fetch all visitors for this resident's flat from database
        $visitors = [];
        if ($flat_id) {
            // Only fetch visitors if flat_id is assigned
            $stmt = $this->pdo->prepare("SELECT v.*, a.id as appointment_id FROM visitors v 
                                         LEFT JOIN appointments a ON a.visitor_id = v.id
                                         WHERE v.flat_id = ? 
                                         ORDER BY v.check_in DESC");
            $stmt->execute([$flat_id]);
            $visitors = $stmt->fetchAll();
        } else {
            // Set a message indicating no flat is assigned
            $_SESSION['message'] = 'Your flat assignment is not set. Please contact admin.';
            $_SESSION['message_type'] = 'warning';
        }

        require VIEWS_PATH . '/layouts/user_layout.php';
    }

    public function actionNotifications() {
        // Get current user ID from session
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        // Handle AJAX requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            header('Content-Type: application/json');

            if ($_POST['action'] === 'toggle_read' && isset($_POST['notification_id'])) {
                $notification_id = (int)$_POST['notification_id'];

                // First, get current read status
                $stmt = $this->pdo->prepare("SELECT is_read FROM notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$notification_id, $user_id]);
                $current = $stmt->fetch();

                if ($current) {
                    $new_status = !$current['is_read'];
                    $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$new_status, $notification_id, $user_id]);

                    echo json_encode([
                        'success' => true,
                        'is_read' => $new_status,
                        'message' => $new_status ? 'Marked as read' : 'Marked as unread'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Notification not found']);
                }
                exit;
            } elseif ($_POST['action'] === 'get_notification_count') {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
                $stmt->execute([$user_id]);
                $unread_count = $stmt->fetchColumn();

                echo json_encode([
                    'success' => true,
                    'unread_count' => (int)$unread_count
                ]);
                exit;
            } elseif ($_POST['action'] === 'get_notification_stats') {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $total_count = $stmt->fetchColumn();

                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = TRUE");
                $stmt->execute([$user_id]);
                $read_count = $stmt->fetchColumn();

                $unread_count = $total_count - $read_count;

                echo json_encode([
                    'success' => true,
                    'total_count' => (int)$total_count,
                    'read_count' => (int)$read_count,
                    'unread_count' => (int)$unread_count
                ]);
                exit;
            } elseif ($_POST['action'] === 'get_recent_notifications') {
                // Get unread count
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
                $stmt->execute([$user_id]);
                $unread_count = $stmt->fetchColumn();

                // Get recent unread notifications (last 5)
                $stmt = $this->pdo->prepare("SELECT id, message, created_at FROM notifications 
                                             WHERE user_id = ? AND is_read = FALSE 
                                             ORDER BY created_at DESC LIMIT 5");
                $stmt->execute([$user_id]);
                $recent_notifications = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'unread_count' => (int)$unread_count,
                    'notifications' => $recent_notifications
                ]);
                exit;
            }
        }

        // For direct page access (fallback), redirect to dashboard
        header('Location: ' . BASE_URL . '/user/dashboard');
        exit;
    }
}
?>
