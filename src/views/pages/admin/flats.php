<?php
// Use database data from controller
$blocks = $blocks ?? [];
$flats = $flats ?? [];
$floors_by_block = $floors_by_block ?? [];
$search_term = $_GET['search'] ?? '';
$filter_block = $_GET['filter'] ?? 'all';

// Filter flats based on search and block filter
$filtered_flats = array_filter($flats, function($flat) use ($search_term, $filter_block) {
    $matches_search = empty($search_term) ||
        stripos($flat['number'] ?? '', $search_term) !== false ||
        stripos($flat['block_name'] ?? '', $search_term) !== false;

    $matches_block = $filter_block === 'all' || ($flat['block_name'] ?? '') === $filter_block;

    return $matches_search && $matches_block;
});
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">Flats Management</h1>
            <p class="text-slate-600 mt-1">Manage all flats across blocks</p>
        </div>
        <button onclick="openAddDialog()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-all">
            <i class="fas fa-plus"></i> Add Flat
        </button>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" id="searchInput" placeholder="Search by flat or block..." value="<?php echo htmlspecialchars($search_term); ?>" onkeyup="applyFilters()" class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <select id="filterSelect" onchange="applyFilters()" class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="all">All Blocks</option>
                <?php foreach ($blocks as $block): ?>
                <option value="<?php echo htmlspecialchars($block['name']); ?>" <?php echo $filter_block === $block['name'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($block['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php if (empty($filtered_flats)): ?>
        <div class="col-span-full bg-white rounded-xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-home text-2xl text-slate-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">No flats found</h3>
            <p class="text-slate-600">Add your first flat or adjust your search criteria.</p>
        </div>
        <?php else: ?>
        <?php foreach ($filtered_flats as $flat): ?>
        <div class="bg-white rounded-xl border border-slate-200 hover:shadow-lg transition-shadow p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-home w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900"><?php echo htmlspecialchars($flat['block_name']); ?> - <?php echo htmlspecialchars($flat['number']); ?></h3>
                        <p class="text-xs text-slate-600">Floor <?php echo htmlspecialchars($flat['floor']); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $flat['occupied'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                        <?php echo $flat['occupied'] ? 'Occupied' : 'Vacant'; ?>
                    </span>
                </div>
            </div>
            <div class="space-y-2 mb-4 pb-4 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Floor</span>
                    <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($flat['floor']); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Status</span>
                    <span class="font-semibold <?php echo $flat['occupied'] ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo $flat['occupied'] ? 'Occupied' : 'Vacant'; ?>
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="editFlat(<?php echo htmlspecialchars(json_encode($flat)); ?>)" class="flex-1 text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button onclick="deleteFlat(<?php echo $flat['id']; ?>)" class="flex-1 text-red-600 hover:bg-red-50 px-3 py-2 rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="addEditModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="bg-indigo-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 id="modalTitle" class="text-xl font-semibold text-slate-900">Add New Flat</h2>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="add_flat" value="1">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Block *</label>
                <select name="block_id" id="blockSelect" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="updateFloors()">
                    <option value="">Select Block</option>
                    <?php foreach ($blocks as $block): ?>
                    <option value="<?php echo htmlspecialchars($block['id']); ?>"><?php echo htmlspecialchars($block['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Floor Number *</label>
                <select name="floor" id="floorSelect" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Select Block First</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Flat Number *</label>
                <input type="text" name="flat_number" placeholder="101" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-medium">Add Flat</button>
                <button type="button" onclick="closeModal()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Floor data from PHP
const floorsByBlock = <?php echo json_encode($floors_by_block); ?>;

function updateFloors() {
    const blockSelect = document.getElementById('blockSelect');
    const floorSelect = document.getElementById('floorSelect');
    const selectedBlockId = blockSelect.value;

    // Clear current floor options
    floorSelect.innerHTML = '<option value="">Select Floor</option>';

    if (selectedBlockId && floorsByBlock[selectedBlockId]) {
        floorsByBlock[selectedBlockId].forEach(floor => {
            const option = document.createElement('option');
            option.value = floor;
            option.textContent = 'Floor ' + floor;
            floorSelect.appendChild(option);
        });
    } else {
        floorSelect.innerHTML = '<option value="">No floors available</option>';
    }
}

function openAddDialog() { document.getElementById('addEditModal').classList.remove('hidden'); }
function closeModal() { document.getElementById('addEditModal').classList.add('hidden'); }
function editFlat(flat) { toast('info', 'Edit flat: ' + flat.number); }
function deleteFlat(id) {
    showConfirmation('This action cannot be undone.', 'Delete Flat?', function() {
        const form = document.createElement('form');
        form.method='POST';
        form.innerHTML='<input type="hidden" name="delete_flat" value="1"><input type="hidden" name="flat_id" value="'+id+'">';
        document.body.appendChild(form);
        form.submit();
    });
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
