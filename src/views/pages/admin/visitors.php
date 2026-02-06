<?php
// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Capture flash message and clear it immediately
$flashMessage = $_SESSION['flash_message'] ?? null;
if ($flashMessage) {
    unset($_SESSION['flash_message']);
}

// Use database data from controller if available, otherwise use session data
$visitors = $visitors ?? $_SESSION['visitors'] ?? [];
$flats = $flats ?? [];
$search_term = $_GET['search'] ?? '';
$filter_status = $_GET['filter'] ?? 'all';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$filter_block = $_GET['block'] ?? '';
$filter_floor = $_GET['floor'] ?? '';
$filter_flat = $_GET['flat'] ?? '';
$filter_source = $_GET['source'] ?? 'all';

$blocks = [];
$floors = [];
foreach ($flats as $flat) {
    if (!empty($flat['block_name'])) {
        $blocks[$flat['block_name']] = true;
    }
    if (isset($flat['floor'])) {
        $floors[(string)$flat['floor']] = true;
    }
}
$blocks = array_keys($blocks);
sort($blocks);
$floors = array_keys($floors);
sort($floors, SORT_NUMERIC);

// Filter visitors based on search and status
$filtered = array_filter($visitors, function($v) use ($search_term, $filter_status, $from_date, $to_date, $filter_block, $filter_floor, $filter_flat, $filter_source) {
    $matches_search = empty($search_term) ||
        stripos($v['name'] ?? '', $search_term) !== false ||
        stripos($v['mobile'] ?? '', $search_term) !== false ||
        stripos($v['purpose'] ?? '', $search_term) !== false ||
        stripos($v['resident_name'] ?? '', $search_term) !== false;

    $matches_filter = $filter_status === 'all' ||
        ($filter_status === 'active' && empty($v['check_out'])) ||
        ($filter_status === 'checked_out' && !empty($v['check_out']));

    $check_in = $v['check_in'] ?? null;
    if (($from_date || $to_date) && empty($check_in)) {
        return false;
    }
    $check_in_date = $check_in ? date('Y-m-d', strtotime($check_in)) : null;
    $matches_date = true;
    if ($from_date && $check_in_date) {
        $matches_date = $check_in_date >= $from_date;
    }
    if ($matches_date && $to_date && $check_in_date) {
        $matches_date = $check_in_date <= $to_date;
    }

    $matches_block = empty($filter_block) || ($v['block_name'] ?? '') === $filter_block;
    $matches_floor = $filter_floor === '' || (string)($v['floor'] ?? '') === (string)$filter_floor;
    $matches_flat = empty($filter_flat) || (string)($v['flat_id'] ?? '') === (string)$filter_flat;
    $source = !empty($v['appointment_id']) ? 'appointment' : 'visitor';
    $matches_source = $filter_source === 'all' || $filter_source === $source;

    return $matches_search && $matches_filter && $matches_date && $matches_block && $matches_floor && $matches_flat && $matches_source;
});


