<?php
/**
 * Create First Admin (Public Setup)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Intercom - Create First Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="text-center mb-6">
                <div class="w-12 h-12 rounded-lg bg-indigo-600 text-white flex items-center justify-center mx-auto">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="text-2xl font-semibold text-slate-900 mt-3">Create First Admin</h1>
                <p class="text-sm text-slate-600 mt-1">Set up the first admin account for QR Intercom.</p>
            </div>

            <?php if (isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
                <div class="mb-4 text-sm <?php echo $_SESSION['message_type'] === 'success' ? 'text-green-700 bg-green-50 border-green-200' : 'text-red-700 bg-red-50 border-red-200'; ?> border rounded-lg p-3">
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/setup-admin" class="space-y-4">
                <input type="hidden" name="register_admin" value="1" />

                <div>
                    <label for="admin_email" class="block text-sm font-medium text-slate-700">Email</label>
                    <div class="relative mt-2">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="email"
                            id="admin_email"
                            name="admin_email"
                            required
                            class="w-full rounded-lg border border-slate-300 pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="admin@example.com"
                        />
                    </div>
                </div>

                <div>
                    <label for="admin_password" class="block text-sm font-medium text-slate-700">Password</label>
                    <div class="relative mt-2">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="password"
                            id="admin_password"
                            name="admin_password"
                            required
                            minlength="6"
                            class="w-full rounded-lg border border-slate-300 pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="••••••••"
                        />
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                    <div class="relative mt-2">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            minlength="6"
                            class="w-full rounded-lg border border-slate-300 pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="••••••••"
                        />
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md transition duration-200 font-medium">
                    Create Admin Account
                </button>

                <div class="text-center">
                    <a href="<?php echo BASE_URL; ?>/login" class="text-sm text-slate-600 hover:text-slate-800">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
