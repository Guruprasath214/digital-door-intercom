<?php
/**
 * AdminController - Admin panel functionality
 */

class AdminController {
    private $pdo;
    private $admin_nav;
    private $notifications;

    public function __construct() {
        global $admin_nav, $pdo;
        if (!$pdo) {
            require_once SRC_PATH . '/config/db.php';
        }
        $this->pdo = $pdo;
        $this->admin_nav = $admin_nav;
        // Fetch notifications for admin (global notifications where user_id IS NULL)
        $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $this->notifications = $stmt->fetchAll();
    }

    public function actionDashboard() {
        $page_title = 'Admin Dashboard';
        
        // Fetch counts from database
        $block_count = $this->pdo->query("SELECT COUNT(*) FROM blocks")->fetchColumn();
        $flat_count = $this->pdo->query("SELECT COUNT(*) FROM flats")->fetchColumn();
        $resident_count = $this->pdo->query("SELECT COUNT(*) FROM users WHERE is_primary = TRUE")->fetchColumn();
        $visitor_count = $this->pdo->query("SELECT COUNT(*) FROM visitors WHERE DATE(check_in) = CURRENT_DATE")->fetchColumn();

        $stats = [
            ['name' => 'Total Blocks', 'value' => $block_count, 'icon' => 'Building2', 'color' => 'bg-indigo-600', 'change' => 'Actual data'],
            ['name' => 'Total Flats', 'value' => $flat_count, 'icon' => 'Home', 'color' => 'bg-blue-600', 'change' => 'Actual data'],
            ['name' => 'Total Residents', 'value' => $resident_count, 'icon' => 'Users', 'color' => 'bg-emerald-600', 'change' => 'Actual data'],
            ['name' => 'Visitors Today', 'value' => $visitor_count, 'icon' => 'UserCheck', 'color' => 'bg-amber-600', 'change' => 'Actual data'],
        ];

        // Fetch today's appointments
        $stmt = $this->pdo->prepare("SELECT a.*, 
                            u.name as resident_name,
                            f.number as flat_number,
                            b.name as block_name,
                            COALESCE(v.name, a.visitor_name) as visitor_name
                         FROM appointments a 
                         LEFT JOIN users u ON a.user_id = u.id 
                         LEFT JOIN flats f ON u.flat_id = f.id
                         LEFT JOIN blocks b ON f.block_id = b.id
                         LEFT JOIN visitors v ON a.visitor_id = v.id 
                         WHERE DATE(a.appointment_time) = CURRENT_DATE 
                         ORDER BY a.appointment_time ASC");
        $stmt->execute();
        $today_appointments = $stmt->fetchAll();

        // Fetch recent visitors
        $stmt = $this->pdo->prepare("SELECT v.*, f.number as flat_number, b.name as block_name, u.name as resident_name 
                                     FROM visitors v 
                                     LEFT JOIN flats f ON v.flat_id = f.id 
                                     LEFT JOIN blocks b ON f.block_id = b.id 
                                     LEFT JOIN users u ON u.flat_id = f.id
                                     ORDER BY v.check_in DESC LIMIT 5");
        $stmt->execute();
        $recent_visitors = $stmt->fetchAll();

        // Visitor trends (last 7 days, including zero-count days)
        $trend_stmt = $this->pdo->prepare("SELECT DATE(check_in) as day, COUNT(*) as visitors
                                           FROM visitors
                                           WHERE check_in >= CURRENT_DATE - INTERVAL '6 days'
                                           GROUP BY DATE(check_in)");
        $trend_stmt->execute();
        $trend_rows = $trend_stmt->fetchAll();
        $trend_map = [];
        foreach ($trend_rows as $row) {
            $trend_map[$row['day']] = (int)$row['visitors'];
        }
        $visitor_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new DateTime("-$i days");
            $key = $date->format('Y-m-d');
            $visitor_data[] = [
                'day' => $date->format('M d'),
                'visitors' => $trend_map[$key] ?? 0,
            ];
        }

        // Occupancy distribution (Owned vs Rent)
        $occ_stmt = $this->pdo->prepare("SELECT 
                                            SUM(CASE WHEN occupancy_type = 'owned' THEN 1 ELSE 0 END) as owned,
                                            SUM(CASE WHEN occupancy_type = 'rent' THEN 1 ELSE 0 END) as rent
                                         FROM users");
        $occ_stmt->execute();
        $occ_row = $occ_stmt->fetch();
        $occupancy = [[
            'month' => 'All',
            'owned' => (int)($occ_row['owned'] ?? 0),
            'rent' => (int)($occ_row['rent'] ?? 0),
        ]];

        require VIEWS_PATH . '/layouts/admin_layout.php';
    }

    public function actionBlocks() {
        $page_title = 'Blocks Management';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'add_block') {
                $block_name = $_POST['block_name'] ?? '';
                if (!empty($block_name)) {
                    $stmt = $this->pdo->prepare("INSERT INTO blocks (name) VALUES (?)");
                    $stmt->execute([$block_name]);
                    $_SESSION['message'] = 'Block added successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/blocks');
                    exit;
                }
            } elseif ($action === 'add_floor') {
                $block_id = $_POST['block_id'] ?? '';
                $floor_no = $_POST['floor_no'] ?? '';
                if (!empty($block_id) && !empty($floor_no)) {
                    // Check if floor already exists for this block
                    $stmt = $this->pdo->prepare("SELECT id FROM floors WHERE block_id = ? AND floor_no = ?");
                    $stmt->execute([$block_id, $floor_no]);
                    if (!$stmt->fetch()) {
                        $stmt = $this->pdo->prepare("INSERT INTO floors (block_id, floor_no) VALUES (?, ?)");
                        $stmt->execute([$block_id, $floor_no]);
                        $_SESSION['message'] = 'Floor added successfully';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Floor already exists for this block';
                        $_SESSION['message_type'] = 'error';
                    }
                    header('Location: ' . BASE_URL . '/admin/blocks');
                    exit;
                }
            } elseif ($action === 'delete_block') {
                $block_id = $_POST['id'] ?? '';
                if (!empty($block_id)) {
                    // Delete associated floors first
                    $stmt = $this->pdo->prepare("DELETE FROM floors WHERE block_id = ?");
                    $stmt->execute([$block_id]);
                    // Delete block
                    $stmt = $this->pdo->prepare("DELETE FROM blocks WHERE id = ?");
                    $stmt->execute([$block_id]);
                    $_SESSION['message'] = 'Block deleted successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/blocks');
                    exit;
                }
            } elseif ($action === 'delete_floor') {
                $floor_id = $_POST['floor_id'] ?? '';
                if (!empty($floor_id)) {
                    $stmt = $this->pdo->prepare("DELETE FROM floors WHERE id = ?");
                    $stmt->execute([$floor_id]);
                    $_SESSION['message'] = 'Floor deleted successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/blocks');
                    exit;
                }
            }
        }

        // Fetch blocks with their floors
        $stmt = $this->pdo->query("SELECT b.id, b.name as block_name, f.id as floor_id, f.floor_no 
                                  FROM blocks b 
                                  LEFT JOIN floors f ON b.id = f.block_id 
                                  ORDER BY b.name ASC, f.floor_no ASC");
        $rows = $stmt->fetchAll();
        
        // Group floors by block
        $blocks = [];
        foreach ($rows as $row) {
            $block_id = $row['id'];
            if (!isset($blocks[$block_id])) {
                $blocks[$block_id] = [
                    'id' => $row['id'],
                    'block_name' => $row['block_name'],
                    'floors' => []
                ];
            }
            if ($row['floor_id']) {
                $blocks[$block_id]['floors'][] = [
                    'id' => $row['floor_id'],
                    'floor_no' => $row['floor_no']
                ];
            }
        }
        $blocks = array_values($blocks);

        require VIEWS_PATH . '/layouts/admin_layout.php';
    }

    public function actionFlats() {
        $page_title = 'Flats Management';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_flat'])) {
                $block_id = $_POST['block_id'] ?? '';
                $flat_number = $_POST['flat_number'] ?? '';
                $floor = $_POST['floor'] ?? '';

                if (!empty($block_id) && !empty($flat_number) && !empty($floor)) {
                    $stmt = $this->pdo->prepare("INSERT INTO flats (block_id, number, floor) VALUES (?, ?, ?)");
                    $stmt->execute([$block_id, $flat_number, $floor]);
                    $_SESSION['message'] = 'Flat added successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/flats');
                    exit;
                } else {
                    $_SESSION['message'] = 'Please fill in all required fields';
                    $_SESSION['message_type'] = 'error';
                }
            } elseif (isset($_POST['update_flat'])) {
                $flat_id = $_POST['flat_id'] ?? '';
                $block_id = $_POST['block_id'] ?? '';
                $flat_number = $_POST['flat_number'] ?? '';
                $floor = $_POST['floor'] ?? '';

                if (!empty($flat_id) && !empty($block_id) && !empty($flat_number) && !empty($floor)) {
                    $stmt = $this->pdo->prepare("UPDATE flats SET block_id = ?, number = ?, floor = ? WHERE id = ?");
                    $stmt->execute([$block_id, $flat_number, $floor, $flat_id]);
                    $_SESSION['message'] = 'Flat updated successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/flats');
                    exit;
                } else {
                    $_SESSION['message'] = 'Please fill in all required fields';
                    $_SESSION['message_type'] = 'error';
                }
            } elseif (isset($_POST['delete_flat'])) {
                $flat_id = $_POST['flat_id'] ?? '';
                if (!empty($flat_id)) {
                    $stmt = $this->pdo->prepare("DELETE FROM flats WHERE id = ?");
                    $stmt->execute([$flat_id]);
                    $_SESSION['message'] = 'Flat deleted successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/flats');
                    exit;
                }
            }
        }

        $blocks = $this->pdo->query("SELECT * FROM blocks ORDER BY name ASC")->fetchAll();
        $flats = $this->pdo->query("SELECT f.*, b.name as block_name FROM flats f JOIN blocks b ON f.block_id = b.id ORDER BY b.name, f.floor, f.number ASC")->fetchAll();

        // Fetch floors grouped by block for the add/edit modal
        $stmt = $this->pdo->query("SELECT f.block_id, f.floor_no FROM floors f ORDER BY f.block_id, f.floor_no ASC");
        $floors_by_block = [];
        while ($row = $stmt->fetch()) {
            $floors_by_block[$row['block_id']][] = $row['floor_no'];
        }

        require VIEWS_PATH . '/layouts/admin_layout.php';
    }

    public function actionGetFloorsByBlock() {
        header('Content-Type: application/json');

        $block_id = $_GET['block_id'] ?? null;

        if (!$block_id) {
            echo json_encode(['error' => 'Block ID is required']);
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT f.floor FROM floors f
                WHERE f.block_id = ?
                ORDER BY f.floor_no ASC
            ");
            $stmt->execute([$block_id]);
            $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['floors' => $floors]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to fetch floors']);
        }
        exit;
    }

    public function actionGetAvailableFlats() {
        header('Content-Type: application/json');

        $block_id = $_GET['block_id'] ?? null;
        $floor = $_GET['floor'] ?? null;
        $current_flat_id = $_GET['current_flat_id'] ?? null;

        if (!$block_id || !$floor) {
            echo json_encode(['error' => 'Block ID and floor are required', 'flats' => []]);
            exit;
        }

        try {
            // Cast to proper types to ensure correct query matching
            $block_id = (int)$block_id;
            $floor = (int)$floor;

            $stmt = $this->pdo->prepare("
                SELECT f.id, f.number,
                       CASE 
                           WHEN f.occupied = 1 AND f.id != ? THEN 'occupied' 
                           WHEN f.occupied = 1 AND f.id = ? THEN 'current'
                           ELSE 'vacant' 
                       END as status
                FROM flats f
                WHERE f.block_id = ? AND f.floor = ?
                ORDER BY f.number ASC
            ");
            $stmt->execute([$current_flat_id, $current_flat_id, $block_id, $floor]);
            $flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Always return a valid response
            if (is_array($flats) && count($flats) > 0) {
                echo json_encode(['success' => true, 'flats' => $flats]);
            } else {
                echo json_encode(['success' => true, 'flats' => [], 'message' => 'No flats found for this block and floor']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to fetch flats: ' . $e->getMessage(), 'flats' => []]);
        }
        exit;
    }

    public function actionResidents() {
        $page_title = 'Residents Management';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_resident'])) {
                $name = $_POST['name'] ?? '';
                $mobile = $_POST['mobile'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $flat_id = $_POST['flat_id'] ?? null;
                $occupancy_type = $_POST['occupancy_type'] ?? '';
                $registration_date = $_POST['registration_date'] ?? '';
                $emergency_contact_name = $_POST['emergency_contact_name'] ?? '';
                $emergency_contact = $_POST['emergency_contact'] ?? '';

                if (!empty($name) && !empty($mobile) && !empty($password)) {
                    // Check if flat is available
                    if (!empty($flat_id)) {
                        $stmt = $this->pdo->prepare("SELECT occupied FROM flats WHERE id = ?");
                        $stmt->execute([$flat_id]);
                        $flat = $stmt->fetch();
                        if ($flat && $flat['occupied']) {
                            $_SESSION['message'] = 'Selected flat is already occupied';
                            $_SESSION['message_type'] = 'error';
                            header('Location: ' . BASE_URL . '/admin/residents');
                            exit;
                        }
                    }

                    try {
                        $this->pdo->beginTransaction();

                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $this->pdo->prepare("INSERT INTO users (name, mobile, email, password, flat_id, occupancy_type, registration_date, emergency_contact_name, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $mobile, $email, $hashed_password, $flat_id, $occupancy_type, $registration_date, $emergency_contact_name, $emergency_contact]);
                        $new_user_id = (int)$this->pdo->lastInsertId();

                        // Mark flat as occupied if assigned
                        if (!empty($flat_id)) {
                            $stmt = $this->pdo->prepare("UPDATE flats SET occupied = 1 WHERE id = ?");
                            $stmt->execute([$flat_id]);
                        }

                        if ($new_user_id > 0) {
                            $message = 'Your resident account has been created. You can now log in and view your notifications.';
                            $this->createUserNotification($new_user_id, $message);

                            if (!empty($flat_id)) {
                                $this->notifyBlockResidentsOnNewResident($flat_id, $name, $new_user_id);
                            }
                        }

                        $this->pdo->commit();
                        $_SESSION['message'] = 'Resident added successfully';
                        $_SESSION['message_type'] = 'success';
                    } catch (Exception $e) {
                        $this->pdo->rollBack();
                        $_SESSION['message'] = 'Failed to add resident';
                        $_SESSION['message_type'] = 'error';
                    }

                    header('Location: ' . BASE_URL . '/admin/residents');
                    exit;
                } else {
                    $_SESSION['message'] = 'Please fill in all required fields';
                    $_SESSION['message_type'] = 'error';
                }
            } elseif (isset($_POST['update_resident'])) {
                $resident_id = $_POST['resident_id'] ?? '';
                $name = $_POST['name'] ?? '';
                $mobile = $_POST['mobile'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? ''; // Password might be optional for update
                $flat_id = $_POST['flat_id'] ?? null;
                $occupancy_type = $_POST['occupancy_type'] ?? '';
                $registration_date = $_POST['registration_date'] ?? '';
                $emergency_contact_name = $_POST['emergency_contact_name'] ?? '';
                $emergency_contact = $_POST['emergency_contact'] ?? '';

                if (!empty($resident_id) && !empty($name) && !empty($mobile)) {
                    // Get current flat assignment
                    $stmt = $this->pdo->prepare("SELECT flat_id FROM users WHERE id = ?");
                    $stmt->execute([$resident_id]);
                    $current_flat = $stmt->fetch();

                    // Check if new flat is available (if changed)
                    if (!empty($flat_id) && $flat_id != $current_flat['flat_id']) {
                        $stmt = $this->pdo->prepare("SELECT occupied FROM flats WHERE id = ?");
                        $stmt->execute([$flat_id]);
                        $flat = $stmt->fetch();
                        if ($flat && $flat['occupied']) {
                            $_SESSION['message'] = 'Selected flat is already occupied';
                            $_SESSION['message_type'] = 'error';
                            header('Location: ' . BASE_URL . '/admin/residents');
                            exit;
                        }
                    }

                    try {
                        $this->pdo->beginTransaction();

                        $sql = "UPDATE users SET name = ?, mobile = ?, email = ?, flat_id = ?, occupancy_type = ?, registration_date = ?, emergency_contact_name = ?, emergency_contact = ?";
                        $params = [$name, $mobile, $email, $flat_id, $occupancy_type, $registration_date, $emergency_contact_name, $emergency_contact];
                        if (!empty($password)) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $sql .= ", password = ?";
                            $params[] = $hashed_password;
                        }
                        $sql .= " WHERE id = ?";
                        $params[] = $resident_id;

                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute($params);

                        // Update flat occupancy
                        if ($current_flat['flat_id'] && $current_flat['flat_id'] != $flat_id) {
                            // Mark old flat as vacant
                            $stmt = $this->pdo->prepare("UPDATE flats SET occupied = 0 WHERE id = ?");
                            $stmt->execute([$current_flat['flat_id']]);
                        }
                        if (!empty($flat_id) && $flat_id != $current_flat['flat_id']) {
                            // Mark new flat as occupied
                            $stmt = $this->pdo->prepare("UPDATE flats SET occupied = 1 WHERE id = ?");
                            $stmt->execute([$flat_id]);
                        }

                        $this->pdo->commit();
                        $_SESSION['message'] = 'Resident updated successfully';
                        $_SESSION['message_type'] = 'success';
                    } catch (Exception $e) {
                        $this->pdo->rollBack();
                        $_SESSION['message'] = 'Failed to update resident';
                        $_SESSION['message_type'] = 'error';
                    }

                    header('Location: ' . BASE_URL . '/admin/residents');
                    exit;
                } else {
                    $_SESSION['message'] = 'Please fill in all required fields';
                    $_SESSION['message_type'] = 'error';
                }
            } elseif (isset($_POST['delete_resident'])) {
                $resident_id = $_POST['resident_id'] ?? '';
                if (!empty($resident_id)) {
                    try {
                        $this->pdo->beginTransaction();

                        // Get resident's flat before deletion
                        $stmt = $this->pdo->prepare("SELECT flat_id FROM users WHERE id = ?");
                        $stmt->execute([$resident_id]);
                        $resident = $stmt->fetch();

                        // Delete resident
                        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$resident_id]);

                        // Mark flat as vacant if resident had one
                        if ($resident && $resident['flat_id']) {
                            $stmt = $this->pdo->prepare("UPDATE flats SET occupied = 0 WHERE id = ?");
                            $stmt->execute([$resident['flat_id']]);
                        }

                        $this->pdo->commit();
                        $_SESSION['message'] = 'Resident deleted successfully';
                        $_SESSION['message_type'] = 'success';
                    } catch (Exception $e) {
                        $this->pdo->rollBack();
                        $_SESSION['message'] = 'Failed to delete resident';
                        $_SESSION['message_type'] = 'error';
                    }

                    header('Location: ' . BASE_URL . '/admin/residents');
                    exit;
                }
            }
        }

        $flats = $this->pdo->query("SELECT f.id, f.number, b.name as block_name, f.floor FROM flats f JOIN blocks b ON f.block_id = b.id ORDER BY b.name, f.floor, f.number ASC")->fetchAll();
        $residents = $this->pdo->query("SELECT u.*, f.number as flat_number, b.name as block_name, f.block_id, f.floor FROM users u LEFT JOIN flats f ON u.flat_id = f.id LEFT JOIN blocks b ON f.block_id = b.id ORDER BY u.name ASC")->fetchAll();
        
        // Fetch blocks for the form
        $blocks = $this->pdo->query("SELECT * FROM blocks ORDER BY name ASC")->fetchAll();

        // Fetch floors grouped by block
        $stmt = $this->pdo->query("SELECT f.block_id, f.floor_no FROM floors f ORDER BY f.block_id, f.floor_no ASC");
        $floors_by_block = [];
        while ($row = $stmt->fetch()) {
            $floors_by_block[$row['block_id']][] = $row['floor_no'];
        }

        require VIEWS_PATH . '/layouts/admin_layout.php';
    }

    public function actionVisitors() {
        $page_title = 'Visitors Management';
        
        // Handle export requests
        if (isset($_GET['export'])) {
            $export_type = $_GET['export'];
            $search_term = $_GET['search'] ?? '';
            $filter_status = $_GET['filter'] ?? 'all';
            $from_date = $_GET['from_date'] ?? ($_GET['start_date'] ?? null);
            $to_date = $_GET['to_date'] ?? ($_GET['end_date'] ?? null);
            $filter_block = $_GET['block'] ?? '';
            $filter_floor = $_GET['floor'] ?? '';
            $filter_flat = $_GET['flat'] ?? '';
            $filter_source = $_GET['source'] ?? 'all';
            
            // Build query with advanced filtering
            $query = "SELECT v.*, f.number as flat_number, f.floor as floor, b.name as block_name, u.name as resident_name, a.id as appointment_id "
                   . "FROM visitors v "
                   . "LEFT JOIN flats f ON v.flat_id = f.id "
                   . "LEFT JOIN blocks b ON f.block_id = b.id "
                   . "LEFT JOIN users u ON f.id = u.flat_id "
                   . "LEFT JOIN appointments a ON a.visitor_id = v.id";
            $conditions = [];
            $params = [];
            
            if ($from_date) {
                $conditions[] = "DATE(v.check_in) >= ?";
                $params[] = $from_date;
            }
            if ($to_date) {
                $conditions[] = "DATE(v.check_in) <= ?";
                $params[] = $to_date;
            }
            if (!empty($search_term)) {
                $conditions[] = "(v.name LIKE ? OR v.mobile LIKE ? OR v.purpose LIKE ? OR u.name LIKE ?)";
                $like = '%' . $search_term . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            if ($filter_status === 'active') {
                $conditions[] = "v.check_out IS NULL";
            } elseif ($filter_status === 'checked_out') {
                $conditions[] = "v.check_out IS NOT NULL";
            }
            if (!empty($filter_block)) {
                $conditions[] = "b.name = ?";
                $params[] = $filter_block;
            }
            if ($filter_floor !== '') {
                $conditions[] = "f.floor = ?";
                $params[] = $filter_floor;
            }
            if (!empty($filter_flat)) {
                $conditions[] = "v.flat_id = ?";
                $params[] = $filter_flat;
            }
            if ($filter_source === 'appointment') {
                $conditions[] = "a.id IS NOT NULL";
            } elseif ($filter_source === 'visitor') {
                $conditions[] = "a.id IS NULL";
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY v.check_in DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $visitors = $stmt->fetchAll();
            
            if (empty($visitors)) {
                // No data to export - redirect with message
                $_SESSION['message'] = 'No visitor data available for the selected date range';
                $_SESSION['message_type'] = 'warning';
                header('Location: ' . BASE_URL . '/admin/visitors');
                exit;
            }
            
            if ($export_type === 'pdf') {
                $this->exportVisitorsPDF($visitors);
            } elseif ($export_type === 'excel') {
                $this->exportVisitorsExcel($visitors);
            }
            exit;
        }
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'add') {
                // Handle add visitor (check-in)
                $name = $_POST['name'] ?? '';
                $mobile = $_POST['mobile'] ?? '';
                $purpose = $_POST['purpose'] ?? '';
                $resident_id = $_POST['resident_id'] ?? null;
                $location_id = $_POST['location_id'] ?? null;
                $entry_time = $_POST['entry_time'] ?? null;
                $exit_time = $_POST['exit_time'] ?? null;

                if (!empty($name) && !empty($mobile) && !empty($purpose) && !empty($location_id)) {
                    // Use location_id as flat_id for database consistency
                    $flat_id = $location_id;

                    // Prepare check_in and check_out timestamps
                    $check_in = $entry_time ? date('Y-m-d H:i:s', strtotime($entry_time)) : date('Y-m-d H:i:s');
                    $check_out = $exit_time ? date('Y-m-d H:i:s', strtotime($exit_time)) : null;

                    $stmt = $this->pdo->prepare("INSERT INTO visitors (name, mobile, purpose, flat_id, check_in, check_out) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $mobile, $purpose, $flat_id, $check_in, $check_out]);

                    $this->notifyFlatResidentsOnVisitorAdd($flat_id, $name, $check_in);
                    $_SESSION['message'] = 'Visitor added successfully!';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/visitors');
                    exit;
                } else {
                    $_SESSION['message'] = 'Please fill in all required fields';
                    $_SESSION['message_type'] = 'error';
                }
            } elseif ($action === 'checkout') {
                // Handle checkout
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    // Get visitor details before checkout
                    $stmt = $this->pdo->prepare("SELECT v.*, f.id as flat_id FROM visitors v 
                                                 LEFT JOIN flats f ON v.flat_id = f.id 
                                                 WHERE v.id = ? AND v.check_out IS NULL");
                    $stmt->execute([$id]);
                    $visitor = $stmt->fetch();

                    if ($visitor) {
                        $stmt = $this->pdo->prepare("UPDATE visitors SET check_out = NOW() WHERE id = ? AND check_out IS NULL");
                        $stmt->execute([$id]);

                        // Notify residents about checkout
                        if (!empty($visitor['flat_id'])) {
                            $this->notifyFlatResidentsOnVisitorCheckout((int)$visitor['flat_id'], $visitor['name'] ?? 'Visitor', date('Y-m-d H:i:s'));
                        }

                        $_SESSION['message'] = 'Visitor checked out successfully!';
                        $_SESSION['message_type'] = 'success';
                    }
                    header('Location: ' . BASE_URL . '/admin/visitors');
                    exit;
                }
            }
        }

        $flats = $this->pdo->query("SELECT f.id, f.number, b.name as block_name, f.floor, u.name as resident_name FROM flats f JOIN blocks b ON f.block_id = b.id LEFT JOIN users u ON f.id = u.flat_id ORDER BY b.name, f.floor, f.number ASC")->fetchAll();
        $residents = $this->pdo->query("SELECT u.id, u.name, f.id as flat_id, f.number as flat_number, b.name as block_name FROM users u LEFT JOIN flats f ON u.flat_id = f.id LEFT JOIN blocks b ON f.block_id = b.id ORDER BY u.name ASC")->fetchAll();
        $visitors = $this->pdo->query("SELECT v.*, f.number as flat_number, f.floor as floor, b.name as block_name, u.name as resident_name, a.id as appointment_id FROM visitors v LEFT JOIN flats f ON v.flat_id = f.id LEFT JOIN blocks b ON f.block_id = b.id LEFT JOIN users u ON f.id = u.flat_id LEFT JOIN appointments a ON a.visitor_id = v.id ORDER BY v.check_in DESC")->fetchAll();
        
        require VIEWS_PATH . '/layouts/admin_layout.php';
    }

    private function exportVisitorsPDF($visitors) {
        require_once VENDOR_PATH . '/tecnickcom/tcpdf/tcpdf.php';
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Build title with date range if provided
        $title = 'Visitors Report';
        if (isset($_GET['start_date']) || isset($_GET['end_date'])) {
            $date_range = '';
            if (isset($_GET['start_date'])) {
                $date_range .= 'From: ' . date('M d, Y', strtotime($_GET['start_date']));
            }
            if (isset($_GET['end_date'])) {
                if ($date_range) $date_range .= ' ';
                $date_range .= 'To: ' . date('M d, Y', strtotime($_GET['end_date']));
            }
            $title .= ' - ' . $date_range;
        }
        
        // Set default header data
        $pdf->SetHeaderData('', 0, $title, 'Generated on ' . date('Y-m-d H:i:s'));
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Title
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->Cell(0, 10, 'Total Visitors: ' . count($visitors), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Table header
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetTextColor(0);
        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('', 'B');
        
        $header = array('Visitor', 'Contact', 'Purpose', 'Resident', 'Location', 'Entry Time', 'Exit Time', 'Status');
        $w = array(25, 25, 25, 25, 30, 25, 25, 20);
        
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        
        // Table data
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        
        $fill = 0;
        foreach($visitors as $visitor) {
            $status = empty($visitor['check_out']) ? 'Inside' : 'Checked Out';
            $entry_time = $visitor['check_in'] ? date('M d, Y H:i', strtotime($visitor['check_in'])) : 'N/A';
            $exit_time = $visitor['check_out'] ? date('M d, Y H:i', strtotime($visitor['check_out'])) : '-';
            
            $pdf->Cell($w[0], 6, $visitor['name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[1], 6, $visitor['mobile'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, $visitor['purpose'] ?? 'N/A', 'LR', 0, 'L', $fill);
            $pdf->Cell($w[3], 6, $visitor['resident_name'] ?? 'N/A', 'LR', 0, 'L', $fill);
            $pdf->Cell($w[4], 6, $visitor['block_name'] . ' - Flat ' . $visitor['flat_number'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[5], 6, $entry_time, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[6], 6, $exit_time, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[7], 6, $status, 'LR', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }
        
        // Closing line
        $pdf->Cell(array_sum($w), 0, '', 'T');
        
        // Output PDF
        $pdf->Output('visitors_report_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    }

    private function exportVisitorsExcel($visitors) {
        require_once VENDOR_PATH . '/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title with date range if provided
        $title = 'Visitors Management Report';
        if (isset($_GET['start_date']) || isset($_GET['end_date'])) {
            $date_range = '';
            if (isset($_GET['start_date'])) {
                $date_range .= 'From: ' . date('M d, Y', strtotime($_GET['start_date']));
            }
            if (isset($_GET['end_date'])) {
                if ($date_range) $date_range .= ' ';
                $date_range .= 'To: ' . date('M d, Y', strtotime($_GET['end_date']));
            }
            $title .= ' - ' . $date_range;
        }
        
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Set total count
        $sheet->setCellValue('A2', 'Total Visitors: ' . count($visitors));
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Set headers
        $headers = ['Visitor', 'Contact', 'Purpose', 'Resident', 'Location', 'Entry Time', 'Exit Time', 'Status'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '4', $header);
            $sheet->getStyle($column . '4')->getFont()->setBold(true);
            $sheet->getStyle($column . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
            $column++;
        }
        
        // Set data
        $row = 5;
        foreach ($visitors as $visitor) {
            $status = empty($visitor['check_out']) ? 'Inside' : 'Checked Out';
            $entry_time = $visitor['check_in'] ? date('M d, Y H:i', strtotime($visitor['check_in'])) : 'N/A';
            $exit_time = $visitor['check_out'] ? date('M d, Y H:i', strtotime($visitor['check_out'])) : '-';
            
            $sheet->setCellValue('A' . $row, $visitor['name']);
            $sheet->setCellValue('B' . $row, $visitor['mobile']);
            $sheet->setCellValue('C' . $row, $visitor['purpose'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $visitor['resident_name'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $visitor['block_name'] . ' - Flat ' . $visitor['flat_number']);
            $sheet->setCellValue('F' . $row, $entry_time);
            $sheet->setCellValue('G' . $row, $exit_time);
            $sheet->setCellValue('H' . $row, $status);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="visitors_report_' . date('Y-m-d_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function actionAppointments() {
        $page_title = 'Appointments Management';
        
        // Get current admin ID from session
        $admin_id = $_SESSION['admin_id'] ?? null;

        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'scan_checkin') {
                $qr_data = $_POST['qr_data'] ?? '';
                
                // Extract appointment ID from QR code (format: APT{id})
                if (preg_match('/APT(\d+)/', $qr_data, $matches)) {
                    $apt_id = $matches[1];
                    
                    try {
                        // Get appointment details with user's flat_id
                        $stmt = $this->pdo->prepare("SELECT a.*, u.flat_id FROM appointments a 
                                                    JOIN users u ON a.user_id = u.id 
                                                    WHERE a.id = ?");
                        $stmt->execute([$apt_id]);
                        $apt = $stmt->fetch();
                        
                        if (!$apt || $apt['status'] !== 'pending') {
                            $_SESSION['message'] = 'Invalid QR code or appointment already checked in';
                            $_SESSION['message_type'] = 'error';
                        } else {
                            // Start transaction
                            $this->pdo->beginTransaction();
                            
                            // Create visitor record in visitors table
                            $stmt_visitor = $this->pdo->prepare("INSERT INTO visitors (name, mobile, purpose, flat_id, check_in) 
                                                                  VALUES (?, ?, ?, ?, NOW())");
                            $stmt_visitor->execute([
                                $apt['visitor_name'],
                                $apt['visitor_contact'],
                                $apt['purpose'],
                                $apt['flat_id']
                            ]);
                            $visitor_id = $this->pdo->lastInsertId();
                            
                            // Update appointment status and link visitor
                            $stmt_apt = $this->pdo->prepare("UPDATE appointments SET status = 'checked_in', check_in = NOW(), visitor_id = ? 
                                                            WHERE id = ?");
                            $stmt_apt->execute([$visitor_id, $apt_id]);

                            if (!empty($apt['user_id'])) {
                                $message = ($apt['visitor_name'] ?? 'Your visitor') . ' has been allowed and checked in.';
                                $this->createUserNotification((int)$apt['user_id'], $message);
                            }
                            
                            // Commit transaction
                            $this->pdo->commit();
                            
                            $_SESSION['message'] = ($apt['visitor_name'] ?? 'Visitor') . ' checked in successfully';
                            $_SESSION['message_type'] = 'success';
                        }
                    } catch (PDOException $e) {
                        $this->pdo->rollBack();
                        $_SESSION['message'] = 'Error: ' . $e->getMessage();
                        $_SESSION['message_type'] = 'error';
                    }
                } else {
                    $_SESSION['message'] = 'Invalid QR code format';
                    $_SESSION['message_type'] = 'error';
                }
                
                header('Location: ' . BASE_URL . '/admin/appointments');
                exit;
            }
            
            if ($action === 'scan_checkout') {
                $qr_data = $_POST['qr_data'] ?? '';
                
                // Extract appointment ID from QR code
                if (preg_match('/APT(\d+)/', $qr_data, $matches)) {
                    $apt_id = $matches[1];
                    
                    try {
                        // Get appointment details including visitor_id and user's flat_id
                        $stmt = $this->pdo->prepare("SELECT a.*, u.flat_id FROM appointments a 
                                                     JOIN users u ON a.user_id = u.id 
                                                     WHERE a.id = ? AND a.status = 'checked_in'");
                        $stmt->execute([$apt_id]);
                        $apt = $stmt->fetch();
                        
                        if (!$apt) {
                            $_SESSION['message'] = 'Invalid QR code or visitor not checked in';
                            $_SESSION['message_type'] = 'error';
                        } else {
                            // Start transaction
                            $this->pdo->beginTransaction();
                            
                            // Update visitor check_out time
                            if ($apt['visitor_id']) {
                                $stmt_visitor = $this->pdo->prepare("UPDATE visitors SET check_out = NOW() WHERE id = ?");
                                $stmt_visitor->execute([$apt['visitor_id']]);
                            }
                            
                            // Update appointment status
                            $stmt_apt = $this->pdo->prepare("UPDATE appointments SET status = 'completed', check_out = NOW() WHERE id = ?");
                            $stmt_apt->execute([$apt_id]);

                            // Notify residents about checkout
                            if (!empty($apt['flat_id'])) {
                                $this->notifyFlatResidentsOnVisitorCheckout((int)$apt['flat_id'], $apt['visitor_name'] ?? 'Visitor', date('Y-m-d H:i:s'));
                            }
                            
                            // Commit transaction
                            $this->pdo->commit();
                            
                            $_SESSION['message'] = ($apt['visitor_name'] ?? 'Visitor') . ' checked out successfully';
                            $_SESSION['message_type'] = 'success';
                        }
                    } catch (PDOException $e) {
                        $this->pdo->rollBack();
                        $_SESSION['message'] = 'Error: ' . $e->getMessage();
                        $_SESSION['message_type'] = 'error';
                    }
                } else {
                    $_SESSION['message'] = 'Invalid QR code format';
                    $_SESSION['message_type'] = 'error';
                }
                
                header('Location: ' . BASE_URL . '/admin/appointments');
                exit;
            }
            
            if ($action === 'manual_checkin') {
                $apt_id = $_POST['id'] ?? null;
                
                if ($apt_id) {
                    try {
                        // Get appointment details with user's flat_id
                        $stmt = $this->pdo->prepare("SELECT a.*, u.flat_id FROM appointments a 
                                                    JOIN users u ON a.user_id = u.id 
                                                    WHERE a.id = ? AND a.status = 'pending'");
                        $stmt->execute([$apt_id]);
                        $apt = $stmt->fetch();
                        
                        if (!$apt) {
                            $_SESSION['message'] = 'Could not check in visitor';
                            $_SESSION['message_type'] = 'error';
                        } else {
                            // Start transaction
                            $this->pdo->beginTransaction();
                            
                            // Create visitor record in visitors table
                            $stmt_visitor = $this->pdo->prepare("INSERT INTO visitors (name, mobile, purpose, flat_id, check_in) 
                                                                  VALUES (?, ?, ?, ?, NOW())");
                            $stmt_visitor->execute([
                                $apt['visitor_name'],
                                $apt['visitor_contact'],
                                $apt['purpose'],
                                $apt['flat_id']
                            ]);
                            $visitor_id = $this->pdo->lastInsertId();
                            
                            // Update appointment status and link visitor
                            $stmt_apt = $this->pdo->prepare("UPDATE appointments SET status = 'checked_in', check_in = NOW(), visitor_id = ? 
                                                            WHERE id = ?");
                            $stmt_apt->execute([$visitor_id, $apt_id]);

                            if (!empty($apt['user_id'])) {
                                $message = ($apt['visitor_name'] ?? 'Your visitor') . ' has been allowed and checked in.';
                                $this->createUserNotification((int)$apt['user_id'], $message);
                            }
                            
                            // Commit transaction
                            $this->pdo->commit();
                            
                            $_SESSION['message'] = ($apt['visitor_name'] ?? 'Visitor') . ' checked in successfully';
                            $_SESSION['message_type'] = 'success';
                        }
                    } catch (PDOException $e) {
                        $this->pdo->rollBack();
                        $_SESSION['message'] = 'Error: ' . $e->getMessage();
                        $_SESSION['message_type'] = 'error';
                    }
                }
                
                header('Location: ' . BASE_URL . '/admin/appointments');
                exit;
            }
            
            if ($action === 'manual_checkout') {
                $apt_id = $_POST['id'] ?? null;
                
                if ($apt_id) {
                    try {
                        // Get appointment details including visitor_id and user's flat_id
                        $stmt = $this->pdo->prepare("SELECT a.*, u.flat_id FROM appointments a 
                                                     JOIN users u ON a.user_id = u.id 
                                                     WHERE a.id = ? AND a.status = 'checked_in'");
                        $stmt->execute([$apt_id]);
                        $apt = $stmt->fetch();
                        
                        if (!$apt) {
                            $_SESSION['message'] = 'Could not check out visitor';
                            $_SESSION['message_type'] = 'error';
                        } else {
                            // Start transaction
                            $this->pdo->beginTransaction();
                            
                            // Update visitor check_out time
                            if ($apt['visitor_id']) {
                                $stmt_visitor = $this->pdo->prepare("UPDATE visitors SET check_out = NOW() WHERE id = ?");
                                $stmt_visitor->execute([$apt['visitor_id']]);
                            }
                            
                            // Update appointment status
                            $stmt_apt = $this->pdo->prepare("UPDATE appointments SET status = 'completed', check_out = NOW() WHERE id = ?");
                            $stmt_apt->execute([$apt_id]);

                            // Notify residents about checkout
                            if (!empty($apt['flat_id'])) {
                                $this->notifyFlatResidentsOnVisitorCheckout((int)$apt['flat_id'], $apt['visitor_name'] ?? 'Visitor', date('Y-m-d H:i:s'));
                            }
                            
                            // Commit transaction
                            $this->pdo->commit();
                            
                            $_SESSION['message'] = ($apt['visitor_name'] ?? 'Visitor') . ' checked out successfully';
                            $_SESSION['message_type'] = 'success';
                        }
                    } catch (PDOException $e) {
                        $this->pdo->rollBack();
                        $_SESSION['message'] = 'Error: ' . $e->getMessage();
                        $_SESSION['message_type'] = 'error';
                    }
                }
                
                header('Location: ' . BASE_URL . '/admin/appointments');
                exit;
            }
        }
        
        // Fetch all appointments from database with related data
        $stmt = $this->pdo->prepare("SELECT a.*, u.name as resident_name, f.number as flat_number, b.name as block_name
                                    FROM appointments a 
                                    LEFT JOIN users u ON a.user_id = u.id
                                    LEFT JOIN flats f ON u.flat_id = f.id
                                    LEFT JOIN blocks b ON f.block_id = b.id
                                    ORDER BY a.appointment_time DESC");
        $stmt->execute();
        $appointments = $stmt->fetchAll();
        
        require VIEWS_PATH . '/layouts/admin_layout.php';
    }

    public function actionRegisterAdmin() {
        if (($_SESSION['user_type'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $page_title = 'Register Admin';

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
                $stmt = $this->pdo->prepare('SELECT id FROM admins WHERE email = ?');
                $stmt->execute([$email]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $_SESSION['message'] = 'An admin with this email already exists';
                    $_SESSION['message_type'] = 'error';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $this->pdo->prepare('INSERT INTO admins (email, password) VALUES (?, ?)');
                    $stmt->execute([$email, $hashed_password]);
                    $_SESSION['message'] = 'Admin registered successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . '/admin/registerAdmin');
                    exit;
                }
            }
        }

        require VIEWS_PATH . '/layouts/admin_layout.php';
    }

    public function actionNotifications() {
        // Handle AJAX requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            header('Content-Type: application/json');

            if ($_POST['action'] === 'toggle_read' && isset($_POST['notification_id'])) {
                $notification_id = (int)$_POST['notification_id'];

                // First, get current read status
                $stmt = $this->pdo->prepare("SELECT is_read FROM notifications WHERE id = ?");
                $stmt->execute([$notification_id]);
                $current = $stmt->fetch();

                if ($current) {
                    $new_status = !$current['is_read'];
                    $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = ? WHERE id = ?");
                    $stmt->execute([$new_status, $notification_id]);

                    echo json_encode([
                        'success' => true,
                        'is_read' => $new_status,
                        'message' => $new_status ? 'Marked as read' : 'Marked as unread'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Notification not found']);
                }
                exit;
            } elseif ($_POST['action'] === 'get_notification_stats') {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications");
                $stmt->execute();
                $total_count = $stmt->fetchColumn();

                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = TRUE");
                $stmt->execute();
                $read_count = $stmt->fetchColumn();

                $unread_count = $total_count - $read_count;

                echo json_encode([
                    'success' => true,
                    'total_count' => (int)$total_count,
                    'read_count' => (int)$read_count,
                    'unread_count' => (int)$unread_count
                ]);
                exit;
            }
        }

        // For direct page access (fallback), redirect to dashboard
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }

    private function createUserNotification(int $user_id, string $message): void {
        if ($user_id <= 0 || $message === '') {
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, FALSE, NOW())");
        $stmt->execute([$user_id, $message]);
    }

    private function notifyFlatResidentsOnVisitorAdd(?int $flat_id, string $visitor_name, string $check_in): void {
        if (empty($flat_id)) {
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE flat_id = ?");
        $stmt->execute([$flat_id]);
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($user_ids)) {
            return;
        }

        $visit_time = $check_in ? date('M d, Y g:i A', strtotime($check_in)) : 'now';
        $message = ($visitor_name ?: 'A visitor') . ' has been added for your flat and checked in at ' . $visit_time . '.';

        foreach ($user_ids as $user_id) {
            $this->createUserNotification((int)$user_id, $message);
        }
    }

    private function notifyFlatResidentsOnVisitorCheckout(?int $flat_id, string $visitor_name, string $check_out): void {
        if (empty($flat_id)) {
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE flat_id = ?");
        $stmt->execute([$flat_id]);
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($user_ids)) {
            return;
        }

        $checkout_time = $check_out ? date('M d, Y g:i A', strtotime($check_out)) : 'now';
        $message = ($visitor_name ?: 'A visitor') . ' has checked out at ' . $checkout_time . '.';

        foreach ($user_ids as $user_id) {
            $this->createUserNotification((int)$user_id, $message);
        }
    }

    private function notifyBlockResidentsOnNewResident(?int $flat_id, string $resident_name, int $exclude_user_id): void {
        if (empty($flat_id)) {
            return;
        }

        // Get block_id and block_name for the flat
        $stmt = $this->pdo->prepare("
            SELECT f.block_id, b.name as block_name 
            FROM flats f 
            JOIN blocks b ON f.block_id = b.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$flat_id]);
        $flat_info = $stmt->fetch();

        if (!$flat_info) {
            return;
        }

        // Get all residents in the same block (excluding the new resident)
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.id 
            FROM users u 
            JOIN flats f ON u.flat_id = f.id 
            WHERE f.block_id = ? AND u.id != ?
        ");
        $stmt->execute([$flat_info['block_id'], $exclude_user_id]);
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($user_ids)) {
            return;
        }

        $message = 'A new resident, ' . ($resident_name ?: 'a resident') . ', has joined ' . $flat_info['block_name'] . '.';

        foreach ($user_ids as $user_id) {
            $this->createUserNotification((int)$user_id, $message);
        }
    }
}
?>