?>
<div class="flex flex-col h-full gap-4">
    <div class="flex items-center justify-between">
        <div><h1 class="text-3xl font-semibold text-slate-900">Visitors Management</h1><p class="text-slate-600 mt-1">Track and manage all visitor entries</p></div>
        <button onclick="openAddDialog()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-all">
            <i class="fas fa-plus"></i> Add Visitor
        </button>
    </div>



    <!-- Filters -->
    <!-- Filters & Export -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="space-y-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, contact, or resident..." value="<?php echo htmlspecialchars($search_term); ?>" onkeyup="applyFilters()" class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <select id="filterSelect" onchange="applyFilters()" class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="all">All Status</option>
                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Currently Inside</option>
                    <option value="checked_out" <?php echo $filter_status === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                </select>
            </div>
            
            <!-- Advanced Filter & Export Buttons -->
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <button type="button" onclick="openAdvancedFilter()" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-900 rounded-lg font-medium flex items-center gap-2 transition-colors">
                        <i class="fas fa-sliders-h"></i> Advanced Filter
                    </button>
                </div>

                <!-- Export Buttons -->
                <div class="flex gap-2">
                    <button onclick="exportData('pdf')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button onclick="exportData('excel')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filter Bottom Sheet -->
    <div id="advancedFilterOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 opacity-0 pointer-events-none transition-opacity duration-300" onclick="closeAdvancedFilter()"></div>
    <div id="advancedFilterSheet" class="fixed inset-x-0 bottom-0 z-50 transform translate-y-full opacity-0 transition-all duration-300 ease-out" onclick="event.stopPropagation()">
        <div class="px-4 pb-4">
            <div class="mx-auto w-full max-w-3xl bg-white rounded-t-2xl shadow-2xl border border-slate-200">
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
                        <input type="date" id="fromDate" value="<?php echo htmlspecialchars($from_date); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">To Date</label>
                        <input type="date" id="toDate" value="<?php echo htmlspecialchars($to_date); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Block</label>
                        <select id="blockFilter" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 shadow-sm appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">All Blocks</option>
                            <?php foreach ($blocks as $block): ?>
                            <option value="<?php echo htmlspecialchars($block); ?>" <?php echo $filter_block === $block ? 'selected' : ''; ?>><?php echo htmlspecialchars($block); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Floor</label>
                        <select id="floorFilter" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 shadow-sm appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">All Floors</option>
                            <?php foreach ($floors as $floor): ?>
                            <option value="<?php echo htmlspecialchars($floor); ?>" <?php echo (string)$filter_floor === (string)$floor ? 'selected' : ''; ?>><?php echo htmlspecialchars($floor); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Flat</label>
                        <select id="flatFilter" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 shadow-sm appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">All Flats</option>
                            <?php foreach ($flats as $flat): ?>
                            <option value="<?php echo htmlspecialchars($flat['id']); ?>" data-block="<?php echo htmlspecialchars($flat['block_name']); ?>" data-floor="<?php echo htmlspecialchars($flat['floor']); ?>" <?php echo (string)$filter_flat === (string)$flat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($flat['block_name'] . ' - Floor ' . $flat['floor'] . ' - Flat ' . $flat['number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                    <select id="sourceFilter" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 shadow-sm appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="all" <?php echo $filter_source === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="visitor" <?php echo $filter_source === 'visitor' ? 'selected' : ''; ?>>Visitor</option>
                        <option value="appointment" <?php echo $filter_source === 'appointment' ? 'selected' : ''; ?>>Appointment</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="applyAdvancedFilters()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-medium">Apply Filter</button>
                    <button type="button" onclick="clearAdvancedFilters()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium">Clear</button>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm flex-1 min-h-0 flex flex-col">
        <div class="overflow-x-auto overflow-y-auto flex-1">
            <table class="w-full relative border-collapse table-auto">
                <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-3 text-left text-base font-semibold text-slate-700">Visitor</th>
                        <th class="px-3 py-3 text-left text-base font-semibold text-slate-700">Contact</th>
                        <th class="px-3 py-3 text-left text-base font-semibold text-slate-700">Purpose</th>
                        <th class="px-3 py-3 text-left text-base font-semibold text-slate-700">Location</th>
                        <th class="px-3 py-3 text-left text-base font-semibold text-slate-700">Entry Time</th>
                        <th class="px-3 py-3 text-left text-base font-semibold text-slate-700">Exit Time</th>
                        <th class="px-3 py-3 text-left text-base font-semibold text-slate-700">Status</th>
                        <th class="px-3 py-3 text-center text-base font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody id="visitorTableBody">
                    <?php foreach ($filtered as $visitor): ?>
                    <tr class="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white text-base font-semibold flex-shrink-0"><?php echo strtoupper(substr($visitor['name'], 0, 1)); ?></div>
                                <p class="font-medium text-slate-900 text-base" title="<?php echo htmlspecialchars($visitor['name']); ?>"><?php echo htmlspecialchars($visitor['name']); ?></p>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-base font-medium text-slate-900"><?php echo htmlspecialchars($visitor['mobile']); ?></td>
                        <td class="px-3 py-3 text-base text-slate-600"><div class="whitespace-normal" title="<?php echo htmlspecialchars($visitor['purpose'] ?? 'N/A'); ?>"><?php echo htmlspecialchars($visitor['purpose'] ?? 'N/A'); ?></div></td>
                        <td class="px-3 py-3 text-base text-slate-600"><div class="whitespace-normal" title="<?php echo htmlspecialchars($visitor['block_name'] . ' - Flat ' . $visitor['flat_number']); ?>"><?php echo htmlspecialchars($visitor['block_name'] . ' - Flat ' . $visitor['flat_number']); ?></div></td>
                        <td class="px-3 py-3 text-base text-slate-600 whitespace-nowrap"><?php echo $visitor['check_in'] ? date('M d, H:i', strtotime($visitor['check_in'])) : 'N/A'; ?></td>
                        <td class="px-3 py-3 text-base text-slate-600 whitespace-nowrap"><?php echo $visitor['check_out'] ? date('M d, H:i', strtotime($visitor['check_out'])) : '-'; ?></td>
                        <td class="px-3 py-3"><span class="inline-block px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap <?php echo empty($visitor['check_out']) ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700'; ?>"><?php echo empty($visitor['check_out']) ? 'Inside' : 'Checked Out'; ?></span></td>
                        <td class="px-3 py-3 text-center">
                            <?php if (empty($visitor['check_out'])): ?>
                            <button onclick="checkOut(<?php echo $visitor['id']; ?>)" class="px-3 py-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2 whitespace-nowrap">
                                <i class="fas fa-sign-out-alt text-sm"></i> <span class="hidden xl:inline">Check Out</span><span class="xl:hidden">Out</span>
                            </button>
                            <?php else: ?>
                            <span class="text-slate-500 text-sm">Out</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($filtered)): ?>
                    <tr id="noResultsRow">
                        <td colspan="8" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="fas fa-users text-5xl mb-4 opacity-50"></i>
                                <p class="text-slate-600">No visitors found</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Visitor Modal -->
<div id="addEditModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4">
        <div class="bg-indigo-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Add Visitor</h2>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form id="visitorForm" method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action" value="add">
            
            <!-- Visitor Information Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Visitor *</label>
                    <input type="text" name="name" placeholder="Enter visitor name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Contact *</label>
                    <input type="tel" name="mobile" placeholder="Enter mobile number" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <!-- Purpose Row -->
            <div><label class="block text-sm font-medium text-slate-700 mb-2">Purpose *</label>
                <input type="text" name="purpose" placeholder="Enter visit purpose" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <!-- Resident and Location Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Resident</label>
                    <select name="resident_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Resident (Optional)</option>
                        <?php foreach ($residents as $resident): ?>
                        <option value="<?php echo $resident['id']; ?>"><?php echo htmlspecialchars($resident['name'] . ($resident['block_name'] ? ' - ' . $resident['block_name'] . ' Flat ' . $resident['flat_number'] : '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Location *</label>
                    <select name="location_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Location</option>
                        <?php foreach ($flats as $flat): ?>
                        <option value="<?php echo $flat['id']; ?>"><?php echo htmlspecialchars($flat['block_name'] . ' - Flat ' . $flat['number'] . ($flat['resident_name'] ? ' (' . $flat['resident_name'] . ')' : ' (Vacant)')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Time Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Entry Time</label>
                    <input type="datetime-local" name="entry_time" value="<?php echo date('Y-m-d\TH:i'); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Exit Time</label>
                    <input type="datetime-local" name="exit_time" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            
            <!-- Buttons Row -->
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-medium">Add Visitor</button>
                <button type="button" onclick="closeModal()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Display flash message on page load
document.addEventListener('DOMContentLoaded', function() {
    const flashData = <?php echo json_encode($flashMessage); ?>;
    if (flashData && flashData.type && flashData.text) {
        toast(flashData.type, flashData.text);
    }
});

function openAddDialog() { 
    document.getElementById('visitorForm').reset();
    document.getElementById('addEditModal').classList.remove('hidden'); 
}
function closeModal() { 
    document.getElementById('addEditModal').classList.add('hidden');
    document.getElementById('visitorForm').reset();
}
function checkOut(id) { 
    showConfirmation('Visitor will be marked as checked out.', 'Check Out Visitor?', function() { 
        const form = document.createElement('form'); 
        form.method='POST'; 
        form.innerHTML='<input type="hidden" name="action" value="checkout"><input type="hidden" name="id" value="'+id+'">'; 
        document.body.appendChild(form); 
        form.submit(); 
    }, 'Check Out', 'red');
}
function applyFilters() { 
    const search = document.getElementById('searchInput').value; 
    const filter = document.getElementById('filterSelect').value; 
    
    // Update URL without page reload
    const url = new URL(window.location); 
    url.searchParams.set('search', search); 
    url.searchParams.set('filter', filter);
    window.history.pushState({}, '', url.toString());
    
    // Filter table rows client-side
    const tableRows = document.querySelectorAll('#visitorTableBody tr:not(#noResultsRow)');
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        // Get text content from each column
        const name = (row.querySelector('td:nth-child(1) p')?.textContent || '').toLowerCase();
        const mobile = (row.querySelector('td:nth-child(2)')?.textContent || '').toLowerCase();
        const purpose = (row.querySelector('td:nth-child(3)')?.textContent || '').toLowerCase();
        const statusBadge = row.querySelector('td:nth-child(7) span');
        const isActive = statusBadge?.textContent?.trim() === 'Inside';
        
        const searchLower = search.toLowerCase();
        const matchesSearch = !search || 
            name.includes(searchLower) || 
            mobile.includes(searchLower) || 
            purpose.includes(searchLower);
        
        const matchesFilter = filter === 'all' || 
            (filter === 'active' && isActive) || 
            (filter === 'checked_out' && !isActive);
        
        if (matchesSearch && matchesFilter) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update "no results" message
    updateNoResultsMessage(visibleCount);
}

function updateNoResultsMessage(visibleCount) {
    let noResultsRow = document.getElementById('noResultsRow');
    
    if (visibleCount === 0) {
        // Show no results message
        if (!noResultsRow) {
            // Create the row if it doesn't exist
            const tbody = document.getElementById('visitorTableBody');
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsRow';
            noResultsRow.innerHTML = `
                <td colspan="8" class="text-center py-12">
                    <div class="flex flex-col items-center justify-center text-slate-400">
                        <i class="fas fa-users text-5xl mb-4 opacity-50"></i>
                        <p class="text-slate-600">No visitors found</p>
                    </div>
                </td>
            `;
            tbody.appendChild(noResultsRow);
        } else {
            noResultsRow.style.display = '';
        }
    } else {
        // Hide no results message
        if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
}

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
    const block = document.getElementById('blockFilter').value;
    const floor = document.getElementById('floorFilter').value;
    const flat = document.getElementById('flatFilter').value;
    const source = document.getElementById('sourceFilter').value;

    if (fromDate) url.searchParams.set('from_date', fromDate); else url.searchParams.delete('from_date');
    if (toDate) url.searchParams.set('to_date', toDate); else url.searchParams.delete('to_date');
    if (block) url.searchParams.set('block', block); else url.searchParams.delete('block');
    if (floor) url.searchParams.set('floor', floor); else url.searchParams.delete('floor');
    if (flat) url.searchParams.set('flat', flat); else url.searchParams.delete('flat');
    if (source && source !== 'all') url.searchParams.set('source', source); else url.searchParams.delete('source');

    url.searchParams.delete('start_date');
    url.searchParams.delete('end_date');

    window.location = url.toString();
}

function clearAdvancedFilters() {
    document.getElementById('fromDate').value = '';
    document.getElementById('toDate').value = '';
    document.getElementById('blockFilter').value = '';
    document.getElementById('floorFilter').value = '';
    document.getElementById('flatFilter').value = '';
    document.getElementById('sourceFilter').value = 'all';
    updateFlatOptions();
}

function updateFlatOptions() {
    const block = document.getElementById('blockFilter').value;
    const floor = document.getElementById('floorFilter').value;
    const flatSelect = document.getElementById('flatFilter');
    const options = flatSelect.querySelectorAll('option[data-block]');

    options.forEach(option => {
        const matchBlock = !block || option.dataset.block === block;
        const matchFloor = !floor || option.dataset.floor === floor;
        option.hidden = !(matchBlock && matchFloor);
    });

    const selected = flatSelect.options[flatSelect.selectedIndex];
    if (selected && selected.hidden) {
        flatSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const blockFilter = document.getElementById('blockFilter');
    const floorFilter = document.getElementById('floorFilter');
    if (blockFilter && floorFilter) {
        blockFilter.addEventListener('change', updateFlatOptions);
        floorFilter.addEventListener('change', updateFlatOptions);
        updateFlatOptions();
    }
});

function exportData(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    const exportUrl = window.location.pathname + '?' + params.toString();
    window.open(exportUrl, '_blank');
    toast('success', 'Exporting to ' + format.toUpperCase() + '...');
}
</script>
