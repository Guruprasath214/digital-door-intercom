<?php
/**
 * Login Page View
 */
$active_tab = $_GET['tab'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Intercom - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-active {
            background-color: #0f172a;
            color: white;
        }
        .tab-inactive {
            background-color: #f1f5f9;
            color: #475569;
        }
        .tab-inactive:hover {
            background-color: #e2e8f0;
        }
        .input-field {
            padding-left: 2.5rem;
        }
        .input-icon {
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
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
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
    <!-- Toast Container (Sonner-like notifications) -->
    <div id="toast-container" class="toast-container"></div>
    
    <div class="w-full max-w-md">
        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <div class="mx-auto w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-xl mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">QR Intercom</h1>
            <p class="text-slate-600">Digital Door Management System</p>
        </div>

        <div class="bg-white shadow-xl border border-slate-200 rounded-lg">
            <div class="p-6 pb-4">
                <h2 class="text-2xl text-center font-semibold text-slate-900">Sign In</h2>
                <p class="text-center text-slate-600 mt-2">
                    Choose your access type to continue
                </p>
            </div>

            <div class="px-6">
                <!-- Tabs -->
                <div class="flex mb-6">
                    <a href="?tab=admin" class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-l-lg transition duration-200 <?php echo $active_tab === 'admin' ? 'tab-active' : 'tab-inactive'; ?>">
                        <i class="fas fa-shield-alt"></i>
                        Admin
                    </a>
                    <a href="?tab=resident" class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-r-lg transition duration-200 <?php echo $active_tab === 'resident' ? 'tab-active' : 'tab-inactive'; ?>">
                        <i class="fas fa-user"></i>
                        Resident
                    </a>
                </div>

                <!-- Display message -->
                <?php if (isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
                    <script>
                        // Show toast notification immediately
                        window.addEventListener('load', function() {
                            console.log('Showing toast:', '<?php echo $_SESSION['message_type']; ?>', '<?php echo addslashes($_SESSION['message']); ?>');
                            toast('<?php echo $_SESSION['message_type']; ?>', '<?php echo addslashes($_SESSION['message']); ?>');
                        });
                    </script>
                    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                <?php endif; ?>

                <!-- Admin Login -->
                <?php if ($active_tab === 'admin'): ?>
                    <form method="POST" class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-900">Email Address</label>
                            <div class="relative">
                                <i class="fas fa-envelope input-icon absolute"></i>
                                <input type="email" name="admin_email" placeholder="admin@qrintercom.com" class="input-field w-full pl-10 pr-4 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 placeholder:text-slate-400" required>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-900">Password</label>
                            <div class="relative">
                                <i class="fas fa-lock input-icon absolute"></i>
                                <input type="password" name="admin_password" placeholder="Enter your password" class="input-field w-full pl-10 pr-4 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 placeholder:text-slate-400" required>
                            </div>
                        </div>

                        <button type="submit" name="admin_login" value="1" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md transition duration-200 font-medium">
                            Sign In as Admin
                        </button>

                        <?php if (!empty($allow_admin_setup)): ?>
                            <div class="text-center">
                                <a href="<?php echo BASE_URL; ?>/setup-admin" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                    Create First Admin
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <p class="text-xs text-slate-600 text-center">
                                <strong>Demo:</strong> admin@qrintercom.com / admin123
                            </p>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Resident Login -->
                <?php if ($active_tab === 'resident'): ?>
                    <form method="POST" class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-900">Mobile Number</label>
                            <div class="relative">
                                <i class="fas fa-phone input-icon absolute"></i>
                                <input type="tel" name="user_mobile" placeholder="Enter your mobile number" class="input-field w-full pl-10 pr-4 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 placeholder:text-slate-400" required>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-900">Password</label>
                            <div class="relative">
                                <i class="fas fa-lock input-icon absolute"></i>
                                <input type="password" name="user_password" placeholder="Enter your password" class="input-field w-full pl-10 pr-4 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 placeholder:text-slate-400" required>
                            </div>
                        </div>

                        <button type="submit" name="user_login" value="1" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md transition duration-200 font-medium">
                            Sign In as Resident
                        </button>

                        <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <p class="text-xs text-slate-600 text-center">
                                <strong>Demo:</strong> 9876543210 / user123
                            </p>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="p-6 pt-4">
                <p class="text-center text-sm text-slate-600">
                    Secure access to your residential community
                </p>
            </div>
        </div>
    </div>
    
    <script>
        /**
         * Toast Notification System (Sonner-like)
         * Mimics React's Sonner library with top-right positioned toasts
         */
        function toast(type, message) {
            const container = document.getElementById('toast-container');
            console.log('Toast function called with type:', type, 'message:', message);
            if (!container) {
                console.error('Toast container not found');
                return;
            }
            
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
    </script>
</body>
</html>
