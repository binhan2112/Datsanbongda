<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$owner_id = $_SESSION['user_id'];
$msg = '';

// Hàm tạo slug đơn giản
function createSlug($str) {
    $str = mb_strtolower($str);
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

// Xử lý Thêm Sân Mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_field') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $district = trim($_POST['district']);
    $price_per_hour = floatval($_POST['price_per_hour']);
    $deposit_percent = intval($_POST['deposit_percent'] ?? 0);
    $type = $_POST['type'] ?? '5v5';
    
    if (empty($name) || empty($address) || empty($district) || empty($price_per_hour)) {
        $msg = "Vui lòng nhập đầy đủ Tên sân, Địa chỉ, Quận và Giá thuê.";
    } else {
        $slug = createSlug($name) . '-' . time(); // Đảm bảo unique
        
        try {
            $stmt = $pdo->prepare("INSERT INTO fields (owner_id, name, slug, address, district, lat, lng, type, price_per_hour, deposit_percent, status) VALUES (?, ?, ?, ?, ?, 10.0307, 105.7725, ?, ?, ?, 'pending')");
            $stmt->execute([$owner_id, $name, $slug, $address, $district, $type, $price_per_hour, $deposit_percent]);
            $msg = "Thêm sân mới thành công! Sân của bạn đang chờ Admin duyệt.";
        } catch (PDOException $e) {
            $msg = "Lỗi thêm sân: " . $e->getMessage();
        }
    }
}

// Xử lý Xóa Sân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_field') {
    $field_id = intval($_POST['field_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM fields WHERE id = ? AND owner_id = ?");
        $stmt->execute([$field_id, $owner_id]);
        $msg = "Đã xóa sân thành công!";
    } catch (PDOException $e) {
        $msg = "Không thể xóa sân do có dữ liệu liên quan.";
    }
}

// Lấy danh sách sân của Owner
$stmt = $pdo->prepare("SELECT * FROM fields WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$owner_id]);
$fields = $stmt->fetchAll();

$page_title = 'Quản lý Sân bóng';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Quản lý Sân bóng của bạn</h2>
        <button onclick="document.getElementById('add-modal').style.display='flex';" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
            <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Thêm sân mới
        </button>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #e0f2fe; color: #0284c7; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bae6fd;">
            <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Thêm Sân -->
    <div id="add-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="width: 100%; max-width: 500px; margin: 20px;">
            <div class="card-header">
                <h3 class="card-title">Đăng ký sân mới</h3>
                <button onclick="document.getElementById('add-modal').style.display='none';" style="background: none; border: none; cursor: pointer;"><i data-lucide="x"></i></button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_field">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Tên cụm sân <span style="color:red;">*</span></label>
                        <input type="text" name="name" required placeholder="VD: Sân Bóng Chuyên Việt" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Địa chỉ <span style="color:red;">*</span></label>
                        <input type="text" name="address" required placeholder="Số nhà, Tên đường..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Quận/Huyện <span style="color:red;">*</span></label>
                            <select name="district" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                                <option value="Ninh Kiều">Ninh Kiều</option>
                                <option value="Bình Thủy">Bình Thủy</option>
                                <option value="Cái Răng">Cái Răng</option>
                                <option value="Ô Môn">Ô Môn</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Loại sân <span style="color:red;">*</span></label>
                            <select name="type" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                                <option value="5v5">Sân 5 người</option>
                                <option value="7v7">Sân 7 người</option>
                                <option value="11v11">Sân 11 người</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Giá thuê mỗi giờ (VNĐ) <span style="color:red;">*</span></label>
                            <input type="number" name="price_per_hour" required placeholder="VD: 150000" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Phần trăm cọc (%) <span style="color:red;">*</span></label>
                            <input type="number" name="deposit_percent" required min="0" max="100" value="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Gửi yêu cầu kiểm duyệt</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách Sân -->
    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Tên sân</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Loại & Giá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Địa chỉ</th>
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
                                </td>
                                <td style="padding: 15px 20px;">
                                    Sân <?php echo $f['type']; ?><br>
                                    <strong style="color: #ef4444;"><?php echo number_format($f['price_per_hour'], 0, ',', '.'); ?>đ/h</strong>
                                    <?php if ($f['discount_percent'] > 0): ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; margin-left: 4px;">-<?php echo $f['discount_percent']; ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($f['address']); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($f['district']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($f['status'] == 'active'): ?>
                                        <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đang H.động</span>
                                    <?php elseif ($f['status'] == 'pending'): ?>
                                        <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chờ duyệt</span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Bị khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <div style="display: flex; gap: 5px; justify-content: flex-end; align-items: center;">
                                        <a href="edit_field.php?id=<?php echo $f['id']; ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; text-decoration: none; border-color: var(--primary); color: var(--primary); display: inline-flex; align-items: center; gap: 4px; border-radius: 6px; font-weight: 500;">
                                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Chi tiết & Ảnh
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sân này? Mọi dữ liệu đặt sân liên quan sẽ bị mất!');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete_field">
                                            <input type="hidden" name="field_id" value="<?php echo $f['id']; ?>">
                                            <button type="submit" class="btn-logout" style="padding: 6px 12px; font-size: 12px;">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Bạn chưa đăng ký sân bóng nào. Hãy bấm "Thêm sân mới".
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
