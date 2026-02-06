<?php
// Use data from controller if available, otherwise use session or defaults
$resident_info = isset($resident_info) ? $resident_info : ($_SESSION['resident_info'] ?? ['name' => '', 'mobile' => '', 'email' => '', 'block' => '', 'flat' => '', 'floor' => '', 'occupancy_type' => '', 'since' => '']);
$recent_visitors = isset($recent_visitors) ? $recent_visitors : ($_SESSION['recent_visitors'] ?? []);
$stats = isset($stats) ? $stats : [
    ['name' => "Today's Visitors", 'value' => '0', 'icon' => 'fa-user-check', 'color' => 'bg-emerald-500'],
    ['name' => 'This Week', 'value' => '0', 'icon' => 'fa-calendar', 'color' => 'bg-emerald-500'],
    ['name' => 'This Month', 'value' => '0', 'icon' => 'fa-users', 'color' => 'bg-emerald-500'],
];
?>
<div class="space-y-6">
    <div><h1 class="text-3xl font-semibold text-slate-900">Welcome back, <?php echo htmlspecialchars($resident_info['name']); ?>!</h1><p class="text-slate-600 mt-1">Here's your dashboard overview</p></div>

    <!-- Resident Info Card -->
    <div class="bg-emerald-600 text-white rounded-xl p-6 border-0">
        <div class="flex items-start justify-between">
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                        <i class="nav-icon" data-lucide="Home" style="width: 2.5rem; height: 2.5rem;"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-semibold"><?php echo htmlspecialchars($resident_info['name']); ?></h2>
                        <p class="text-emerald-100">Resident since <?php echo date('F Y', strtotime($resident_info['since'])); ?></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div><p class="text-emerald-100 text-sm">Location</p><p class="font-semibold">Block <?php echo htmlspecialchars($resident_info['block']); ?>, Flat <?php echo htmlspecialchars($resident_info['flat']); ?></p></div>
                    <div><p class="text-emerald-100 text-sm">Floor</p><p class="font-semibold">Floor <?php echo htmlspecialchars($resident_info['floor']); ?></p></div>
                    <div><p class="text-emerald-100 text-sm">Mobile</p><p class="font-semibold"><?php echo htmlspecialchars($resident_info['mobile']); ?></p></div>
                    <div><p class="text-emerald-100 text-sm">Occupancy</p><p class="font-semibold capitalize"><?php echo htmlspecialchars($resident_info['occupancy_type']); ?></p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($stats as $stat): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium"><?php echo htmlspecialchars($stat['name']); ?></p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2"><?php echo htmlspecialchars($stat['value']); ?></h3>
                </div>
                <div class="w-12 h-12 rounded-xl <?php echo $stat['color']; ?> flex items-center justify-center">
                    <i class="nav-icon text-white" data-lucide="<?php echo $stat['icon']; ?>"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Visitors -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Recent Visitors</h2>
        </div>
        <div class="divide-y divide-slate-200">
            <?php if (!empty($recent_visitors)): ?>
            <?php foreach ($recent_visitors as $visitor): ?>
            <div class="flex items-center justify-between p-4 hover:bg-slate-50 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-600 rounded-full flex items-center justify-center text-white font-semibold">
                        <?php echo strtoupper(substr($visitor['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900"><?php echo htmlspecialchars($visitor['name']); ?></p>
                        <div class="flex items-center gap-1 text-sm text-slate-600 mt-1">
                            <i class="nav-icon" data-lucide="Phone"></i>
                            <?php echo htmlspecialchars($visitor['mobile'] ?? 'N/A'); ?>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="flex items-center gap-1 text-sm text-slate-600 mb-1">
                        <i class="nav-icon" data-lucide="Clock"></i>
                        <?php echo date('M d, h:i a', strtotime($visitor['check_in'])); ?>
                    </div>
                    <?php if ($visitor['check_out']): ?>
                    <div class="flex items-center gap-1 text-sm text-slate-500">
                        <span>Exit:</span>
                        <?php echo date('h:i a', strtotime($visitor['check_out'])); ?>
                    </div>
                    <?php endif; ?>
                    <span class="inline-block px-2 py-1 rounded text-xs font-medium text-slate-600 bg-slate-100 mt-2"><?php echo $visitor['check_out'] ? 'Checked Out' : 'Checked In'; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-8">
                <i class="nav-icon" data-lucide="Users" style="width: 3rem; height: 3rem; color: #cbd5e1;"></i>
                <p class="text-slate-600 mt-4">No recent visitors</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
            <h3 class="font-semibold text-slate-900 mb-2">Security Notice</h3>
            <p class="text-sm text-slate-600">All visitors are required to register at the main gate. Please ensure your guests provide valid ID proof.</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
            <h3 class="font-semibold text-slate-900 mb-2">Visitor Policy</h3>
            <p class="text-sm text-slate-600">Visitors must check out before 10 PM. For late-night visits, please inform the security in advance.</p>
        </div>
    </div>
</div>
