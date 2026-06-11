<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('room_list');
$active_page = 'rooms';
$module_js = 'rooms';
$baseUrl = getBaseUrl();

// Filters
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_floor = $_GET['floor'] ?? '';

$where = [];
$params = [];
if ($filter_type) { $where[] = 'r.room_type_id = ?'; $params[] = $filter_type; }
if ($filter_status) { $where[] = 'r.status = ?'; $params[] = $filter_status; }
if ($filter_floor) { $where[] = 'r.floor = ?'; $params[] = $filter_floor; }

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT r.*, rt.name as type_name, rt.base_price 
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    $whereClause 
    ORDER BY r.floor, r.room_number
");
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$types = $pdo->query("SELECT * FROM room_types ORDER BY id")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-door-open"></i> <?php echo t('room_list'); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/rooms/create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> <?php echo t('add_room'); ?>
    </a>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form id="filterForm" method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <select name="type" class="form-select form-select-sm">
                <option value=""><?php echo t('all'); ?> <?php echo t('room_type'); ?></option>
                <?php foreach ($types as $type): ?>
                <option value="<?php echo $type['id']; ?>" <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value=""><?php echo t('all'); ?> <?php echo t('status'); ?></option>
                <option value="available" <?php echo $filter_status === 'available' ? 'selected' : ''; ?>><?php echo t('status_available'); ?></option>
                <option value="occupied" <?php echo $filter_status === 'occupied' ? 'selected' : ''; ?>><?php echo t('status_occupied'); ?></option>
                <option value="maintenance" <?php echo $filter_status === 'maintenance' ? 'selected' : ''; ?>><?php echo t('status_maintenance'); ?></option>
                <option value="reserved" <?php echo $filter_status === 'reserved' ? 'selected' : ''; ?>><?php echo t('status_reserved'); ?></option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="floor" class="form-select form-select-sm">
                <option value=""><?php echo t('all'); ?> <?php echo t('floor'); ?></option>
                <?php for ($f = 1; $f <= 4; $f++): ?>
                <option value="<?php echo $f; ?>" <?php echo $filter_floor == $f ? 'selected' : ''; ?>><?php echo t('floor'); ?> <?php echo $f; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <a href="<?php echo $baseUrl; ?>/modules/rooms/index.php" class="btn btn-sm btn-outline-secondary"><?php echo t('reset'); ?></a>
        </div>
    </form>
</div>

<!-- Room Status Summary -->
<div class="row g-3 mb-4">
    <?php
    $statusCounts = ['available' => 0, 'occupied' => 0, 'reserved' => 0, 'maintenance' => 0];
    $stmt = $pdo->query("SELECT status, COUNT(*) as c FROM rooms GROUP BY status");
    foreach ($stmt->fetchAll() as $row) {
        if (isset($statusCounts[$row['status']])) {
            $statusCounts[$row['status']] = (int)$row['c'];
        }
    }
    $statusCards = [
        ['key' => 'available', 'icon' => 'bi-check-circle-fill', 'color' => 'success', 'count' => $statusCounts['available']],
        ['key' => 'occupied', 'icon' => 'bi-person-fill', 'color' => 'danger', 'count' => $statusCounts['occupied']],
        ['key' => 'reserved', 'icon' => 'bi-bookmark-fill', 'color' => 'info', 'count' => $statusCounts['reserved']],
        ['key' => 'maintenance', 'icon' => 'bi-tools', 'color' => 'warning', 'count' => $statusCounts['maintenance']],
    ];
    foreach ($statusCards as $card):
    ?>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card" style="cursor:pointer" onclick="filterByStatus('<?php echo $card['key']; ?>')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('status_' . $card['key']); ?></h6>
                        <h3><?php echo $card['count']; ?></h3>
                    </div>
                    <div class="stat-icon bg-<?php echo $card['color']; ?>">
                        <i class="bi <?php echo $card['icon']; ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Rooms Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php echo t('room_number'); ?></th>
                        <th><?php echo t('room_type'); ?></th>
                        <th><?php echo t('floor'); ?></th>
                        <th><?php echo t('price_per_night'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted"><?php echo t('no_data'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                        <td><?php echo $room['floor']; ?></td>
                        <td><?php echo formatCurrency($room['base_price']); ?></td>
                        <td><span class="badge badge-status badge-<?php echo $room['status']; ?>"><?php echo t('status_' . $room['status']); ?></span></td>
                        <td>
                            <a href="<?php echo $baseUrl; ?>/modules/rooms/edit.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo t('edit'); ?>">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger btn-delete-room" 
                                    data-id="<?php echo $room['id']; ?>"
                                    data-url="<?php echo $baseUrl; ?>/modules/rooms/api.php"
                                    data-confirm="<?php echo t('confirm_delete'); ?>"
                                    title="<?php echo t('delete'); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterByStatus(status) {
    const select = document.querySelector('select[name="status"]');
    if (select) {
        select.value = status;
        document.getElementById('filterForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
