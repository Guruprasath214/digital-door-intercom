<?php
/**
 * Admin Layout
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - QR Intercom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .sidebar {
            width: 16rem;
            transition: transform 0.3s ease-in-out;
        }
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        @media (max-width: 1023px) {
            .sidebar.hidden {
                position: absolute;
                z-index: 50;
            }
        }
        .nav-link {
            transition: all 0.2s;
        }
        .nav-link.active {
            background-color: #eef2ff;
            color: #1e293b;
        }
        .nav-icon {
            width: 1.5rem;
            height: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .nav-icon svg {
            width: 1.5rem;
            height: 1.5rem;
            stroke-width: 2;
            stroke: currentColor;
        }
        .nav-link:hover {
            background-color: #f1f5f9;
        }
        .backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        @keyframes bell-shake {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(-12deg); }
            40% { transform: rotate(10deg); }
            60% { transform: rotate(-8deg); }
            80% { transform: rotate(6deg); }
        }
        .bell-shake {
            animation: bell-shake 0.8s ease-in-out 1;
            transform-origin: 50% 0%;
        }
        
        /* Toast Notification Styles (Sonner-like) */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            pointer-events: none;
        }
        
        .toast {
            pointer-events: auto;
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 350px;
            max-width: 400px;
            border-left: 4px solid;
            animation: slideIn 0.2s ease-out;
            transition: all 0.2s ease;
        }
        
        .toast.toast-success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .toast.toast-error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .toast.toast-hiding {
            animation: slideOut 0.2s ease-out forwards;
        }
        
        .toast-icon {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toast-icon.success {
            color: #10b981;
        }
        
        .toast-icon.error {
            color: #ef4444;
        }
        
        .toast-message {
            flex: 1;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
        }
        
        .toast-message.success {
            color: #166534;
        }
        
        .toast-message.error {
            color: #991b1b;
        }
        
        .toast-close {
            flex-shrink: 0;
            width: 1rem;
            height: 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        @media (max-width: 640px) {
            .toast-container {
                left: 1rem;
                right: 1rem;
            }
            .toast {
                min-width: auto;
                max-width: none;
                width: 100%;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50">
    <!-- Toast Container (Sonner-like notifications) -->
    <div id="toast-container" class="toast-container"></div>
    
    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="p-6">
                <h2 id="confirmTitle" class="text-lg font-semibold text-slate-900 mb-2">Confirm Action</h2>
                <p id="confirmMessage" class="text-slate-600 text-sm mb-6">Are you sure you want to proceed?</p>
                <div class="flex gap-3">
                    <button onclick="confirmAction()" id="confirmButton" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-medium transition-colors">Delete</button>
                    <button onclick="cancelConfirmation()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex h-screen overflow-hidden">
        <!-- Mobile backdrop -->
        <div id="backdrop" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed top-0 left-0 z-50 h-screen w-64 bg-white text-slate-900 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:relative border-r border-slate-200">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-between h-20 px-6 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                            <i class="w-6 h-6 text-white" data-lucide="building-2"></i>
                        </div>
                        <div>
                            <h1 class="font-semibold text-base">QR Intercom</h1>
                            <p class="text-xs text-slate-500">Admin Panel</p>
                        </div>
                    </div>
                    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-slate-900">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
                    <?php foreach ($this->admin_nav as $item): ?>
                        <?php
                        $current_url = $_SERVER['REQUEST_URI'];
                        $is_active = strpos($current_url, $item['href']) !== false;
                        ?>
                        <a href="<?php echo BASE_URL . $item['href']; ?>" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm <?php echo $is_active ? 'active' : 'text-slate-600'; ?>">
                            <?php
                            $icons = [
                                'LayoutDashboard' => 'layout-dashboard',
                                'Building2' => 'building-2',
                                'Home' => 'home',
                                'Users' => 'users',
                                'UserCheck' => 'user-check',
                                'Calendar' => 'calendar',
                                'UserPlus' => 'user-plus',
                            ];
                            $iconName = $icons[$item['icon']] ?? 'circle';
                            ?>
                            <i class="nav-icon" data-lucide="<?php echo $iconName; ?>"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Logout -->
                <div class="p-3 border-t border-slate-200">
                    <a href="<?php echo BASE_URL; ?>/logout?type=admin" onclick="confirmLogout(event)" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-slate-600">
                        <i class="nav-icon" data-lucide="log-out"></i>
                        <span class="font-medium">Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Logout Confirmation Modal -->
        <div id="logoutModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-900 text-center mb-2">Confirm Logout</h3>
                    <p class="text-slate-600 text-center mb-6">Are you sure you want to log out? You'll need to sign in again to access your account.</p>
                    <div class="flex gap-3">
                        <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg font-medium hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>
                        <button id="confirmLogoutBtn" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-6 h-20 flex items-center justify-between flex-shrink-0 shadow-sm">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden text-slate-900">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-slate-900"><?php echo htmlspecialchars($page_title); ?></h1>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <?php
                    // Get unread notification count
                    $unread_count = 0;
                    $unread_notifications = [];
                    if (isset($this->notifications) && is_array($this->notifications)) {
                        foreach ($this->notifications as $notification) {
                            if (!$notification['is_read']) {
                                $unread_count++;
                                $unread_notifications[] = $notification;
                            }
                        }
                    }
                    ?>
                    <div class="relative group">
                        <button onclick="toggleNotifications()" id="notificationBell" class="relative p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition block <?php echo $unread_count > 0 ? 'bell-shake' : ''; ?>">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if ($unread_count > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-semibold">
                                <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Notification Hover Dropdown -->
                        <div class="absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-lg border border-slate-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="p-4">
                                <?php if (!empty($unread_notifications)): ?>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-semibold text-slate-900">Recent Notifications</h3>
                                    <span class="text-xs text-slate-500"><?php echo $unread_count; ?> unread</span>
                                </div>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    <?php 
                                    $display_count = 0;
                                    foreach ($unread_notifications as $notification): 
                                        if ($display_count >= 5) break; // Show max 5 in dropdown
                                        $display_count++;
                                    ?>
                                    <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50 border border-blue-100">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-slate-900 font-medium leading-tight">
                                                <?php echo htmlspecialchars(substr($notification['message'], 0, 80)); ?>
                                                <?php if (strlen($notification['message']) > 80): ?>...<?php endif; ?>
                                            </p>
                                            <p class="text-xs text-slate-500 mt-1">
                                                <?php echo date('g:i A', strtotime($notification['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3 pt-3 border-t border-slate-200 text-center">
                                    <button onclick="toggleNotifications(); event.stopPropagation();" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                        View all notifications →
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-bell text-slate-400 text-lg"></i>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-900 mb-1">No new notifications</h3>
                                    <p class="text-xs text-slate-500">You're all caught up!</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-semibold">
                            A
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-sm font-medium text-slate-900">Admin</p>
                            <p class="text-xs text-slate-500">Administrator</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-auto p-6">
                <?php
                // Display session message if exists
                if (isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            toast('<?php echo $_SESSION['message_type']; ?>', '<?php echo addslashes($_SESSION['message']); ?>');
                        });
                    </script>
                    <?php 
                    unset($_SESSION['message'], $_SESSION['message_type']); 
                endif;
                
                // Load the appropriate page
                $uri = $_SERVER['REQUEST_URI'];
                
                if (strpos($uri, '/admin/dashboard') !== false) {
                    require VIEWS_PATH . '/pages/admin/dashboard.php';
                } elseif (strpos($uri, '/admin/blocks') !== false) {
                    require VIEWS_PATH . '/pages/admin/blocks.php';
                } elseif (strpos($uri, '/admin/flats') !== false) {
                    require VIEWS_PATH . '/pages/admin/flats.php';
                } elseif (strpos($uri, '/admin/residents') !== false) {
                    require VIEWS_PATH . '/pages/admin/residents.php';
                } elseif (strpos($uri, '/admin/visitors') !== false) {
                    require VIEWS_PATH . '/pages/admin/visitors.php';
                } elseif (strpos($uri, '/admin/appointments') !== false) {
                    require VIEWS_PATH . '/pages/admin/appointments.php';
                } elseif (strpos($uri, '/admin/registerAdmin') !== false) {
                    require VIEWS_PATH . '/pages/admin/register_admin.php';
                } else {
                    // Default to dashboard if no match
                    require VIEWS_PATH . '/pages/admin/dashboard.php';
                }
                ?>
            </main>
        </div>
    </div>

    <script>
        /**
         * Toast Notification System (Sonner-like)
         * Mimics React's Sonner library with top-right positioned toasts
         */
        function toast(type, message) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            // Create toast element
            const toastEl = document.createElement('div');
            toastEl.className = `toast toast-${type}`;
            
            // Determine icon based on type
            const icon = type === 'success' 
                ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
                : '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
            
            toastEl.innerHTML = `
                <div class="toast-icon ${type}">
                    ${icon}
                </div>
                <div class="toast-message ${type}">
                    ${message}
                </div>
                <button class="toast-close" onclick="dismissToast(this)">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            `;
            
            // Add to container
            container.appendChild(toastEl);
            
            // Auto-dismiss after 4 seconds (Sonner default)
            setTimeout(() => {
                dismissToast(toastEl);
            }, 4000);
        }
        
        function dismissToast(element) {
            const toastEl = element.classList ? element : element.closest('.toast');
            if (!toastEl) return;
            
            toastEl.classList.add('toast-hiding');
            
            // Remove from DOM after animation completes
            setTimeout(() => {
                toastEl.remove();
            }, 200);
        }
        
        // Confirmation Modal System
        let pendingConfirmAction = null;
        
        function showConfirmation(message, title, onConfirm, buttonLabel, buttonColor) {
            document.getElementById('confirmTitle').textContent = title || 'Confirm Action';
            document.getElementById('confirmMessage').textContent = message || 'Are you sure you want to proceed?';
            const confirmBtn = document.getElementById('confirmButton');
            confirmBtn.textContent = buttonLabel || 'Delete';
            
            // Set button color based on action type
            confirmBtn.className = 'flex-1 py-2 rounded-lg font-medium transition-colors';
            if (buttonColor === 'red') {
                confirmBtn.className += ' bg-red-600 hover:bg-red-700 text-white';
            } else if (buttonColor === 'blue') {
                confirmBtn.className += ' bg-blue-600 hover:bg-blue-700 text-white';
            } else if (buttonColor === 'green') {
                confirmBtn.className += ' bg-emerald-600 hover:bg-emerald-700 text-white';
            } else {
                confirmBtn.className += ' bg-red-600 hover:bg-red-700 text-white';
            }
            
            pendingConfirmAction = onConfirm;
            document.getElementById('confirmationModal').classList.remove('hidden');
        }
        
        function confirmAction() {
            if (pendingConfirmAction && typeof pendingConfirmAction === 'function') {
                pendingConfirmAction();
            }
            cancelConfirmation();
        }
        
        function cancelConfirmation() {
            document.getElementById('confirmationModal').classList.add('hidden');
            pendingConfirmAction = null;
        }

        let logoutUrl = '';

        function confirmLogout(event) {
            event.preventDefault();
            logoutUrl = event.currentTarget.href;
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
            logoutUrl = '';
        }

        // Handle logout confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirmLogoutBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (logoutUrl) {
                        window.location = logoutUrl;
                    }
                });
            }

            // Close modal when clicking outside
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeLogoutModal();
                    }
                });
            }
        });
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('backdrop');
            
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
            
            if (!sidebar.classList.contains('-translate-x-full')) {
                backdrop.addEventListener('click', toggleSidebar);
            }
        }
        
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>

    <!-- Notification Modal -->
    <div id="notificationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-slate-200">
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-900">Notifications</h2>
                        <p class="text-slate-600 mt-1">Stay updated with system activities</p>
                    </div>
                    <button onclick="toggleNotifications()" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 max-h-[calc(90vh-140px)] overflow-y-auto">
                    <?php
                    // Group notifications by date for display
                    $notifications_by_date = [];
                    $total_notifications = 0;
                    $unread_count = 0;

                    if (isset($this->notifications) && is_array($this->notifications)) {
                        $total_notifications = count($this->notifications);
                        $unread_count = count(array_filter($this->notifications, function($n) {
                            return !$n['is_read'];
                        }));

                        foreach ($this->notifications as $notification) {
                            $date = date('Y-m-d', strtotime($notification['created_at']));
                            if (!isset($notifications_by_date[$date])) {
                                $notifications_by_date[$date] = [];
                            }
                            $notifications_by_date[$date][] = $notification;
                        }
                    }
                    ?>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-xl border border-slate-200 p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-emerald-600 flex items-center justify-center">
                                    <i class="fas fa-bell w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">Total Notifications</p>
                                    <h3 class="text-2xl font-bold text-slate-900" data-stat="total"><?php echo $total_notifications; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-600 flex items-center justify-center">
                                    <i class="fas fa-envelope-open w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">Read</p>
                                    <h3 class="text-2xl font-bold text-slate-900" data-stat="read"><?php echo $total_notifications - $unread_count; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-red-600 flex items-center justify-center">
                                    <i class="fas fa-envelope w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">Unread</p>
                                    <h3 class="text-2xl font-bold text-slate-900" data-stat="unread"><?php echo $unread_count; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    <?php if (empty($notifications_by_date)): ?>
                    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-bell text-2xl text-slate-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">No notifications yet</h3>
                        <p class="text-slate-600">You'll see your notifications here when you have any updates.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($notifications_by_date as $date => $notifications): ?>
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <div class="flex items-center gap-4">
                                    <h3 class="text-lg font-semibold text-slate-900">
                                        <?php
                                        $date_obj = new DateTime($date);
                                        $today = new DateTime();
                                        $yesterday = new DateTime('yesterday');

                                        if ($date_obj->format('Y-m-d') === $today->format('Y-m-d')) {
                                            echo 'Today';
                                        } elseif ($date_obj->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                                            echo 'Yesterday';
                                        } else {
                                            echo $date_obj->format('F j, Y');
                                        }
                                        ?>
                                    </h3>
                                    <div class="flex-1 h-px bg-slate-200"></div>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="space-y-3">
                                    <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item flex items-start gap-4 p-4 rounded-lg border transition cursor-pointer <?php echo $notification['is_read'] ? 'border-slate-100 bg-slate-50 hover:bg-slate-100' : 'border-blue-200 bg-blue-50 hover:bg-blue-100'; ?>"
                                         data-id="<?php echo $notification['id']; ?>"
                                         onclick="toggleNotificationRead(<?php echo $notification['id']; ?>)">
                                        <div class="flex-shrink-0">
                                            <?php if (!$notification['is_read']): ?>
                                            <div class="w-3 h-3 bg-blue-500 rounded-full mt-2"></div>
                                            <?php else: ?>
                                            <div class="w-3 h-3 bg-slate-300 rounded-full mt-2"></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-slate-900 <?php echo !$notification['is_read'] ? 'font-semibold' : ''; ?>">
                                                        <?php echo htmlspecialchars($notification['message']); ?>
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-1">
                                                        <?php echo date('g:i A', strtotime($notification['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Notification modal functions
        function toggleNotifications() {
            const modal = document.getElementById('notificationModal');
            modal.classList.toggle('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('notificationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleNotifications();
            }
        });

        // Toggle notification read status
        function toggleNotificationRead(notificationId) {
            fetch('<?php echo BASE_URL; ?>/admin/notifications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle_read&notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the notification item appearance
                    const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
                    const isRead = data.is_read;

                    if (isRead) {
                        notificationItem.classList.remove('border-blue-200', 'bg-blue-50', 'hover:bg-blue-100');
                        notificationItem.classList.add('border-slate-100', 'bg-slate-50', 'hover:bg-slate-100');
                        notificationItem.querySelector('.text-sm').classList.remove('font-semibold');
                        notificationItem.querySelector('.w-3').classList.remove('bg-blue-500');
                        notificationItem.querySelector('.w-3').classList.add('bg-slate-300');
                    } else {
                        notificationItem.classList.remove('border-slate-100', 'bg-slate-50', 'hover:bg-slate-100');
                        notificationItem.classList.add('border-blue-200', 'bg-blue-50', 'hover:bg-blue-100');
                        notificationItem.querySelector('.text-sm').classList.add('font-semibold');
                        notificationItem.querySelector('.w-3').classList.remove('bg-slate-300');
                        notificationItem.querySelector('.w-3').classList.add('bg-blue-500');
                    }

                    // Update counts in modal and header
                    updateNotificationCounts();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Update notification counts in header and modal
        function updateNotificationCounts() {
            fetch('<?php echo BASE_URL; ?>/admin/notifications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_notification_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update header badge
                    const bell = document.getElementById('notificationBell');
                    const countBadge = bell ? bell.querySelector('span') : null;
                    if (data.unread_count > 0) {
                        if (countBadge) {
                            countBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        } else {
                            const badge = document.createElement('span');
                            badge.className = 'absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-semibold';
                            badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                            if (bell) {
                                bell.appendChild(badge);
                            }
                        }
                        if (bell) {
                            bell.classList.add('bell-shake');
                        }
                    } else if (countBadge) {
                        countBadge.remove();
                        if (bell) {
                            bell.classList.remove('bell-shake');
                        }
                    }

                    // Update modal statistics if modal is open
                    const modal = document.getElementById('notificationModal');
                    if (!modal.classList.contains('hidden')) {
                        // Update total count
                        const totalElements = modal.querySelectorAll('[data-stat="total"]');
                        totalElements.forEach(el => el.textContent = data.total_count);

                        // Update read count
                        const readElements = modal.querySelectorAll('[data-stat="read"]');
                        readElements.forEach(el => el.textContent = data.read_count);

                        // Update unread count
                        const unreadElements = modal.querySelectorAll('[data-stat="unread"]');
                        unreadElements.forEach(el => el.textContent = data.unread_count);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
