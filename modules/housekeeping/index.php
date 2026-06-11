<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = 'Housekeeping';
$active_page = 'housekeeping';
$baseUrl = getBaseUrl();

// 确保 housekeeping_status 字段和 housekeeping_tasks 表存在
try {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN housekeeping_status TEXT DEFAULT 'clean'");
} catch (Exception $e) { /* column already exists */ }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS housekeeping_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        task_type TEXT NOT NULL DEFAULT 'cleaning' CHECK(task_type IN ('cleaning','bed_change','maintenance')),
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending','in_progress','completed')),
        assigned_to TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id)
    )");
} catch (Exception $e) { /* table already exists */ }

// 初始化所有没有 housekeeping_status 的房间
$pdo->exec("UPDATE rooms SET housekeeping_status = 'clean' WHERE housekeeping_status IS NULL");

// 查询所有房间及其房型信息
$stmt = $pdo->query("
    SELECT r.id, r.room_number, r.status, r.floor, r.housekeeping_status, r.notes,
        rt.name as type_name, rt.base_price
    FROM rooms r
    LEFT JOIN room_types rt ON r.room_type_id = rt.id
    ORDER BY r.floor, r.room_number
");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 统计各状态的房间数
$statusCounts = ['clean' => 0, 'dirty' => 0, 'inspecting' => 0, 'maintenance' => 0];
foreach ($rooms as $room) {
    $hs = $room['housekeeping_status'] ?? 'clean';
    if (isset($statusCounts[$hs])) {
        $statusCounts[$hs]++;
    }
}

// 查询今日任务
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT ht.*, r.room_number
    FROM housekeeping_tasks ht
    JOIN rooms r ON ht.room_id = r.id
    WHERE DATE(ht.created_at) = ?
    ORDER BY ht.status ASC, ht.created_at DESC
");
$stmt->execute([$today]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取员工列表用于任务分配
$staffList = $pdo->query("SELECT id, first_name, last_name FROM staff WHERE status = 'active' ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<style>
.room-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
@media (max-width: 991.98px) { .room-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 767.98px) { .room-grid { grid-template-columns: repeat(2, 1fr); } }

.room-cell {
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    border: 2px solid transparent;
}
.room-cell:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.room-cell.hk-clean {
    background: #d4edda;
    border-color: #28a745;
}
.room-cell.hk-dirty {
    background: #f8d7da;
    border-color: #dc3545;
}
.room-cell.hk-inspecting {
    background: #d1ecf1;
    border-color: #17a2b8;
}
.room-cell.hk-maintenance {
    background: #fff3cd;
    border-color: #ffc107;
}
.room-cell .room-num {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
}
.room-cell .room-type-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
}
.badge-hk-clean { background: #28a745; color: #fff; }
.badge-hk-dirty { background: #dc3545; color: #fff; }
.badge-hk-inspecting { background: #17a2b8; color: #fff; }
.badge-hk-maintenance { background: #ffc107; color: #333; }
</style>

<div class="page-header">
    <h4><i class="bi bi-brush"></i> <?php echo t('nav_housekeeping'); ?></h4>
    <div class="d-flex gap-2">
        <select id="statusFilter" class="form-select form-select-sm" style="width:auto" onchange="filterRooms(this.value)">
            <option value=""><?php echo t('all'); ?></option>
            <option value="clean">Clean</option>
            <option value="dirty">Dirty</option>
            <option value="inspecting">Inspecting</option>
            <option value="maintenance">Maintenance</option>
        </select>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-circle"></i> Add Task
        </button>
    </div>
</div>

<!-- 统计卡片 -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm" style="cursor:pointer" onclick="filterRooms('clean')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Clean</h6>
                        <h3 class="text-success"><?php echo $statusCounts['clean']; ?></h3>
                    </div>
                    <div class="stat-icon bg-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm" style="cursor:pointer" onclick="filterRooms('dirty')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Dirty</h6>
                        <h3 class="text-danger"><?php echo $statusCounts['dirty']; ?></h3>
                    </div>
                    <div class="stat-icon bg-danger">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm" style="cursor:pointer" onclick="filterRooms('inspecting')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Inspecting</h6>
                        <h3 class="text-info"><?php echo $statusCounts['inspecting']; ?></h3>
                    </div>
                    <div class="stat-icon bg-info">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm" style="cursor:pointer" onclick="filterRooms('maintenance')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Maintenance</h6>
                        <h3 class="text-warning"><?php echo $statusCounts['maintenance']; ?></h3>
                    </div>
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room Status Grid -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-grid-3x3-gap me-2"></i>Room Status Grid</span>
        <span class="text-muted small">Click a room to update status</span>
    </div>
    <div class="card-body">
        <div class="room-grid" id="roomGrid">
            <?php foreach ($rooms as $room): ?>
            <?php
                $hs = $room['housekeeping_status'] ?? 'clean';
                $floorLabels = [1 => '1st Floor', 2 => '2nd Floor', 3 => '3rd Floor', 4 => '4th Floor'];
            ?>
            <div class="room-cell hk-<?php echo $hs; ?>"
                 data-status="<?php echo $hs; ?>"
                 data-room-id="<?php echo $room['id']; ?>"
                 data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>"
                 data-type="<?php echo htmlspecialchars($room['type_name'] ?? ''); ?>"
                 onclick="openStatusModal(this)">
                <div class="room-num"><?php echo htmlspecialchars($room['room_number']); ?></div>
                <div class="room-type-label"><?php echo htmlspecialchars($room['type_name'] ?? 'N/A'); ?></div>
                <span class="badge badge-hk-<?php echo $hs; ?> badge-status"><?php echo ucfirst($hs); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Task List -->
<div class="card border-0 shadow-sm">
    <div class="card-header">
        <i class="bi bi-list-task me-2"></i>Today's Tasks
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Task Type</th>
                        <th><?php echo t('status'); ?></th>
                        <th>Assigned To</th>
                        <th><?php echo t('notes'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No tasks for today.</td></tr>
                    <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($task['room_number']); ?></strong></td>
                        <td>
                            <?php
                            $taskTypeLabels = ['cleaning' => 'Cleaning', 'bed_change' => 'Bed Change', 'maintenance' => 'Maintenance'];
                            $taskTypeIcons = ['cleaning' => 'bi-brush', 'bed_change' => 'bi-bed', 'maintenance' => 'bi-tools'];
                            ?>
                            <i class="bi <?php echo $taskTypeIcons[$task['task_type']] ?? 'bi-circle'; ?> me-1"></i>
                            <?php echo $taskTypeLabels[$task['task_type']] ?? ucfirst($task['task_type']); ?>
                        </td>
                        <td>
                            <?php
                            $taskStatusColors = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success'];
                            $taskStatusLabels = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'];
                            ?>
                            <span class="badge badge-status badge-<?php echo $taskStatusColors[$task['status']]; ?>">
                                <?php echo $taskStatusLabels[$task['status']] ?? ucfirst($task['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($task['assigned_to'] ?? '-'); ?></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($task['notes'] ?? '-'); ?></td>
                        <td>
                            <?php if ($task['status'] !== 'completed'): ?>
                            <button class="btn btn-sm btn-outline-success btn-advance-task"
                                    data-id="<?php echo $task['id']; ?>"
                                    data-status="<?php echo $task['status']; ?>"
                                    title="Advance status">
                                <i class="bi bi-arrow-right"></i>
                            </button>
                            <?php else: ?>
                            <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Room Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Room Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm">
                <input type="hidden" id="status_room_id" name="room_id" value="">
                <input type="hidden" name="action" value="update_status">
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="fs-4 fw-bold" id="statusModalRoomNum"></div>
                        <div class="text-muted" id="statusModalRoomType"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('status'); ?></label>
                        <select class="form-select" id="status_hk_status" name="status">
                            <option value="clean">Clean</option>
                            <option value="dirty">Dirty</option>
                            <option value="inspecting">Inspecting</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('notes'); ?></label>
                        <textarea class="form-control" name="notes" id="status_notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> <?php echo t('update'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Housekeeping Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTaskForm">
                <input type="hidden" name="action" value="add_task">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Room <span class="text-danger">*</span></label>
                        <select class="form-select" name="room_id" required>
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>">
                                <?php echo htmlspecialchars($room['room_number'] . ' - ' . ($room['type_name'] ?? 'N/A') . ' (Floor ' . $room['floor'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Task Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="task_type" required>
                            <option value="cleaning">Cleaning</option>
                            <option value="bed_change">Bed Change</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select class="form-select" name="assigned_to">
                            <option value="">Unassigned</option>
                            <?php foreach ($staffList as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>">
                                <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('notes'); ?></label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Task details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const apiUrl = '<?php echo $baseUrl; ?>/modules/housekeeping/api.php';

// Filter rooms by housekeeping status
function filterRooms(status) {
    const filterSelect = document.getElementById('statusFilter');
    filterSelect.value = status;

    document.querySelectorAll('.room-cell').forEach(cell => {
        if (!status || cell.dataset.status === status) {
            cell.style.display = '';
        } else {
            cell.style.display = 'none';
        }
    });
}

// Open status update modal
function openStatusModal(cell) {
    document.getElementById('status_room_id').value = cell.dataset.roomId;
    document.getElementById('statusModalRoomNum').textContent = 'Room ' + cell.dataset.roomNumber;
    document.getElementById('statusModalRoomType').textContent = cell.dataset.type;
    document.getElementById('status_hk_status').value = cell.dataset.status;
    document.getElementById('status_notes').value = '';
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

// Submit status update
document.getElementById('statusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(apiUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error occurred');
            }
        })
        .catch(err => alert('Request failed: ' + err));
});

// Submit add task
document.getElementById('addTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(apiUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error occurred');
            }
        })
        .catch(err => alert('Request failed: ' + err));
});

// Advance task status
document.querySelectorAll('.btn-advance-task').forEach(btn => {
    btn.addEventListener('click', function() {
        const taskId = this.dataset.id;
        const currentStatus = this.dataset.status;
        let nextStatus = 'completed';
        if (currentStatus === 'pending') nextStatus = 'in_progress';
        else if (currentStatus === 'in_progress') nextStatus = 'completed';

        if (!confirm('Change task status to "' + nextStatus.replace('_', ' ') + '"?')) return;

        const formData = new FormData();
        formData.append('action', 'update_task');
        formData.append('task_id', taskId);
        formData.append('status', nextStatus);

        fetch(apiUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Error occurred');
                }
            })
            .catch(err => alert('Request failed: ' + err));
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
