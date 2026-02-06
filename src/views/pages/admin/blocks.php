<?php
// Use blocks data from controller, fallback to session if available
$blocks = $blocks ?? $_SESSION['blocks'] ?? [];
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-3xl font-semibold text-slate-900">Blocks & Floors</h1><p class="text-slate-600 mt-1">Manage building blocks and their floors</p></div>
        <button onclick="openAddBlockDialog()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-all">
            <i class="fas fa-plus"></i> Add Block
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php foreach ($blocks as $block): ?>
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="bg-indigo-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-building w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($block['block_name']); ?></h3>
                        <p class="text-xs text-slate-600"><?php echo count($block['floors']); ?> floors</p>
                    </div>
                </div>
                <button onclick="deleteBlock(<?php echo $block['id']; ?>)" class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition-colors"><i class="fas fa-trash w-4 h-4"></i></button>
            </div>

            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-semibold text-slate-700">Floors</h4>
                    <button onclick="openAddFloorDialog(<?php echo $block['id']; ?>)" class="text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1 text-sm">
                        <i class="fas fa-plus"></i> Add Floor
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($block['floors'] as $floor): ?>
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-sm font-medium text-slate-700">Floor <?php echo htmlspecialchars($floor['floor_no']); ?></span>
                        <button onclick="deleteFloor(<?php echo $block['id']; ?>, <?php echo $floor['id']; ?>)" class="text-red-600 hover:bg-red-50 p-1 rounded transition-colors">
                            <i class="fas fa-trash w-3 h-3"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($blocks)): ?>
        <div class="col-span-full text-center py-12">
            <div class="flex flex-col items-center justify-center text-slate-400">
                <i class="fas fa-building text-5xl mb-4 opacity-50"></i>
                <p class="text-slate-600">No blocks found</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Block Modal -->
<div id="addBlockModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="bg-indigo-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Add New Block</h2>
            <button onclick="closeBlockModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add_block">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Block Name *</label>
                <input type="text" name="block_name" placeholder="e.g., Block A" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-medium">Add Block</button>
                <button type="button" onclick="closeBlockModal()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Floor Modal -->
<div id="addFloorModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="bg-indigo-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Add Floor</h2>
            <button onclick="closeFloorModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add_floor">
            <input type="hidden" name="block_id" id="blockIdInput">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Floor Number *</label>
                <input type="number" name="floor_no" placeholder="1" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-medium">Add Floor</button>
                <button type="button" onclick="closeFloorModal()" class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-900 py-2 rounded-lg font-medium">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddBlockDialog() { document.getElementById('addBlockModal').classList.remove('hidden'); }
function closeBlockModal() { document.getElementById('addBlockModal').classList.add('hidden'); }
function openAddFloorDialog(blockId) { document.getElementById('blockIdInput').value = blockId; document.getElementById('addFloorModal').classList.remove('hidden'); }
function closeFloorModal() { document.getElementById('addFloorModal').classList.add('hidden'); }
function deleteBlock(id) { 
    showConfirmation('This action cannot be undone.', 'Delete Block?', function() { 
        const form = document.createElement('form'); 
        form.method='POST'; 
        form.innerHTML='<input type="hidden" name="action" value="delete_block"><input type="hidden" name="id" value="'+id+'">'; 
        document.body.appendChild(form); 
        form.submit(); 
    }); 
}
function deleteFloor(blockId, floorId) { 
    showConfirmation('This action cannot be undone.', 'Delete Floor?', function() { 
        const form = document.createElement('form'); 
        form.method='POST'; 
        form.innerHTML='<input type="hidden" name="action" value="delete_floor"><input type="hidden" name="block_id" value="'+blockId+'"><input type="hidden" name="floor_id" value="'+floorId+'">'; 
        document.body.appendChild(form); 
        form.submit(); 
    }); 
}
</script>
