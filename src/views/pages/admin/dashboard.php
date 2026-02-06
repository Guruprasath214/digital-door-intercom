<?php
/**
 * Admin Dashboard Page
 */
?>
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-semibold text-slate-900">Dashboard</h1>
        <p class="text-slate-600 mt-1">Welcome back! Here's what's happening today.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card bg-white rounded-lg border border-slate-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-slate-600 font-medium"><?php echo htmlspecialchars($stat['name']); ?></p>
                            <h3 class="text-3xl font-bold text-slate-900 mt-2"><?php echo htmlspecialchars($stat['value']); ?></h3>
                            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                <i class="nav-icon" data-lucide="TrendingUp"></i>
                                <?php echo htmlspecialchars($stat['change']); ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-lg <?php echo $stat['color']; ?> flex items-center justify-center">
                            <i class="nav-icon text-white" data-lucide="<?php echo $stat['icon']; ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Visitor Trends -->
        <div class="bg-white rounded-lg border border-slate-200">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Visitor Trends (Last 7 Days)</h2>
            </div>
            <div class="p-6 h-[300px]">
                <canvas id="visitorChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- Occupancy Distribution -->
        <div class="bg-white rounded-lg border border-slate-200">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Occupancy Distribution</h2>
            </div>
            <div class="p-6 h-[300px]">
                <canvas id="occupancyChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Today's Appointments -->
    <div class="bg-white rounded-lg border border-slate-200">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                <i class="nav-icon" data-lucide="Calendar"></i>
                Today's Appointments (<?php echo count($today_appointments ?? []); ?>)
            </h2>
        </div>
        <div class="p-6">
            <?php if (empty($today_appointments)): ?>
                <div class="text-center py-8">
                    <i class="nav-icon" data-lucide="Calendar" style="width: 3rem; height: 3rem; color: #cbd5e1;"></i>
                    <p class="text-slate-600">No appointments scheduled for today</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($today_appointments as $apt): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($apt['visitor_name'] ?? '', 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900"><?php echo htmlspecialchars($apt['visitor_name'] ?? ''); ?></p>
                                    <p class="text-sm text-slate-600">Visiting <?php echo htmlspecialchars($apt['resident_name'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-sm text-slate-600">Block <?php echo htmlspecialchars($apt['block_name'] ?? '-'); ?>, Flat <?php echo htmlspecialchars($apt['flat_number'] ?? '-'); ?></p>
                                    <p class="text-sm font-medium text-slate-900"><?php echo !empty($apt['appointment_time']) ? date('M d, H:i', strtotime($apt['appointment_time'])) : ''; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Visitors -->
    <div class="bg-white rounded-lg border border-slate-200">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Recent Visitors</h2>
        </div>
        <div class="p-6">
            <?php if (empty($recent_visitors)): ?>
                <div class="text-center py-8">
                    <i class="nav-icon" data-lucide="Users" style="width: 3rem; height: 3rem; color: #cbd5e1;"></i>
                    <p class="text-slate-600">No visitors recorded</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Visitor Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Visiting</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Location</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Time</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_visitors as $visitor): ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 text-sm text-slate-900"><?php echo htmlspecialchars($visitor['name'] ?? ''); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-600"><?php echo htmlspecialchars($visitor['resident_name'] ?? ''); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-600">Block <?php echo htmlspecialchars($visitor['block_name'] ?? '-'); ?>, Flat <?php echo htmlspecialchars($visitor['flat_number'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        <div class="flex items-center gap-1">
                                            <i class="nav-icon" data-lucide="Clock"></i>
                                            <?php echo !empty($visitor['check_in']) ? date('M d, H:i', strtotime($visitor['check_in'])) : ''; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo empty($visitor['check_out']) ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'; ?>">
                                            <?php echo empty($visitor['check_out']) ? 'Inside' : 'Left'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Visitor Trends Chart
    const visitorCtx = document.getElementById('visitorChart').getContext('2d');
    new Chart(visitorCtx, {
        type: 'line',
        data: {
            labels: [<?php echo !empty($visitor_data) ? implode(',', array_map(fn($d) => "'" . $d['day'] . "'", $visitor_data)) : "'No data'"; ?>],
            datasets: [{
                label: 'Visitors',
                data: [<?php echo !empty($visitor_data) ? implode(',', array_map(fn($d) => $d['visitors'], $visitor_data)) : "0"; ?>],
                borderColor: '#3b82f6',
                backgroundColor: 'transparent',
                tension: 0.4,
                fill: false,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 4,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#fff',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    titleColor: '#334155',
                    bodyColor: '#475569',
                    displayColors: false,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    grid: {
                        color: '#e2e8f0',
                        borderDash: [3, 3]
                    },
                    ticks: {
                        color: '#64748b'
                    }
                },
                x: {
                    grid: {
                        color: '#e2e8f0',
                        borderDash: [3, 3]
                    },
                    ticks: {
                        color: '#64748b'
                    }
                }
            }
        }
    });

    // Occupancy Chart
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    new Chart(occupancyCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo !empty($occupancy) ? implode(',', array_map(fn($d) => "'" . $d['month'] . "'", $occupancy)) : "'No data'"; ?>],
            datasets: [
                {
                    label: 'Owned',
                    data: [<?php echo !empty($occupancy) ? implode(',', array_map(fn($d) => $d['owned'], $occupancy)) : "0"; ?>],
                    backgroundColor: '#3b82f6',
                    borderRadius: 8,
                    borderSkipped: 'bottom',
                },
                {
                    label: 'Rent',
                    data: [<?php echo !empty($occupancy) ? implode(',', array_map(fn($d) => $d['rent'], $occupancy)) : "0"; ?>],
                    backgroundColor: '#60a5fa',
                    borderRadius: 8,
                    borderSkipped: 'bottom',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#fff',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    titleColor: '#334155',
                    bodyColor: '#475569',
                    displayColors: false,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    grid: {
                        color: '#e2e8f0',
                        borderDash: [3, 3]
                    },
                    ticks: {
                        color: '#64748b'
                    }
                },
                x: {
                    grid: {
                        color: '#e2e8f0',
                        borderDash: [3, 3]
                    },
                    ticks: {
                        color: '#64748b'
                    }
                }
            }
        }
    });
</script>
