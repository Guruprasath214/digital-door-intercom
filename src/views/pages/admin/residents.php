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

// Use controller data (from database) instead of session
// The controller passes $residents and $flats from database query
if (!isset($residents)) {
    $residents = [];
}

// Available blocks and floors from controller
$blocks = $blocks ?? [];
$floors_by_block = $floors_by_block ?? [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_resident'])) {
        // This is handled by controller, no need to process here
        // Just redirect back
        header('Location: ' . BASE_URL . '/admin/residents');
        exit;
    }
}

// Search and filter
$search_term = $_GET['search'] ?? '';
$filter_type = $_GET['filter'] ?? 'all';
$filtered = array_filter($residents, function($r) use ($search_term, $filter_type) {
    $match_search = empty($search_term) || 
        stripos($r['name'], $search_term) !== false || 
        stripos($r['mobile'], $search_term) !== false || 
        (isset($r['email']) && stripos($r['email'], $search_term) !== false) ||
        (isset($r['flat_number']) && stripos($r['flat_number'], $search_term) !== false);
    $match_type = $filter_type === 'all' || (isset($r['occupancy_type']) && $r['occupancy_type'] === $filter_type);
    return $match_search && $match_type;
});
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">Residents Management</h1>
            <p class="text-slate-600 mt-1">Manage all residents in the complex</p>
        </div>
        <button onclick="openAddDialog()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-all">
            <i class="fas fa-plus"></i> Add Resident
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" id="searchInput" placeholder="Search by name, mobile, email or flat..." value="<?php echo htmlspecialchars($search_term); ?>" onkeyup="applyFilters()" class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
            </div>
            <select id="filterSelect" onchange="applyFilters()" class="w-full md:w-48 px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                <option value="all">All Types</option>
                <option value="owned" <?php echo $filter_type === 'owned' ? 'selected' : ''; ?>>Owned</option>
                <option value="rent" <?php echo $filter_type === 'rent' ? 'selected' : ''; ?>>Rent</option>
            </select>
        </div>
    </div>

    <!-- Residents Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-900">All Residents (<?php echo count($filtered); ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700">Name</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700">Contact</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700">Location</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700">Role</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700">Type</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700">Registered</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($filtered) > 0): ?>
                        <?php foreach ($filtered as $resident): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        <?php echo strtoupper(substr($resident['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($resident['name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo isset($resident['email']) && !empty($resident['email']) ? htmlspecialchars($resident['email']) : 'Not provided'; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1 text-sm text-slate-600">
                                    <i class="fas fa-phone text-xs"></i>
                                    <?php echo htmlspecialchars($resident['mobile']); ?>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1 text-sm text-slate-600">
                                    <i class="fas fa-home text-xs"></i>
                                    <?php 
                                        if (isset($resident['block_name']) && isset($resident['flat_number'])) {
                                            echo htmlspecialchars($resident['block_name']) . ', Flat ' . htmlspecialchars($resident['flat_number']);
                                        } else {
                                            echo 'Unassigned';
                                        }
                                    ?>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium 
                                    <?php echo (isset($resident['is_primary']) && $resident['is_primary']) ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700'; ?>">
                                    <?php echo (isset($resident['is_primary']) && $resident['is_primary']) ? 'Primary' : 'Family Member'; ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <?php if (isset($resident['occupancy_type']) && !empty($resident['occupancy_type'])): ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium 
                                        <?php echo $resident['occupancy_type'] === 'owned' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo ucfirst($resident['occupancy_type']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        Not specified
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-sm text-slate-600">
                                <?php echo date('M d, Y', strtotime($resident['created_at'])); ?>
                            </td>
                            <td class="py-3 px-4">
                                <button onclick="openViewModal(<?php echo htmlspecialchars(json_encode($resident)); ?>)" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">
                                    <i class="fas fa-eye text-xs"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="fas fa-users text-5xl mb-4"></i>
                                    <p class="text-slate-600">No residents found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="addEditModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 id="modalTitle" class="text-xl font-semibold text-slate-900">Register New Resident</h2>
            <button onclick="closeAddEditModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <form id="residentForm" method="POST" class="p-6 space-y-6">
            <input type="hidden" name="add_resident" value="1">
            <input type="hidden" name="resident_id" id="residentId">
            <input type="hidden" name="qr_code" id="qrCode">
            <input type="hidden" name="registration_date" id="registrationDate">
            <input type="hidden" name="family_members" id="familyMembersInput" value="[]">
            
            <!-- Personal Information -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Full Name *</label>
                        <input type="text" name="name" id="residentName" placeholder="Enter full name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Mobile Number *</label>
                        <input type="tel" name="mobile" id="residentMobile" placeholder="Enter mobile number" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                    </div>
                    <div class="space-y-2 col-span-2">
                        <label class="block text-sm font-medium text-slate-900">Email Address</label>
                        <input type="email" name="email" id="residentEmail" placeholder="Enter email address" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <!-- Flat Information -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Flat Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Block *</label>
                        <select id="blockSelect" name="block_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                            <option value="">Select Block</option>
                            <?php foreach ($blocks as $block): ?>
                                <option value="<?php echo $block['id']; ?>"><?php echo htmlspecialchars($block['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Floor *</label>
                        <select id="floorSelect" name="floor" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                            <option value="">Select Block First</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Flat *</label>
                        <select id="flatSelect" name="flat_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                            <option value="">Select Floor First</option>
                        </select>
                        <div id="noFlatsNotification" class="mt-2 hidden p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle mt-0.5"></i>
                            <span id="noFlatsMessage">All flats are occupied in this floor</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-900">Occupancy Type</label>
                    <select name="occupancy_type" id="occupancyType" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select type</option>
                        <option value="owned">Owned</option>
                        <option value="rent">Rent</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-900">Registration Date</label>
                    <input type="date" name="registration_date" id="registrationDateInput" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <!-- Emergency Contact -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Emergency Contact</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Contact Name</label>
                        <input type="text" name="emergency_contact_name" id="emergencyContactName" placeholder="Enter contact name" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Contact Number</label>
                        <input type="tel" name="emergency_contact" id="emergencyContact" placeholder="Enter contact number" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <!-- Family Members -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Family Members</h3>
                <div class="space-y-3">
                    <div id="familyMembersContainer" class="space-y-3"></div>
                    <div class="grid grid-cols-4 gap-2">
                        <input type="text" id="newMemberName" placeholder="Name" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <input type="text" id="newMemberRelation" placeholder="Relation" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <input type="text" id="newMemberAge" placeholder="Age" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <button type="button" onclick="handleAddFamilyMember()" class="px-3 py-2 border border-slate-300 hover:bg-slate-50 rounded-lg text-sm font-medium flex items-center justify-center gap-1">
                            <i class="fas fa-user-plus text-xs"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <!-- Password Section -->
            <div id="passwordSection">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Password</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Password *</label>
                        <input type="password" name="password" id="residentPassword" placeholder="Enter password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-900">Confirm Password *</label>
                        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <button type="submit" id="submitBtn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition-colors">
                Register Resident
            </button>
        </form>
    </div>
</div>

<!-- View Resident Dialog -->
<div id="viewModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden overflow-y-auto py-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 overflow-y-auto max-h-[calc(100vh-2rem)]">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Resident Details</h2>
            <button onclick="closeViewModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- QR Code -->
            <div class="flex justify-center p-6 bg-slate-50 rounded-lg">
                <div class="text-center">
                    <div id="qrCodeContainer" class="inline-block p-4 bg-white rounded-lg border border-slate-200"></div>
                    <p class="text-sm text-slate-600 mt-2">Resident QR Code</p>
                    <p id="viewQRCodeText" class="text-xs text-slate-500"></p>
                </div>
            </div>

            <!-- Personal Info -->
            <div>
                <h4 class="text-sm font-semibold text-slate-900 mb-3">Personal Information</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-slate-600">Name:</span> <strong id="viewName"></strong></div>
                    <div><span class="text-slate-600">Mobile:</span> <strong id="viewMobile"></strong></div>
                    <div class="col-span-2"><span class="text-slate-600">Email:</span> <strong id="viewEmail"></strong></div>
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <!-- Flat Info -->
            <div>
                <h4 class="text-sm font-semibold text-slate-900 mb-3">Flat Information</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-slate-600">Block:</span> <strong id="viewBlock"></strong></div>
                    <div><span class="text-slate-600">Flat No:</span> <strong id="viewFlat"></strong></div>
                    <div><span class="text-slate-600">Type:</span> <span id="viewOccupancyBadge" class="inline-block px-2.5 py-1 rounded-full text-xs font-medium"></span></div>
                    <div><span class="text-slate-600">Registered:</span> <strong id="viewRegistered"></strong></div>
                </div>
            </div>

            <div class="border-t border-slate-200"></div>

            <!-- Emergency Contact -->
            <div>
                <h4 class="text-sm font-semibold text-slate-900 mb-3">Emergency Contact</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-slate-600">Name:</span> <strong id="viewEmergencyName"></strong></div>
                    <div><span class="text-slate-600">Phone:</span> <strong id="viewEmergencyPhone"></strong></div>
                </div>
            </div>

            <div id="familyMembersSection">
                <div class="border-t border-slate-200"></div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 mb-3">Family Members (<span id="familyCount">0</span>)</h4>
                    <div id="viewFamilyMembers" class="space-y-2"></div>
                </div>
            </div>

            <div class="flex gap-2">
                <button onclick="handleEditFromView()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-medium transition-colors">
                    Edit Details
                </button>
                <button onclick="handleDeleteFromView()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-medium transition-colors">
                    Delete Resident
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentResident = null;
let familyMembers = [];

// Display flash message on page load
document.addEventListener('DOMContentLoaded', function() {
    const flashData = <?php echo json_encode($flashMessage); ?>;
    if (flashData && flashData.type && flashData.text) {
        toast(flashData.type, flashData.text);
    }

    // Add event listeners for dropdown cascading
    const blockSelect = document.getElementById('blockSelect');
    const floorSelect = document.getElementById('floorSelect');
    if (blockSelect) blockSelect.addEventListener('change', updateFloors);
    if (floorSelect) floorSelect.addEventListener('change', updateFlats);
});

function openAddDialog() {
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const residentForm = document.getElementById('residentForm');
    const passwordSection = document.getElementById('passwordSection');
    const registrationDateInput = document.getElementById('registrationDateInput');
    const blockSelect = document.getElementById('blockSelect');
    const floorSelect = document.getElementById('floorSelect');
    const flatSelect = document.getElementById('flatSelect');
    const familyMembersContainer = document.getElementById('familyMembersContainer');
    const familyMembersInput = document.getElementById('familyMembersInput');
    const addEditModal = document.getElementById('addEditModal');
    
    if (modalTitle) modalTitle.textContent = 'Register New Resident';
    if (submitBtn) submitBtn.textContent = 'Register Resident';
    if (residentForm) residentForm.reset();
    if (passwordSection) passwordSection.style.display = 'block';
    
    // Make password fields required for add mode
    const residentPassword = document.getElementById('residentPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    if (residentPassword) residentPassword.setAttribute('required', 'required');
    if (confirmPassword) confirmPassword.setAttribute('required', 'required');
    
    // Clear current resident flat ID for add mode
    window.currentResidentFlatId = null;
    
    // Set default registration date to today
    const today = new Date().toISOString().split('T')[0];
    if (registrationDateInput) registrationDateInput.value = today;
    
    // Ensure required attributes are set for adding
    if (blockSelect) blockSelect.setAttribute('required', 'required');
    if (floorSelect) floorSelect.setAttribute('required', 'required');
    if (flatSelect) flatSelect.setAttribute('required', 'required');
    
    // Update labels to show required indicators
    const blockLabel = document.querySelector('label[for="blockSelect"]');
    const floorLabel = document.querySelector('label[for="floorSelect"]');
    const flatLabel = document.querySelector('label[for="flatSelect"]');
    if (blockLabel && !blockLabel.textContent.includes('*')) blockLabel.textContent += ' *';
    if (floorLabel && !floorLabel.textContent.includes('*')) floorLabel.textContent += ' *';
    if (flatLabel && !flatLabel.textContent.includes('*')) flatLabel.textContent += ' *';
    
    // Reset form action for add
    let actionInput = document.querySelector('input[name="add_resident"], input[name="update_resident"]');
    if (actionInput) {
        actionInput.name = 'add_resident';
    }
    
    familyMembers = [];
    if (familyMembersContainer) familyMembersContainer.innerHTML = '';
    if (familyMembersInput) familyMembersInput.value = '[]';
    if (addEditModal) addEditModal.classList.remove('hidden');
    initializeDropdowns();
}

// Floor data from PHP
const floorsByBlock = <?php echo json_encode($floors_by_block); ?>;

function updateFloors() {
    const blockSelect = document.getElementById('blockSelect');
    const floorSelect = document.getElementById('floorSelect');
    const flatSelect = document.getElementById('flatSelect');
    const selectedBlockId = blockSelect ? blockSelect.value : '';

    // Clear floor and flat options
    if (floorSelect) floorSelect.innerHTML = '<option value="">Select Floor</option>';
    if (flatSelect) flatSelect.innerHTML = '<option value="">Select Floor First</option>';
    const noFlatsNotification = document.getElementById('noFlatsNotification');
    if (noFlatsNotification) noFlatsNotification.classList.add('hidden');

    if (selectedBlockId && floorsByBlock[selectedBlockId]) {
        floorsByBlock[selectedBlockId].forEach(floor => {
            const option = document.createElement('option');
            option.value = floor;
            option.textContent = 'Floor ' + floor;
            if (floorSelect) floorSelect.appendChild(option);
        });
    }
}

function updateFlats() {
    const blockSelect = document.getElementById('blockSelect');
    const floorSelect = document.getElementById('floorSelect');
    const flatSelect = document.getElementById('flatSelect');
    const noFlatsNotification = document.getElementById('noFlatsNotification');
    const selectedBlockId = blockSelect.value;
    const selectedFloor = floorSelect.value;

    flatSelect.innerHTML = '<option value="">Loading...</option>';
    flatSelect.disabled = true;
    noFlatsNotification.classList.add('hidden');

    if (selectedBlockId && selectedFloor) {
        const currentFlatId = window.currentResidentFlatId || '';
        const url = `<?php echo BASE_URL; ?>/admin/getAvailableFlats?block_id=${selectedBlockId}&floor=${selectedFloor}&current_flat_id=${currentFlatId}`;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                flatSelect.innerHTML = '<option value="">Select Flat</option>';

                if (data.flats && data.flats.length > 0) {
                    // Flats found
                    let selectableCount = 0;
                    data.flats.forEach(flat => {
                        const option = document.createElement('option');
                        option.value = flat.id;
                        option.textContent = `Flat ${flat.number} (${flat.status})`;
                        option.disabled = flat.status === 'occupied';
                        if (flat.status === 'current') {
                            option.style.color = '#2563eb'; // blue color for current resident's flat
                            option.disabled = false;
                            selectableCount++; // Current flat is selectable
                        } else if (flat.status === 'occupied') {
                            option.style.color = '#dc2626'; // red color for occupied
                        } else {
                            option.style.color = '#16a34a'; // green color for vacant
                            selectableCount++; // Vacant flats are selectable
                        }
                        flatSelect.appendChild(option);
                    });

                    // Show notification only if no selectable flats exist (no vacant AND no current flats)
                    if (selectableCount === 0) {
                        if (noFlatsNotification) noFlatsNotification.classList.remove('hidden');
                        const noFlatsMessage = document.getElementById('noFlatsMessage');
                        if (noFlatsMessage && blockSelect && blockSelect.options[blockSelect.selectedIndex]) {
                            noFlatsMessage.textContent = 'All flats are occupied in Block ' + blockSelect.options[blockSelect.selectedIndex].text + ', Floor ' + selectedFloor;
                        }
                        if (flatSelect) {
                            flatSelect.innerHTML = '<option value="">No flats available</option>';
                            flatSelect.disabled = true;
                        }
                    } else {
                        if (flatSelect) flatSelect.disabled = false;
                    }
                } else {
                    // No flats found for this block/floor combination
                    if (noFlatsNotification) noFlatsNotification.classList.remove('hidden');
                    const noFlatsMessage = document.getElementById('noFlatsMessage');
                    if (noFlatsMessage && blockSelect && blockSelect.options[blockSelect.selectedIndex]) {
                        noFlatsMessage.textContent = 'All flats are occupied in Block ' + blockSelect.options[blockSelect.selectedIndex].text + ', Floor ' + selectedFloor;
                    }
                    if (flatSelect) {
                        flatSelect.innerHTML = '<option value="">No flats available</option>';
                        flatSelect.disabled = true;
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching flats:', error);
                flatSelect.innerHTML = '<option value="">Error loading flats</option>';
                flatSelect.disabled = true;
                console.log('Request URL:', url);
            });
    } else {
        flatSelect.innerHTML = '<option value="">Select Floor First</option>';
        flatSelect.disabled = true;
    }
}

function initializeDropdowns() {
    const blockSelect = document.getElementById('blockSelect');
    const floorSelect = document.getElementById('floorSelect');
    const flatSelect = document.getElementById('flatSelect');

    // Reset all selects when modal opens
    if (blockSelect) blockSelect.value = '';
    if (floorSelect) floorSelect.value = '';
    if (flatSelect) {
        flatSelect.value = '';
        flatSelect.disabled = true;
    }
    const noFlatsNotification = document.getElementById('noFlatsNotification');
    if (noFlatsNotification) noFlatsNotification.classList.add('hidden');
}

function updateFlatDetails() {
    const select = document.getElementById('flatSelect');
    const value = select ? select.value : '';
    if (value) {
        const [block, flat] = value.split('|');
        const blockNameInput = document.getElementById('blockName');
        const flatNoInput = document.getElementById('flatNo');
        if (blockNameInput) blockNameInput.value = block;
        if (flatNoInput) flatNoInput.value = flat;
    }
}

function handleAddFamilyMember() {
    const newMemberName = document.getElementById('newMemberName');
    const newMemberRelation = document.getElementById('newMemberRelation');
    const newMemberAge = document.getElementById('newMemberAge');
    
    const name = newMemberName ? newMemberName.value.trim() : '';
    const relation = newMemberRelation ? newMemberRelation.value.trim() : '';
    const age = newMemberAge ? newMemberAge.value.trim() : '';
    
    if (!name || !relation) {
        toast('error', 'Please enter member name and relation');
        return;
    }
    
    familyMembers.push({ name, relation, age });
    const familyMembersInput = document.getElementById('familyMembersInput');
    if (familyMembersInput) familyMembersInput.value = JSON.stringify(familyMembers);
    
    renderFamilyMembers();
    
    // Clear inputs
    if (newMemberName) newMemberName.value = '';
    if (newMemberRelation) newMemberRelation.value = '';
    if (newMemberAge) newMemberAge.value = '';
}

function renderFamilyMembers() {
    const container = document.getElementById('familyMembersContainer');
    if (container) {
        container.innerHTML = familyMembers.map((member, index) => `
            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
                <div class="flex-1 grid grid-cols-3 gap-2 text-sm">
                    <span><strong>Name:</strong> ${member.name}</span>
                    <span><strong>Relation:</strong> ${member.relation}</span>
                    <span><strong>Age:</strong> ${member.age || 'N/A'}</span>
                </div>
                <button type="button" onclick="removeFamilyMember(${index})" class="p-2 text-red-600 hover:bg-red-50 rounded transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }
}

function removeFamilyMember(index) {
    familyMembers.splice(index, 1);
    const familyMembersInput = document.getElementById('familyMembersInput');
    if (familyMembersInput) familyMembersInput.value = JSON.stringify(familyMembers);
    renderFamilyMembers();
}

function openViewModal(resident) {
    viewResident(resident);
}

function viewResident(resident) {
    currentResident = resident;
    
    // Set basic information from database
    const viewName = document.getElementById('viewName');
    const viewMobile = document.getElementById('viewMobile');
    const viewEmail = document.getElementById('viewEmail');
    const viewBlock = document.getElementById('viewBlock');
    const viewFlat = document.getElementById('viewFlat');
    const viewOccupancyBadge = document.getElementById('viewOccupancyBadge');
    const viewRegistered = document.getElementById('viewRegistered');
    const viewEmergencyName = document.getElementById('viewEmergencyName');
    const viewEmergencyPhone = document.getElementById('viewEmergencyPhone');
    const viewQRCodeText = document.getElementById('viewQRCodeText');
    const qrCodeContainer = document.getElementById('qrCodeContainer');
    
    if (viewName) viewName.textContent = resident.name || 'N/A';
    if (viewMobile) viewMobile.textContent = resident.mobile || 'N/A';
    if (viewEmail) viewEmail.textContent = resident.email || 'Not provided';
    
    // Set flat information
    if (resident.block_name && resident.flat_number) {
        if (viewBlock) viewBlock.textContent = resident.block_name;
        if (viewFlat) viewFlat.textContent = resident.flat_number;
    } else {
        if (viewBlock) viewBlock.textContent = 'Unassigned';
        if (viewFlat) viewFlat.textContent = 'Unassigned';
    }
    
    // Set occupancy type
    const occupancyType = resident.occupancy_type || 'Not specified';
    if (viewOccupancyBadge) {
        viewOccupancyBadge.textContent = occupancyType.charAt(0).toUpperCase() + occupancyType.slice(1);
        if (occupancyType === 'owned') {
            viewOccupancyBadge.className = 'inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700';
        } else if (occupancyType === 'rent') {
            viewOccupancyBadge.className = 'inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700';
        } else {
            viewOccupancyBadge.className = 'inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-slate-200 text-slate-700';
        }
    }
    
    // Set registration date from created_at
    const regDate = new Date(resident.created_at);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    if (viewRegistered) viewRegistered.textContent = `${months[regDate.getMonth()]} ${regDate.getDate()}, ${regDate.getFullYear()}`;
    
    // Set emergency contact
    if (viewEmergencyName) viewEmergencyName.textContent = resident.emergency_contact_name || 'Not provided';
    if (viewEmergencyPhone) viewEmergencyPhone.textContent = resident.emergency_contact || 'Not provided';
    
    // Set QR Code (not in database, show ID instead)
    const qrCode = 'RES-' + String(resident.id).padStart(3, '0');
    if (viewQRCodeText) viewQRCodeText.textContent = qrCode;
    
    // Generate QR Code with qrcodejs library
    if (qrCodeContainer) {
        qrCodeContainer.innerHTML = '';
        
        // Check if QRCode library is loaded
        if (typeof QRCode === 'undefined') {
            console.warn('QRCode library not loaded, showing text fallback');
            qrCodeContainer.innerHTML = '<div class="p-4 bg-white rounded-lg border-2 border-slate-300"><p class="text-sm font-mono text-slate-900">' + qrCode + '</p></div>';
        } else {
            try {
                // Create QR code using qrcodejs
                new QRCode(qrCodeContainer, {
                    text: qrCode,
                    width: 150,
                    height: 150,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            } catch (error) {
                console.error('QR Code generation error:', error);
                qrCodeContainer.innerHTML = '<div class="p-4 bg-white rounded-lg border-2 border-slate-300"><p class="text-sm font-mono text-slate-900">' + qrCode + '</p></div>';
            }
        }
    }
    
    // Family members (not in database, hide section)
    const familySection = document.getElementById('familyMembersSection');
    if (familySection) {
        familySection.style.display = 'none';
    }
    
    const viewModal = document.getElementById('viewModal');
    if (viewModal) viewModal.classList.remove('hidden');
}

function handleEditFromView() {
    if (!currentResident) {
        console.error('No resident selected');
        return;
    }

    const resident = currentResident;
    closeViewModal();
    
    // Store current resident's flat_id globally for flat selection
    window.currentResidentFlatId = resident.flat_id;
    
    // Wait for modal to be visible before populating
    setTimeout(() => {
        try {
            // Populate edit form with database fields only
            const modalTitle = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');
            if (modalTitle) modalTitle.textContent = 'Edit Resident';
            if (submitBtn) submitBtn.textContent = 'Update Resident';
            
            // Change form action for update
            let actionInput = document.querySelector('input[name="add_resident"], input[name="update_resident"]');
            if (actionInput) {
                actionInput.name = 'update_resident';
            }
            
            const residentIdInput = document.getElementById('residentId');
            const residentNameInput = document.getElementById('residentName');
            const residentMobileInput = document.getElementById('residentMobile');
            const residentEmailInput = document.getElementById('residentEmail');
            const occupancyTypeInput = document.getElementById('occupancyType');
            const registrationDateInput = document.getElementById('registrationDateInput');
            const emergencyContactNameInput = document.getElementById('emergencyContactName');
            const emergencyContactInput = document.getElementById('emergencyContact');
            
            if (residentIdInput) residentIdInput.value = resident.id;
            if (residentNameInput) residentNameInput.value = resident.name;
            if (residentMobileInput) residentMobileInput.value = resident.mobile;
            if (residentEmailInput) residentEmailInput.value = resident.email || '';
            if (occupancyTypeInput) occupancyTypeInput.value = resident.occupancy_type || '';
            if (registrationDateInput) registrationDateInput.value = resident.registration_date || '';
            if (emergencyContactNameInput) emergencyContactNameInput.value = resident.emergency_contact_name || '';
            if (emergencyContactInput) emergencyContactInput.value = resident.emergency_contact || '';
            
            // Make flat selection optional for editing
            const blockSelect = document.getElementById('blockSelect');
            const floorSelect = document.getElementById('floorSelect');
            const flatSelect = document.getElementById('flatSelect');
            
            if (blockSelect) blockSelect.removeAttribute('required');
            if (floorSelect) floorSelect.removeAttribute('required');
            if (flatSelect) flatSelect.removeAttribute('required');
            
            // Update labels to remove required indicators
            const blockLabel = document.querySelector('label[for="blockSelect"]');
            const floorLabel = document.querySelector('label[for="floorSelect"]');
            const flatLabel = document.querySelector('label[for="flatSelect"]');
            if (blockLabel) blockLabel.textContent = blockLabel.textContent.replace(' *', '');
            if (floorLabel) floorLabel.textContent = floorLabel.textContent.replace(' *', '');
            if (flatLabel) flatLabel.textContent = flatLabel.textContent.replace(' *', '');
            
            // Set flat selection if resident has one
            if (resident.flat_id) {
                if (blockSelect) blockSelect.value = resident.block_id || '';
                // Manually trigger floor update
                updateFloors();
                setTimeout(() => {
                    if (floorSelect) floorSelect.value = resident.floor || '';
                    // Manually trigger flat update
                    updateFlats();
                    setTimeout(() => {
                        if (flatSelect) flatSelect.value = resident.flat_id || '';
                    }, 200);
                }, 200);
            } else {
                if (blockSelect) blockSelect.value = '';
                if (floorSelect) floorSelect.value = '';
                if (flatSelect) flatSelect.value = '';
            }
            
            // Hide password section for edit (optional password)
            const passwordSection = document.getElementById('passwordSection');
            if (passwordSection) passwordSection.style.display = 'none';
            
            // Make password fields optional for edit
            const residentPassword = document.getElementById('residentPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            if (residentPassword) residentPassword.removeAttribute('required');
            if (confirmPassword) confirmPassword.removeAttribute('required');
            
            // Clear family members (not used in current implementation)
            familyMembers = [];
            const familyMembersInput = document.getElementById('familyMembersInput');
            if (familyMembersInput) familyMembersInput.value = JSON.stringify(familyMembers);
            
            document.getElementById('addEditModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error populating edit form:', error);
            toast('error', 'Error opening edit form. Please try again.');
        }
    }, 50);
}

function handleDeleteFromView() {
    if (!currentResident) {
        console.error('No resident selected');
        return;
    }
    
    const residentId = currentResident.id;
    closeViewModal();
    
    // Show confirmation after closing view modal
    setTimeout(() => {
        showConfirmation('This action cannot be undone.', 'Delete Resident?', function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="delete_resident" value="1">
                <input type="hidden" name="resident_id" value="${residentId}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }, 100);
}

function closeAddEditModal() {
    const addEditModal = document.getElementById('addEditModal');
    if (addEditModal) addEditModal.classList.add('hidden');
    currentResident = null;
}

function closeViewModal() {
    const viewModal = document.getElementById('viewModal');
    if (viewModal) viewModal.classList.add('hidden');
    currentResident = null;
}

function applyFilters() {
    const searchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');
    const search = searchInput ? searchInput.value : '';
    const filter = filterSelect ? filterSelect.value : '';
    const url = new URL(window.location);
    url.searchParams.set('search', search);
    url.searchParams.set('filter', filter);
    window.location = url.toString();
}

const residentForm = document.getElementById('residentForm');
if (residentForm) {
    residentForm.addEventListener('submit', function(e) {
        // Form will submit normally with all data
        const familyMembersInput = document.getElementById('familyMembersInput');
        if (familyMembersInput) familyMembersInput.value = JSON.stringify(familyMembers);
    });
}
</script>
