<?php
session_start();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_login'])) {
        $email = $_POST['admin_email'] ?? '';
        $password = $_POST['admin_password'] ?? '';
        if ($email === 'admin@qrintercom.com' && $password === 'admin123') {
            $_SESSION['message'] = 'Admin login successful!';
            $_SESSION['message_type'] = 'success';
            header('Location: /admin/dashboard');
            exit;
        } else {
            $_SESSION['message'] = 'Invalid admin credentials';
            $_SESSION['message_type'] = 'error';
        }
    } elseif (isset($_POST['user_login'])) {
        $mobile = $_POST['user_mobile'] ?? '';
        $password = $_POST['user_password'] ?? '';
        if ($mobile === '9876543210' && $password === 'user123') {
            $_SESSION['message'] = 'Resident login successful!';
            $_SESSION['message_type'] = 'success';
            header('Location: /user/dashboard');
            exit;
        } else {
            $_SESSION['message'] = 'Invalid resident credentials';
            $_SESSION['message_type'] = 'error';
        }
    }
}

// Get active tab from GET or default to admin
$activeTab = $_GET['tab'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Intercom - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            400: '#94a3b8',
                            600: '#475569',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .tab-active {
            background-color: #3b82f6 !important;
            color: white !important;
        }
        .tab-inactive {
            background-color: #f1f5f9;
            color: #475569;
        }
        .tab-inactive:hover {
            background-color: #e2e8f0;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <div class="mx-auto w-20 h-20 bg-slate-900 rounded-2xl flex items-center justify-center shadow-xl mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">QR Intercom</h1>
            <p class="text-slate-600">Digital Door Management System</p>
        </div>

        <div class="bg-white shadow-xl border border-slate-200 rounded-lg">
            <div class="p-6 pb-4">
                <h2 class="text-2xl text-center font-semibold">Sign In</h2>
                <p class="text-center text-slate-600 mt-2">
                    Choose your access type to continue
                </p>
            </div>

            <div class="px-6">
                <!-- Tabs -->
                <div class="flex mb-6 bg-blue-600 rounded-lg">
                    <a href="?tab=admin" class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-l-lg <?php echo $activeTab === 'admin' ? 'tab-active' : 'tab-inactive'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Admin
                    </a>
                    <a href="?tab=resident" class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-r-lg <?php echo $activeTab === 'resident' ? 'tab-active' : 'tab-inactive'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Resident
                    </a>
                </div>

                <!-- Display message -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="mb-4 p-3 rounded-lg <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                        <?php echo $_SESSION['message']; ?>
                    </div>
                    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                <?php endif; ?>

                <!-- Admin Login -->
                <?php if ($activeTab === 'admin'): ?>
                    <form method="POST" class="space-y-4">
                        <div class="space-y-2">
                            <label for="admin-email" class="block text-sm font-medium text-slate-700">Email Address</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <input
                                    id="admin-email"
                                    name="admin_email"
                                    type="email"
                                    placeholder="admin@qrintercom.com"
                                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-500"
                                    required
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="admin-password" class="block text-sm font-medium text-slate-700">Password</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <input
                                    id="admin-password"
                                    name="admin_password"
                                    type="password"
                                    placeholder="Enter your password"
                                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-500"
                                    required
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            name="admin_login"
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2 px-4 rounded-md transition duration-200"
                        >
                            Sign In as Admin
                        </button>

                        <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <p class="text-xs text-slate-600 text-center">
                                Demo: admin@qrintercom.com / admin123
                            </p>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Resident Login -->
                <?php if ($activeTab === 'resident'): ?>
                    <form method="POST" class="space-y-4">
                        <div class="space-y-2">
                            <label for="user-mobile" class="block text-sm font-medium text-slate-700">Mobile Number</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <input
                                    id="user-mobile"
                                    name="user_mobile"
                                    type="tel"
                                    placeholder="Enter your mobile number"
                                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-500"
                                    required
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="user-password" class="block text-sm font-medium text-slate-700">Password</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <input
                                    id="user-password"
                                    name="user_password"
                                    type="password"
                                    placeholder="Enter your password"
                                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-500"
                                    required
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            name="user_login"
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2 px-4 rounded-md transition duration-200"
                        >
                            Sign In as Resident
                        </button>

                        <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <p class="text-xs text-slate-600 text-center">
                                Demo: 9876543210 / user123
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
</body>
</html>
