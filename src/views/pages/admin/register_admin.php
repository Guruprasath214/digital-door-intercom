<?php
/**
 * Admin Registration Page
 */
?>
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900">Register Admin</h1>
        <p class="text-slate-600 mt-1">Create a new admin account to access the admin panel.</p>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Admin Details</h2>
        </div>
        <div class="p-6">
            <form method="POST" action="<?php echo BASE_URL; ?>/admin/registerAdmin" class="space-y-6">
                <input type="hidden" name="register_admin" value="1" />

                <div>
                    <label for="admin_email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input
                        type="email"
                        id="admin_email"
                        name="admin_email"
                        required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="admin@example.com"
                    />
                </div>

                <div>
                    <label for="admin_password" class="block text-sm font-medium text-slate-700">Password</label>
                    <input
                        type="password"
                        id="admin_password"
                        name="admin_password"
                        required
                        minlength="6"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="••••••••"
                    />
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        minlength="6"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="••••••••"
                    />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="reset" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Clear
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                        Register Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
