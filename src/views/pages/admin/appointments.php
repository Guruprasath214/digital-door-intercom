<?php
// Use appointments data from controller (fetched from database)
$appointments = $appointments ?? [];
$search_term = $_GET['search'] ?? ''; 
$filter_date = $_GET['filter'] ?? 'all';
$today = date('Y-m-d');

// Filter appointments based on search and date
$filtered = array_filter($appointments, function($a) use ($search_term, $filter_date, $today) {
    $match = empty($search_term) || 
             stripos($a['visitor_name'] ?? '', $search_term) !== false || 
             stripos($a['resident_name'] ?? '', $search_term) !== false ||
             stripos($a['visitor_contact'] ?? '', $search_term) !== false;
    
    if (!$match) return false;
    
    $apt_date = date('Y-m-d', strtotime($a['appointment_time']));
    if ($filter_date === 'today') return $apt_date === $today;
    if ($filter_date === 'upcoming') return $apt_date >= $today && $a['status'] !== 'completed';
    return true;
});

// Calculate statistics
$today_appointments = array_filter($appointments, function($a) use ($today) {
    return date('Y-m-d', strtotime($a['appointment_time'])) === $today;
});
$today_count = count($today_appointments);
$pending_count = count(array_filter($today_appointments, fn($a) => $a['status'] === 'pending'));
$inside_count = count(array_filter($appointments, fn($a) => $a['status'] === 'checked_in'));
$completed_today = count(array_filter($today_appointments, fn($a) => $a['status'] === 'completed'));
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-3xl font-semibold text-slate-900">Appointments Management</h1><p class="text-slate-600 mt-1">Manage visitor appointments and check-ins</p></div>
        <div class="flex gap-2">
            <button onclick="openScanDialog('checkin')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                <i class="fas fa-qrcode"></i> Scan Check-In
            </button>
            <button onclick="openScanDialog('checkout')" class="border border-slate-300 hover:bg-slate-50 text-slate-900 px-4 py-2 rounded-lg transition flex items-center gap-2">
                <i class="fas fa-qrcode"></i> Scan Check-Out
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-slate-900 flex items-center justify-center"><i class="fas fa-calendar w-6 h-6 text-white"></i></div>
                <div><p class="text-sm text-slate-600">Today's Appointments</p><h3 class="text-2xl font-bold text-slate-900"><?php echo $today_count; ?></h3></div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center"><i class="fas fa-clock w-6 h-6 text-white"></i></div>
                <div><p class="text-sm text-slate-600">Pending</p><h3 class="text-2xl font-bold text-slate-900"><?php echo $pending_count; ?></h3></div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center"><i class="fas fa-user w-6 h-6 text-white"></i></div>
                <div><p class="text-sm text-slate-600">Currently Inside</p><h3 class="text-2xl font-bold text-slate-900"><?php echo $inside_count; ?></h3></div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-emerald-500 flex items-center justify-center"><i class="fas fa-check w-6 h-6 text-white"></i></div>
                <div><p class="text-sm text-slate-600">Completed Today</p><h3 class="text-2xl font-bold text-slate-900"><?php echo $completed_today; ?></h3></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" id="searchInput" placeholder="Search by visitor, contact or resident..." value="<?php echo htmlspecialchars($search_term); ?>" onkeyup="applyFilters()" class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <select id="filterSelect" onchange="applyFilters()" class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="today" <?php echo $filter_date === 'today' ? 'selected' : ''; ?>>Today's Appointments</option>
                <option value="upcoming" <?php echo $filter_date === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                <option value="all" <?php echo $filter_date === 'all' ? 'selected' : ''; ?>>All Appointments</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-900">Appointments (<?php echo count($filtered); ?>)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Visitor</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Contact</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Visiting</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Location</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Schedule</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Status</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($filtered) > 0): ?>
                        <?php foreach ($filtered as $apt): ?>
                        <tr class="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-sm font-semibold">
                                        <?php echo strtoupper(substr($apt['visitor_name'], 0, 1)); ?>
                                    </div>
                                    <span class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($apt['visitor_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1 text-sm text-slate-600">
                                    <i class="fas fa-phone w-3 h-3"></i>
                                    <?php echo htmlspecialchars($apt['visitor_contact']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4"><p class="text-sm text-slate-900"><?php echo htmlspecialchars($apt['resident_name']); ?></p></td>
                            <td class="px-6 py-4"><p class="text-sm text-slate-600"><?php echo htmlspecialchars($apt['block_name'] ?? '-'); ?> Block, Flat <?php echo htmlspecialchars($apt['flat_number'] ?? '-'); ?></p></td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-600">
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-calendar w-3 h-3"></i>
                                        <?php echo date('M d', strtotime($apt['appointment_time'])); ?>
                                    </div>
                                    <div class="flex items-center gap-1 mt-1">
                                        <i class="fas fa-clock w-3 h-3"></i>
                                        <?php echo date('H:i', strtotime($apt['appointment_time'])); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium <?php 
                                    if ($apt['status'] === 'pending') echo 'bg-yellow-100 text-yellow-700';
                                    elseif ($apt['status'] === 'checked_in') echo 'bg-blue-100 text-blue-700';
                                    elseif ($apt['status'] === 'completed') echo 'bg-green-100 text-green-700';
                                    else echo 'bg-slate-100 text-slate-700';
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($apt['status'] === 'pending'): ?>
                                <button onclick="manualCheckIn(<?php echo $apt['id']; ?>)" class="px-3 py-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2 whitespace-nowrap">
                                    <i class="fas fa-sign-in-alt text-sm"></i> <span>Check In</span>
                                </button>
                                <?php elseif ($apt['status'] === 'checked_in'): ?>
                                <button onclick="manualCheckOut(<?php echo $apt['id']; ?>)" class="px-3 py-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2 whitespace-nowrap">
                                    <i class="fas fa-sign-out-alt text-sm"></i> <span>Check Out</span>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-center">
                                    <i class="fas fa-calendar text-5xl text-slate-300 mb-4 block"></i>
                                    <p class="text-slate-600 mt-4">No appointments found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- QR Scan Modal -->
<div id="scanModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="bg-slate-900 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 id="scanTitle" class="text-xl font-semibold text-white">Scan QR Code</h2>
            <button type="button" onclick="closeScanModal()" class="text-slate-400 hover:text-slate-200"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4" onsubmit="handleScanSubmit(event)">
            <input type="hidden" name="action" id="scanAction" value="">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Enter QR Code or Appointment ID</label>
                <input type="text" name="qr_data" id="qrInput" placeholder="Scan or enter code..." required autofocus class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg font-medium transition"><i class="fas fa-check mr-2"></i>Check In Visitor</button>
                <button type="button" onclick="closeScanModal()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openScanDialog(type) { 
    const actionValue = type === 'checkin' ? 'scan_checkin' : 'scan_checkout';
    const titleText = type === 'checkin' ? 'Scan QR Code for Check-In' : 'Scan QR Code for Check-Out';
    const buttonText = type === 'checkin' ? '<i class="fas fa-check mr-2"></i>Check In Visitor' : '<i class="fas fa-times mr-2"></i>Check Out Visitor';
    
    document.getElementById('scanAction').value = actionValue; 
    document.getElementById('scanTitle').textContent = titleText;
    document.querySelector('form button[type="submit"]').innerHTML = buttonText;
    document.getElementById('qrInput').value = ''; 
    document.getElementById('scanModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('qrInput').focus(), 100);
}

function closeScanModal() { 
    document.getElementById('scanModal').classList.add('hidden'); 
}

function handleScanSubmit(event) {
    event.preventDefault();
    const qrInput = document.getElementById('qrInput').value.trim();
    const action = document.getElementById('scanAction').value;
    
    if (!qrInput) {
        toast('Please enter QR code', 'error');
        return false;
    }
    
    // Show confirmation for checkout with appropriate button
    if (action === 'scan_checkout') {
        showConfirmation('Visitor will be marked as checked out.', 'Check Out Visitor?', () => {
            event.target.submit();
        }, 'Check Out', 'red');
        return false;
    }
    
    // Show confirmation for check-in with appropriate button
    if (action === 'scan_checkin') {
        showConfirmation('Visitor will be checked in successfully.', 'Check In Visitor?', () => {
            event.target.submit();
        }, 'Check In', 'green');
        return false;
    }
    
    event.target.submit();
}

function manualCheckIn(id) { 
    showConfirmation('Visitor will be checked in successfully.', 'Check In Visitor?', () => {
        const form = document.createElement('form'); 
        form.method='POST'; 
        form.innerHTML='<input type="hidden" name="action" value="manual_checkin"><input type="hidden" name="id" value="'+id+'">'; 
        document.body.appendChild(form); 
        form.submit();
    }, 'Check In', 'green');
}

function manualCheckOut(id) { 
    showConfirmation('Visitor will be marked as checked out.', 'Check Out Visitor?', () => {
        const form = document.createElement('form'); 
        form.method='POST'; 
        form.innerHTML='<input type="hidden" name="action" value="manual_checkout"><input type="hidden" name="id" value="'+id+'">'; 
        document.body.appendChild(form); 
        form.submit();
    }, 'Check Out', 'red');
}

function applyFilters() { 
    const search = document.getElementById('searchInput').value; 
    const filter = document.getElementById('filterSelect').value; 
    const url = new URL(window.location); 
    url.searchParams.set('search', search); 
    url.searchParams.set('filter', filter); 
    window.location = url.toString(); 
}
</script>
