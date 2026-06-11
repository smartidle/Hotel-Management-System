<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = 'Room Types';
$active_page = 'roomtypes';
$baseUrl = getBaseUrl();

// 查询所有房型及每种房型的房间数量统计
$stmt = $pdo->query("
    SELECT rt.*,
        COUNT(r.id) as total_rooms,
        SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_count
    FROM room_types rt
    LEFT JOIN rooms r ON r.room_type_id = rt.id
    GROUP BY rt.id
    ORDER BY rt.base_price ASC
");
$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 统计数据
$totalTypes = count($roomTypes);
$totalRooms = array_sum(array_column($roomTypes, 'total_rooms'));
$avgPrice = $totalTypes > 0 ? array_sum(array_column($roomTypes, 'base_price')) / $totalTypes : 0;
$totalAvailable = array_sum(array_column($roomTypes, 'available_count'));
$occupiedRate = $totalRooms > 0 ? round((($totalRooms - $totalAvailable) / $totalRooms) * 100, 1) : 0;

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-tags"></i> <?php echo t('nav_room_types'); ?></h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roomTypeModal" onclick="openAddModal()">
        <i class="bi bi-plus-circle"></i> <?php echo t('create_new'); ?>
    </button>
</div>

<!-- 统计卡片 -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Types</h6>
                        <h3><?php echo $totalTypes; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-tags"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('total_rooms'); ?></h6>
                        <h3><?php echo $totalRooms; ?></h3>
                    </div>
                    <div class="stat-icon bg-info">
                        <i class="bi bi-door-open"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Avg Price</h6>
                        <h3><?php echo formatCurrency($avgPrice); ?></h3>
                    </div>
                    <div class="stat-icon bg-purple">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('occupancy_rate'); ?></h6>
                        <h3><?php echo $occupiedRate; ?>%</h3>
                    </div>
                    <div class="stat-icon bg-success">
                        <i class="bi bi-pie-chart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 房型卡片网格 -->
<div class="row g-3">
    <?php if (empty($roomTypes)): ?>
    <div class="col-12">
        <div class="empty-state">
            <i class="bi bi-tags"></i>
            <p><?php echo t('no_data'); ?></p>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($roomTypes as $rt): ?>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($rt['name']); ?></h5>
                    <span class="badge bg-primary"><?php echo $rt['total_rooms']; ?> <?php echo t('total_rooms'); ?></span>
                </div>

                <div class="mb-2">
                    <span class="fs-4 text-primary fw-bold"><?php echo formatCurrency($rt['base_price']); ?></span>
                    <span class="text-muted">/night</span>
                </div>

                <p class="text-muted small mb-2"><?php echo htmlspecialchars($rt['description'] ?? ''); ?></p>

                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-people text-muted me-2"></i>
                    <small><?php echo t('max_occupancy'); ?>: <strong><?php echo $rt['max_occupancy']; ?></strong></small>
                </div>

                <?php if ($rt['total_rooms'] > 0): ?>
                <div class="mb-2">
                    <span class="badge bg-success"><?php echo $rt['available_count']; ?> <?php echo t('status_available'); ?></span>
                    <span class="badge bg-secondary"><?php echo $rt['total_rooms'] - $rt['available_count']; ?> <?php echo t('status_occupied'); ?>/<?php echo t('status_maintenance'); ?></span>
                </div>
                <?php endif; ?>

                <?php
                $amenities = json_decode($rt['amenities'], true);
                if ($amenities):
                ?>
                <div class="mb-3">
                    <?php foreach ($amenities as $key => $val): ?>
                    <?php if ($val): ?>
                    <span class="badge bg-light text-dark me-1 mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i><?php echo ucfirst($key); ?></span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary flex-fill"
                            onclick="openEditModal(<?php echo $rt['id']; ?>, '<?php echo htmlspecialchars(addslashes($rt['name'])); ?>', '<?php echo htmlspecialchars(addslashes($rt['description'] ?? '')); ?>', <?php echo $rt['base_price']; ?>, <?php echo $rt['max_occupancy']; ?>, '<?php echo htmlspecialchars(addslashes($rt['amenities'] ?? '{}')); ?>')">
                        <i class="bi bi-pencil"></i> <?php echo t('edit'); ?>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-delete-roomtype"
                            data-id="<?php echo $rt['id']; ?>"
                            data-name="<?php echo htmlspecialchars($rt['name']); ?>"
                            title="<?php echo t('delete'); ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="roomTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomTypeModalTitle">Add Room Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="roomTypeForm">
                <input type="hidden" id="rt_id" name="id" value="">
                <input type="hidden" name="action" id="rt_action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rt_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('description'); ?></label>
                        <textarea class="form-control" id="rt_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('base_price'); ?> <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="rt_base_price" name="base_price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('max_occupancy'); ?> <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="rt_max_occupancy" name="max_occupancy" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('amenities'); ?></label>
                        <div class="row">
                            <?php
                            $allAmenities = ['wifi' => 'WiFi', 'tv' => 'TV', 'ac' => 'AC', 'minibar' => 'Minibar', 'bathtub' => 'Bathtub', 'kitchenette' => 'Kitchenette', 'butler' => 'Butler', 'jacuzzi' => 'Jacuzzi'];
                            foreach ($allAmenities as $key => $label):
                            ?>
                            <div class="col-md-3 col-6">
                                <div class="form-check">
                                    <input class="form-check-input amenity-check" type="checkbox" value="1" id="amenity_<?php echo $key; ?>" data-key="<?php echo $key; ?>">
                                    <label class="form-check-label" for="amenity_<?php echo $key; ?>"><?php echo $label; ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> <?php echo t('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const apiUrl = '<?php echo $baseUrl; ?>/modules/roomtypes/api.php';

function openAddModal() {
    document.getElementById('roomTypeModalTitle').textContent = 'Add Room Type';
    document.getElementById('rt_id').value = '';
    document.getElementById('rt_action').value = 'create';
    document.getElementById('roomTypeForm').reset();
    document.querySelectorAll('.amenity-check').forEach(cb => cb.checked = false);
}

function openEditModal(id, name, description, basePrice, maxOccupancy, amenitiesJson) {
    document.getElementById('roomTypeModalTitle').textContent = 'Edit Room Type';
    document.getElementById('rt_id').value = id;
    document.getElementById('rt_action').value = 'update';
    document.getElementById('rt_name').value = name;
    document.getElementById('rt_description').value = description;
    document.getElementById('rt_base_price').value = basePrice;
    document.getElementById('rt_max_occupancy').value = maxOccupancy;

    // Set amenities checkboxes
    let amenities = {};
    try { amenities = JSON.parse(amenitiesJson); } catch(e) {}
    document.querySelectorAll('.amenity-check').forEach(cb => {
        const key = cb.dataset.key;
        cb.checked = !!(amenities[key]);
    });

    new bootstrap.Modal(document.getElementById('roomTypeModal')).show();
}

// Build amenities JSON from checkboxes
function getAmenitiesJson() {
    const amenities = {};
    document.querySelectorAll('.amenity-check').forEach(cb => {
        amenities[cb.dataset.key] = cb.checked;
    });
    return JSON.stringify(amenities);
}

// Submit form
document.getElementById('roomTypeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('amenities', getAmenitiesJson());

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

// Delete
document.querySelectorAll('.btn-delete-roomtype').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('Are you sure you want to delete "' + this.dataset.name + '"?\n<?php echo t('confirm_delete'); ?>')) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', this.dataset.id);

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
