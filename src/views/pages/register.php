<?php
// Get data from controller
$blocks = $blocks ?? [];
$floors_by_block = $floors_by_block ?? [];

// Get any errors or success messages
$errors = $_SESSION['registration_errors'] ?? [];
$success = $_SESSION['registration_success'] ?? '';
$form_data = $_SESSION['registration_data'] ?? [];

// Clear session messages
unset($_SESSION['registration_errors'], $_SESSION['registration_success'], $_SESSION['registration_data']);
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Resident Registration
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Create your account to access the apartment management system
            </p>
        </div>

        <?php if (!empty($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST" action="<?php echo BASE_URL; ?>/register/register">
            <div class="bg-white py-8 px-6 shadow-lg rounded-lg space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                    <input id="name" name="name" type="text" required
                           value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="mobile" class="block text-sm font-medium text-gray-700">Mobile Number *</label>
                    <input id="mobile" name="mobile" type="tel" required pattern="[0-9]{10}"
                           value="<?php echo htmlspecialchars($form_data['mobile'] ?? ''); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter 10-digit mobile number">
                </div>

                <div>
                    <label for="block_id" class="block text-sm font-medium text-gray-700">Block *</label>
                    <select id="block_id" name="block_id" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Block</option>
                        <?php foreach ($blocks as $block): ?>
                        <option value="<?php echo $block['id']; ?>" <?php echo ($form_data['block_id'] ?? '') == $block['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($block['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="floor" class="block text-sm font-medium text-gray-700">Floor *</label>
                    <select id="floor" name="floor" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Block First</option>
                    </select>
                </div>

                <div>
                    <label for="flat_id" class="block text-sm font-medium text-gray-700">Flat *</label>
                    <select id="flat_id" name="flat_id" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Floor First</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
                    <input id="password" name="password" type="password" required minlength="6"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Minimum 6 characters">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password *</label>
                    <input id="confirm_password" name="confirm_password" type="password" required minlength="6"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Re-enter your password">
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Register
                    </button>
                </div>
            </div>
        </form>

        <div class="text-center">
            <p class="text-sm text-gray-600">
                Already have an account?
                <a href="<?php echo BASE_URL; ?>/login" class="font-medium text-blue-600 hover:text-blue-500">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</div>

<script>
// Floor data from PHP
const floorsByBlock = <?php echo json_encode($floors_by_block); ?>;

// Form data persistence
const formData = {
    block_id: '<?php echo $form_data['block_id'] ?? ''; ?>',
    floor: '<?php echo $form_data['floor'] ?? ''; ?>',
    flat_id: '<?php echo $form_data['flat_id'] ?? ''; ?>'
};

function updateFloors() {
    const blockSelect = document.getElementById('block_id');
    const floorSelect = document.getElementById('floor');
    const flatSelect = document.getElementById('flat_id');
    const selectedBlockId = blockSelect.value;

    // Clear floor and flat options
    floorSelect.innerHTML = '<option value="">Select Floor</option>';
    flatSelect.innerHTML = '<option value="">Select Floor First</option>';

    if (selectedBlockId && floorsByBlock[selectedBlockId]) {
        floorsByBlock[selectedBlockId].forEach(floor => {
            const option = document.createElement('option');
            option.value = floor;
            option.textContent = 'Floor ' + floor;
            // Restore selected floor from form data
            if (formData.floor && formData.floor == floor) {
                option.selected = true;
            }
            floorSelect.appendChild(option);
        });

        // Set the floor select value if it was in form data
        if (formData.floor) {
            floorSelect.value = formData.floor;
        }
    }
}

function updateFlats() {
    const blockSelect = document.getElementById('block_id');
    const floorSelect = document.getElementById('floor');
    const flatSelect = document.getElementById('flat_id');
    const selectedBlockId = blockSelect.value;
    const selectedFloor = floorSelect.value;

    flatSelect.innerHTML = '<option value="">Loading...</option>';

    if (selectedBlockId && selectedFloor) {
        fetch(`<?php echo BASE_URL; ?>/register/getFlats?block_id=${selectedBlockId}&floor=${selectedFloor}`)
            .then(response => response.json())
            .then(data => {
                flatSelect.innerHTML = '<option value="">Select Flat</option>';

                if (data.flats && data.flats.length > 0) {
                    data.flats.forEach(flat => {
                        const option = document.createElement('option');
                        option.value = flat.id;
                        option.textContent = `Flat ${flat.number} (${flat.status})`;
                        option.disabled = flat.status === 'occupied';
                        if (flat.status === 'occupied') {
                            option.style.color = '#dc2626'; // red color for occupied
                        } else {
                            option.style.color = '#16a34a'; // green color for vacant
                        }
                        // Restore selected flat from form data
                        if (formData.flat_id && formData.flat_id == flat.id) {
                            option.selected = true;
                        }
                        flatSelect.appendChild(option);
                    });

                    // Set the flat select value if it was in form data
                    if (formData.flat_id) {
                        flatSelect.value = formData.flat_id;
                    }
                } else {
                    flatSelect.innerHTML = '<option value="">No flats available</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching flats:', error);
                flatSelect.innerHTML = '<option value="">Error loading flats</option>';
            });
    } else {
        flatSelect.innerHTML = '<option value="">Select Floor First</option>';
    }
}

// Event listeners
document.getElementById('block_id').addEventListener('change', updateFloors);
document.getElementById('floor').addEventListener('change', updateFlats);

// Initialize on page load if block is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const blockSelect = document.getElementById('block_id');

    // If block is selected (either from form data or user selection)
    if (blockSelect.value) {
        updateFloors();

        // Small delay to ensure floors are populated, then update flats if floor is selected
        setTimeout(() => {
            const floorSelect = document.getElementById('floor');
            if (floorSelect.value) {
                updateFlats();
            } else if (formData.floor) {
                // If form data has floor but it's not selected, try to select it
                floorSelect.value = formData.floor;
                if (floorSelect.value) {
                    updateFlats();
                }
            }
        }, 100);
    }
});
</script>