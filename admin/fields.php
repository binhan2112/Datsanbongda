<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('admin');
$msg = '';

// Xử lý thay đổi trạng thái hoặc xóa sân bóng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $field_id = intval($_POST['field_id']);
    
    if ($_POST['action'] === 'toggle_status') {
        $new_status = $_POST['status'];
        if (in_array($new_status, ['active', 'inactive', 'pending'])) {
            $stmt = $pdo->prepare("UPDATE fields SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $field_id]);
            $msg = "Đã cập nhật trạng thái sân bóng thành công!";
        }
    } elseif ($_POST['action'] === 'delete') {
        try {
            // Xóa ảnh liên quan trước
            $stmtImages = $pdo->prepare("SELECT image_path FROM field_images WHERE field_id = ?");
            $stmtImages->execute([$field_id]);
            $images = $stmtImages->fetchAll();
            foreach ($images as $img) {
                $filePath = '../' . $img['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Xóa sân bóng (khóa ngoại CASCADE ở field_images, reviews, favorites, field_unavailable)
            // Lưu ý: bookings có fk_booking_field không có ON DELETE CASCADE, có thể gặp lỗi nếu đã có lịch đặt.
            // Để an toàn, chúng ta bắt lỗi ngoại lệ nếu không thể xóa do ràng buộc dữ liệu đặt sân.
            $stmt = $pdo->prepare("DELETE FROM fields WHERE id = ?");
            $stmt->execute([$field_id]);
            $msg = "Đã xóa sân bóng thành công!";
        } catch (PDOException $e) {
            $msg = "<span style='color:#dc2626;'>Lỗi: Không thể xóa sân bóng này vì đã có lịch sử đặt sân liên quan (Bookings). Vui lòng chuyển trạng thái sang Vô hiệu hóa thay vì xóa.</span>";
        }
    }
}

// Lấy bộ lọc và tìm kiếm
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_district = isset($_GET['district']) ? $_GET['district'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Tạo câu truy vấn SQL
$sql = "
    SELECT f.*, u.full_name as owner_name, u.phone as owner_phone 
    FROM fields f
    JOIN users u ON f.owner_id = u.id
    WHERE 1=1
";
$params = [];

if ($filter_status !== '') {
    $sql .= " AND f.status = ?";
    $params[] = $filter_status;
}
if ($filter_district !== '') {
    $sql .= " AND f.district = ?";
    $params[] = $filter_district;
}
if ($search !== '') {
    $sql .= " AND f.name LIKE ?";
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fields = $stmt->fetchAll();

// Lấy danh sách quận/huyện để làm bộ lọc
$districts_stmt = $pdo->query("SELECT DISTINCT district FROM fields ORDER BY district");
$districts = $districts_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Quản lý Sân bóng';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h2 style="margin: 0;">Quản lý Sân bóng</h2>
        
        <!-- Bộ lọc & Tìm kiếm -->
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" placeholder="Tìm tên sân..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); outline: none; min-width: 200px;">
            
            <select name="district" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); outline: none;">
                <option value="">-- Tất cả Quận/Huyện --</option>
                <?php foreach ($districts as $d): ?>
                    <option value="<?php echo htmlspecialchars($d); ?>" <?php if($filter_district === $d) echo 'selected'; ?>><?php echo htmlspecialchars($d); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); outline: none;">
                <option value="">-- Tất cả Trạng thái --</option>
                <option value="active" <?php if($filter_status === 'active') echo 'selected'; ?>>Đang hoạt động</option>
                <option value="inactive" <?php if($filter_status === 'inactive') echo 'selected'; ?>>Vô hiệu hóa</option>
                <option value="pending" <?php if($filter_status === 'pending') echo 'selected'; ?>>Chờ duyệt</option>
            </select>
            
            <button type="submit" class="btn btn-primary" style="padding: 8px 15px; border-radius: 6px; display: flex; align-items: center; gap: 5px;">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i> Tìm
            </button>
            <?php if($search !== '' || $filter_district !== '' || $filter_status !== ''): ?>
                <a href="fields.php" class="btn-logout" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); text-decoration: none; color: var(--text-muted); display: inline-flex; align-items: center;">Xóa lọc</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border); font-weight: 500;">
            <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Sân bóng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Chủ sân</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Địa chỉ & Quận</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Loại & Giá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Đánh giá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Trạng thái</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($fields) > 0): ?>
                        <?php foreach ($fields as $f): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($f['name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">Đăng lúc: <?php echo date('d/m/Y', strtotime($f['created_at'])); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($f['owner_name']); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($f['owner_phone']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($f['address']); ?><br>
                                    <strong style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($f['district']); ?></strong>
                                </td>
                                <td style="padding: 15px 20px;">
                                    Sân <?php echo $f['type']; ?><br>
                                    <strong style="color: #ef4444;"><?php echo number_format($f['price_per_hour'], 0, ',', '.'); ?>đ/h</strong>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <span style="display: flex; align-items: center; gap: 3px; color: #fbbf24; font-weight: 600;">
                                        <i data-lucide="star" style="width: 14px; height: 14px; fill: #fbbf24;"></i>
                                        <?php echo number_format($f['rating'], 1); ?>
                                    </span>
                                    <small style="color: var(--text-muted);">(<?php echo $f['total_reviews']; ?> đánh giá)</small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($f['status'] === 'active'): ?>
                                        <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Hoạt động</span>
                                    <?php elseif ($f['status'] === 'inactive'): ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Vô hiệu hóa</span>
                                    <?php else: ?>
                                        <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chờ duyệt</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                        <!-- Thay đổi trạng thái nhanh -->
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="field_id" value="<?php echo $f['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); font-size: 12px;">
                                                <option value="active" <?php if($f['status'] === 'active') echo 'selected'; ?>>Kích hoạt</option>
                                                <option value="inactive" <?php if($f['status'] === 'inactive') echo 'selected'; ?>>Vô hiệu hóa</option>
                                                <option value="pending" <?php if($f['status'] === 'pending') echo 'selected'; ?>>Đợi duyệt</option>
                                            </select>
                                        </form>

                                        <!-- Xóa -->
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA sân này không?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="field_id" value="<?php echo $f['id']; ?>">
                                            <button type="submit" class="btn-logout" style="padding: 6px 10px; font-size: 12px; border-radius: 4px;">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Không có sân bóng nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
