<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$msg = '';

$active_menu = 'rewards';
$owner_id = $_SESSION['user_id'];

// Xử lý POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_reward') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $points_required = intval($_POST['points_required']);
        $quantity = intval($_POST['quantity']);
        $status = $_POST['status'] ?? 'active';

        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/rewards/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = 'assets/images/rewards/' . $filename;
            }
        }

        if (empty($name) || $points_required <= 0) {
            $msg = "<span style='color:#dc2626;'>Lỗi: Vui lòng nhập Tên phần quà và Điểm đổi hợp lệ.</span>";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO rewards (owner_id, name, description, points_required, quantity, image_url, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$owner_id, $name, $description, $points_required, $quantity, $image_url, $status]);
                $msg = "Thêm phần quà thành công!";
            } catch (PDOException $e) {
                $msg = "<span style='color:#dc2626;'>Lỗi: " . $e->getMessage() . "</span>";
            }
        }
    } elseif ($_POST['action'] === 'update_reward_status') {
        $reward_id = intval($_POST['reward_id']);
        $status = $_POST['status'];
        if (in_array($status, ['active', 'inactive'])) {
            $stmt = $pdo->prepare("UPDATE rewards SET status = ? WHERE id = ? AND owner_id = ?");
            $stmt->execute([$status, $reward_id, $owner_id]);
            $msg = "Cập nhật trạng thái phần quà thành công!";
        }
    } elseif ($_POST['action'] === 'delete_reward') {
        $reward_id = intval($_POST['reward_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM rewards WHERE id = ? AND owner_id = ?");
            $stmt->execute([$reward_id, $owner_id]);
            $msg = "Đã xóa phần quà thành công!";
        } catch (PDOException $e) {
            $msg = "<span style='color:#dc2626;'>Lỗi: Không thể xóa phần quà này.</span>";
        }
    } elseif ($_POST['action'] === 'update_exchange_status') {
        $exchange_id = intval($_POST['exchange_id']);
        $status = $_POST['status'];
        if (in_array($status, ['pending', 'approved', 'rejected', 'delivered'])) {
            try {
                // Kiểm tra xem yêu cầu đổi quà này có phải của quà chủ sân này không
                $stmt_check = $pdo->prepare("
                    SELECT re.id FROM reward_exchanges re
                    JOIN rewards r ON re.reward_id = r.id
                    WHERE re.id = ? AND r.owner_id = ?
                ");
                $stmt_check->execute([$exchange_id, $owner_id]);
                if ($stmt_check->rowCount() > 0) {
                    $stmt = $pdo->prepare("UPDATE reward_exchanges SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $exchange_id]);
                    $msg = "Cập nhật trạng thái yêu cầu đổi quà thành công!";
                } else {
                    $msg = "<span style='color:#dc2626;'>Lỗi: Không tìm thấy yêu cầu hoặc không có quyền sửa.</span>";
                }
            } catch (PDOException $e) {
                $msg = "<span style='color:#dc2626;'>Lỗi: " . $e->getMessage() . "</span>";
            }
        }
    }
}

// Lấy danh sách phần quà của chủ sân này
$stmt = $pdo->prepare("SELECT * FROM rewards WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$owner_id]);
$rewards = $stmt->fetchAll();

// Lấy danh sách yêu cầu đổi quà đối với quà của chủ sân
$stmt_exc = $pdo->prepare("
    SELECT re.*, u.full_name, u.phone, r.name as reward_name, r.image_url
    FROM reward_exchanges re
    JOIN users u ON re.user_id = u.id
    JOIN rewards r ON re.reward_id = r.id
    WHERE r.owner_id = ?
    ORDER BY re.exchange_date DESC
");
$stmt_exc->execute([$owner_id]);
$exchanges = $stmt_exc->fetchAll();

$page_title = 'Quản lý Quà tặng Của Sân';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Quản lý Quà tặng (Sân của tôi)</h2>
        <button onclick="document.getElementById('add-reward-modal').style.display='flex';" class="btn" style="display: flex; align-items: center; gap: 8px; background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600;">
            <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Thêm phần quà mới
        </button>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border); font-weight: 500;">
            <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Thêm Quà Tặng -->
    <div id="add-reward-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
            <div class="card-header">
                <h3 class="card-title">Thêm phần quà mới</h3>
                <button onclick="document.getElementById('add-reward-modal').style.display='none';" style="background: none; border: none; cursor: pointer;"><i data-lucide="x"></i></button>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_reward">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Tên phần quà <span style="color:red;">*</span></label>
                        <input type="text" name="name" required placeholder="VD: Nước suối / Giảm giá 20%" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Mô tả</label>
                        <textarea name="description" rows="3" placeholder="Chi tiết phần quà..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; resize: vertical;"></textarea>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Điểm đổi yêu cầu <span style="color:red;">*</span></label>
                            <input type="number" name="points_required" required min="1" placeholder="VD: 50" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Số lượng (0 = Hết hàng)</label>
                            <input type="number" name="quantity" required min="0" value="10" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Hình ảnh minh họa</label>
                        <input type="file" name="image" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Trạng thái</label>
                        <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="active">Kích hoạt (Hiển thị cho khách hàng)</option>
                            <option value="inactive">Tạm ẩn</option>
                        </select>
                    </div>
                    
                    <button type="submit" style="width: 100%; background: var(--primary); color: white; padding: 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; font-size: 15px;">Lưu phần quà</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách phần quà -->
    <h3 style="margin-bottom: 15px; font-size: 18px; color: var(--dark);">Danh sách phần quà của sân</h3>
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 80px;">Hình ảnh</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Tên phần quà</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Điểm đổi</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Số lượng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Trạng thái</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rewards) > 0): ?>
                        <?php foreach ($rewards as $r): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <?php if ($r['image_url']): ?>
                                        <img src="<?php echo $base_url . $r['image_url']; ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8;"><i data-lucide="image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; font-weight: 600; color: var(--dark);">
                                    <?php echo htmlspecialchars($r['name']); ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <span style="color: var(--warning); font-weight: 700; display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="coins" style="width:14px; height:14px;"></i> <?php echo number_format($r['points_required'], 0, ',', '.'); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo $r['quantity']; ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($r['status'] === 'active'): ?>
                                        <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đang hoạt động</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Tạm ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                        <!-- Thay đổi trạng thái -->
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="action" value="update_reward_status">
                                            <input type="hidden" name="reward_id" value="<?php echo $r['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); font-size: 12px;">
                                                <option value="active" <?php if($r['status'] === 'active') echo 'selected'; ?>>Kích hoạt</option>
                                                <option value="inactive" <?php if($r['status'] === 'inactive') echo 'selected'; ?>>Tạm ẩn</option>
                                            </select>
                                        </form>

                                        <!-- Xóa -->
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA phần quà này không? Các lịch sử đổi thưởng liên quan cũng sẽ bị xóa!');">
                                            <input type="hidden" name="action" value="delete_reward">
                                            <input type="hidden" name="reward_id" value="<?php echo $r['id']; ?>">
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
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Sân của bạn chưa có phần quà nào được tạo.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Danh sách yêu cầu đổi quà -->
    <h3 style="margin-bottom: 15px; font-size: 18px; color: var(--dark);">Yêu cầu đổi quà (từ khách hàng)</h3>
    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Thời gian</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Người dùng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Phần quà</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Điểm tiêu dùng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Trạng thái</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); text-align: right;">Cập nhật</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($exchanges) > 0): ?>
                        <?php foreach ($exchanges as $ex): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <?php echo date('d/m/Y H:i', strtotime($ex['exchange_date'] ?? $ex['updated_at'])); ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($ex['full_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($ex['phone']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if ($ex['image_url']): ?>
                                            <img src="<?php echo $base_url . $ex['image_url']; ?>" style="width: 30px; height: 30px; border-radius: 4px; object-fit: cover;">
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($ex['reward_name']); ?></span>
                                    </div>
                                </td>
                                <td style="padding: 15px 20px; color: var(--warning); font-weight: bold;">
                                    -<?php echo number_format($ex['points_used'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($ex['status'] === 'pending'): ?>
                                        <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chờ duyệt</span>
                                    <?php elseif ($ex['status'] === 'approved'): ?>
                                        <span style="background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã duyệt</span>
                                    <?php elseif ($ex['status'] === 'delivered'): ?>
                                        <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã giao</span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Từ chối</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="update_exchange_status">
                                        <input type="hidden" name="exchange_id" value="<?php echo $ex['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); font-size: 12px;">
                                            <option value="pending" <?php if($ex['status'] === 'pending') echo 'selected'; ?>>Chờ duyệt</option>
                                            <option value="approved" <?php if($ex['status'] === 'approved') echo 'selected'; ?>>Duyệt (Chờ lấy)</option>
                                            <option value="delivered" <?php if($ex['status'] === 'delivered') echo 'selected'; ?>>Đã giao quà</option>
                                            <option value="rejected" <?php if($ex['status'] === 'rejected') echo 'selected'; ?>>Từ chối</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Chưa có yêu cầu đổi quà nào từ khách hàng.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>

<?php include '../includes/dashboard_footer.php'; ?>
