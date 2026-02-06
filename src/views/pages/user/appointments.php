<?php
// Use data from controller
$resident_name = $resident_info['name'] ?? '';
$resident_flat = ($resident_info['block'] ?? '') . '-' . ($resident_info['flat'] ?? '');

// Separate upcoming and past appointments based on appointment_time and status
$upcoming = [];
$past = [];

foreach ($appointments as $apt) {
    $apt_datetime = strtotime($apt['appointment_time']);
    $current_time = time();
    
    // Upcoming: Future time AND status is pending or checked_in (active statuses)
    if ($apt_datetime > $current_time && in_array($apt['status'], ['pending', 'checked_in'])) {
        $upcoming[] = $apt;
    } else {
        // Past: Either past time OR status is completed
        $past[] = $apt;
    }
}

// Sort by date/time descending
usort($upcoming, function($a, $b) { 
    return strtotime($b['appointment_time']) - strtotime($a['appointment_time']); 
});
usort($past, function($a, $b) { 
    return strtotime($b['appointment_time']) - strtotime($a['appointment_time']); 
});
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">My Appointments</h1>
            <p class="text-slate-600 mt-1">Manage your visitor appointments</p>
        </div>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-6 py-2 font-medium flex items-center gap-2 transition-all">
            <i class="fas fa-plus w-4 h-4"></i>
            Create Appointment
        </button>
    </div>

    <!-- Create Appointment Modal -->
    <div id="createModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">Create Appointment</h2>
                <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Visitor Name</label>
                    <input type="text" name="visitor_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Enter visitor name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Contact Number</label>
                    <input type="tel" name="visitor_contact" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="10-digit mobile number">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Date</label>
                    <input type="date" name="date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Entry Time</label>
                    <input type="time" name="time" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Purpose of Visit</label>
                    <textarea name="purpose" required rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Enter purpose of visit"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-slate-300 text-slate-900 font-medium rounded-lg hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Upcoming Appointments</h2>
        </div>
        <?php if (empty($upcoming)): ?>
        <div class="p-8 text-center text-slate-500">
            <i class="fas fa-calendar-times w-12 h-12 mx-auto mb-3 opacity-50"></i>
            <p>No upcoming appointments</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-200">
            <?php foreach ($upcoming as $apt): ?>
            <div class="p-6 hover:bg-slate-50 transition-colors">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-slate-900"><?php echo htmlspecialchars($apt['visitor_name']); ?></h3>
                        <p class="text-sm text-slate-600 mt-1"><?php echo htmlspecialchars($apt['purpose']); ?></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                        <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-calendar w-4 h-4"></i>
                        <?php echo date('M d, Y', strtotime($apt['appointment_time'])); ?>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-clock w-4 h-4"></i>
                        <?php echo date('H:i', strtotime($apt['appointment_time'])); ?>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-phone w-4 h-4"></i>
                        <?php echo htmlspecialchars($apt['visitor_contact']); ?>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-home w-4 h-4"></i>
                        <?php echo htmlspecialchars($resident_flat); ?>
                    </div>
                </div>
                <button onclick="showQRCode('APT<?php echo $apt['id']; ?>', '<?php echo htmlspecialchars($apt['visitor_name']); ?>')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 font-medium flex items-center justify-center gap-2 transition-all">
                    <i class="fas fa-qrcode w-4 h-4"></i>
                    View QR Code
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Past Appointments -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Past Appointments</h2>
        </div>
        <?php if (empty($past)): ?>
        <div class="p-8 text-center text-slate-500">
            <i class="fas fa-history w-12 h-12 mx-auto mb-3 opacity-50"></i>
            <p>No past appointments</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-200">
            <?php foreach (array_slice($past, 0, 5) as $apt): ?>
            <div class="p-6 hover:bg-slate-50 transition-colors">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-slate-900"><?php echo htmlspecialchars($apt['visitor_name']); ?></h3>
                        <p class="text-sm text-slate-600 mt-1"><?php echo htmlspecialchars($apt['purpose']); ?></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $apt['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800'; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i class="fas fa-calendar w-4 h-4"></i>
                            <?php echo date('M d, Y', strtotime($apt['appointment_time'])); ?>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i class="fas fa-clock w-4 h-4"></i>
                            <?php echo date('H:i', strtotime($apt['appointment_time'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="flex items-center justify-between p-6 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-900">QR Code</h2>
            <button onclick="document.getElementById('qrModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 text-center space-y-4">
            <p class="text-sm text-slate-600">For: <span id="visitorName" class="font-semibold text-slate-900"></span></p>
            <div id="qrContainer" class="flex justify-center">
                <img id="qrImage" src="" alt="QR Code" class="w-48 h-48">
            </div>
            <p class="text-xs text-slate-500">Share this QR code with your visitor</p>
            <button onclick="downloadQR()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 font-medium flex items-center justify-center gap-2">
                <i class="fas fa-download w-4 h-4"></i>
                Download QR
            </button>
        </div>
    </div>
</div>

<script>
function showQRCode(qrData, visitorName) {
    document.getElementById('visitorName').textContent = visitorName;
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}`;
    document.getElementById('qrImage').src = qrUrl;
    document.getElementById('qrModal').classList.remove('hidden');
}

function downloadQR() {
    const link = document.createElement('a');
    link.href = document.getElementById('qrImage').src;
    link.download = 'appointment-qr.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
