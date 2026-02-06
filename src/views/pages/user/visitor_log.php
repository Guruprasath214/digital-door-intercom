<?php
// Use data from controller
$all_visitors = $visitors ?? [];
$period = $_GET['period'] ?? 'all';
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$source_filter = $_GET['source'] ?? 'all';
$query_params = array_filter([
    'search' => $search,
    'from_date' => $from_date,
    'to_date' => $to_date,
    'source' => $source_filter !== 'all' ? $source_filter : '',
], function($value) {
    return $value !== '';
});
$query_string = http_build_query($query_params);
$filtered_visitors = $all_visitors;
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('-7 days'));
$month_start = date('Y-m-d', strtotime('-30 days'));

// Check if no visitors list is available
$no_flat_assigned = empty($all_visitors) && isset($_SESSION['message']) && strpos($_SESSION['message'], 'flat assignment') !== false;

if ($period === 'today') {
    $filtered_visitors = array_filter($filtered_visitors, function($v) use ($today) {
        return substr($v['check_in'], 0, 10) === $today;
    });
} elseif ($period === 'week') {
    $filtered_visitors = array_filter($filtered_visitors, function($v) use ($week_start) {
        return substr($v['check_in'], 0, 10) >= $week_start;
    });
} elseif ($period === 'month') {
    $filtered_visitors = array_filter($filtered_visitors, function($v) use ($month_start) {
        return substr($v['check_in'], 0, 10) >= $month_start;
    });
}
if (!empty($search)) {
    $search_lower = strtolower($search);
    $filtered_visitors = array_filter($filtered_visitors, function($v) use ($search_lower) {
        return stripos($v['name'], $search_lower) !== false || stripos($v['mobile'], $search_lower) !== false || stripos($v['purpose'], $search_lower) !== false;
    });
}
$filtered_visitors = array_filter($filtered_visitors, function($v) use ($from_date, $to_date, $source_filter) {
    $check_in = $v['check_in'] ?? null;
    if (($from_date || $to_date) && empty($check_in)) {
        return false;
    }
    if ($check_in) {
        $check_in_date = date('Y-m-d', strtotime($check_in));
        if ($from_date && $check_in_date < $from_date) {
            return false;
        }
        if ($to_date && $check_in_date > $to_date) {
            return false;
        }
    }

    $source = !empty($v['appointment_id']) ? 'appointment' : 'visitor';
    if ($source_filter !== 'all' && $source_filter !== $source) {
        return false;
    }

    return true;
});
usort($filtered_visitors, function($a, $b) {
    return strtotime($b['check_in']) - strtotime($a['check_in']);
});
$total_today = count(array_filter($all_visitors, fn($v) => substr($v['check_in'], 0, 10) === $today));
$total_week = count(array_filter($all_visitors, fn($v) => substr($v['check_in'], 0, 10) >= $week_start));
$total_month = count(array_filter($all_visitors, fn($v) => substr($v['check_in'], 0, 10) >= $month_start));
?>
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900">Visitor Log</h1>
        <p class="text-slate-600 mt-1">View all visitor history and activities</p>
    </div>

    <?php if ($no_flat_assigned): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
            <div>
                <h3 class="font-semibold text-yellow-900">Flat Assignment Required</h3>
                <p class="text-yellow-800 text-sm mt-1">Your flat has not been assigned yet. Please contact the administrator to assign a flat to your account before you can view your visitor log.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php if (!$no_flat_assigned): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Today's Visitors</p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2"><?php echo $total_today; ?></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center">
                    <i class="fas fa-calendar-day w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">This Week</p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2"><?php echo $total_week; ?></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center">
                    <i class="fas fa-calendar-week w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">This Month</p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2"><?php echo $total_month; ?></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center">
                    <i class="fas fa-calendar w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <?php if (!$no_flat_assigned): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-900 mb-2">Filter by Period</label>
                <div class="flex gap-2 flex-wrap">
                    <?php $periods = ['all' => 'All Time', 'month' => 'This Month', 'week' => 'This Week', 'today' => 'Today']; ?>
                    <?php foreach ($periods as $key => $label): ?>
                    <a href="?period=<?php echo $key; ?><?php echo $query_string ? '&' . $query_string : ''; ?>" class="px-4 py-2 rounded-lg font-medium text-sm transition-all <?php echo $period === $key ? 'bg-emerald-600 text-white' : 'border border-slate-300 text-slate-900 hover:bg-slate-50'; ?>">
                        <?php echo $label; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-900 mb-2">Search</label>
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                    <?php if (!empty($from_date)): ?>
                    <input type="hidden" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    <?php endif; ?>
                    <?php if (!empty($to_date)): ?>
                    <input type="hidden" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                    <?php endif; ?>
                    <?php if ($source_filter !== 'all'): ?>
                    <input type="hidden" name="source" value="<?php echo htmlspecialchars($source_filter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, contact, or purpose..." class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-600">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-6 py-2 font-medium transition-all">
                        <i class="fas fa-search w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="button" onclick="openAdvancedFilter()" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-900 rounded-lg font-medium flex items-center gap-2 transition-colors">
                <i class="fas fa-sliders-h"></i> Advanced Filter
            </button>
        </div>
    </div>

    <!-- Advanced Filter Bottom Sheet -->
    <div id="advancedFilterOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 opacity-0 pointer-events-none transition-opacity duration-300" onclick="closeAdvancedFilter()"></div>
    <div id="advancedFilterSheet" class="fixed inset-x-0 bottom-0 z-50 transform translate-y-full opacity-0 transition-all duration-300 ease-out" onclick="event.stopPropagation()">
        <div class="px-4 pb-4">
            <div class="mx-auto w-full max-w-2xl bg-white rounded-t-2xl shadow-2xl border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Advanced Filter</h3>
                    <button type="button" onclick="closeAdvancedFilter()" class="text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">From Date</label>
                            <input type="date" id="fromDate" value="<?php echo htmlspecialchars($from_date); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">To Date</label>
                            <input type="date" id="toDate" value="<?php echo htmlspecialchars($to_date); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                        <select id="sourceFilter" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 shadow-sm appearance-none focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition">
                            <option value="all" <?php echo $source_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="visitor" <?php echo $source_filter === 'visitor' ? 'selected' : ''; ?>>Visitor</option>
                            <option value="appointment" <?php echo $source_filter === 'appointment' ? 'selected' : ''; ?>>Appointment</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="applyAdvancedFilters()" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg font-medium">Apply Filter</button>
                        <button type="button" onclick="clearAdvancedFilters()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium">Clear</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visitor List -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Visitor Name</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Contact</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Visit Date & Time</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Exit Date & Time</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Purpose</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($filtered_visitors)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                            <i class="fas fa-inbox w-12 h-12 mx-auto mb-3 opacity-50"></i>
                            <p>No visitors found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($filtered_visitors as $visitor): ?>
                        <?php 
                            $visit_time = new DateTime($visitor['check_in']);
                            $exit_time = new DateTime($visitor['check_out']);
                            $interval = $visit_time->diff($exit_time);
                            $duration = $interval->h . 'h ' . $interval->i . 'm';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                        <?php echo strtoupper(substr($visitor['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900"><?php echo htmlspecialchars($visitor['name']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($visitor['mobile'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <div><?php echo date('M d, Y', strtotime($visitor['check_in'])); ?></div>
                                <div class="text-xs text-slate-500"><?php echo date('h:i A', strtotime($visitor['check_in'])); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <div><?php echo date('M d, Y', strtotime($visitor['check_out'])); ?></div>
                                <div class="text-xs text-slate-500"><?php echo date('h:i A', strtotime($visitor['check_out'])); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold">
                                    <?php echo $duration; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function openAdvancedFilter() {
    const overlay = document.getElementById('advancedFilterOverlay');
    const sheet = document.getElementById('advancedFilterSheet');
    overlay.classList.remove('opacity-0', 'pointer-events-none');
    overlay.classList.add('opacity-100');
    sheet.classList.remove('translate-y-full', 'opacity-0');
    sheet.classList.add('translate-y-0', 'opacity-100');
}

function closeAdvancedFilter() {
    const overlay = document.getElementById('advancedFilterOverlay');
    const sheet = document.getElementById('advancedFilterSheet');
    overlay.classList.add('opacity-0', 'pointer-events-none');
    overlay.classList.remove('opacity-100');
    sheet.classList.add('translate-y-full', 'opacity-0');
    sheet.classList.remove('translate-y-0', 'opacity-100');
}

function applyAdvancedFilters() {
    const url = new URL(window.location);
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const source = document.getElementById('sourceFilter').value;

    if (fromDate) url.searchParams.set('from_date', fromDate); else url.searchParams.delete('from_date');
    if (toDate) url.searchParams.set('to_date', toDate); else url.searchParams.delete('to_date');
    if (source && source !== 'all') url.searchParams.set('source', source); else url.searchParams.delete('source');

    window.location = url.toString();
}

function clearAdvancedFilters() {
    document.getElementById('fromDate').value = '';
    document.getElementById('toDate').value = '';
    document.getElementById('sourceFilter').value = 'all';
}
</script>
