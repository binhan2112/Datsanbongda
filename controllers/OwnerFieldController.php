<?php
require_login('owner');
$owner_id = $_SESSION['user_id'];
$field_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = '';

// Kiểm tra sân bóng thuộc quyền sở hữu của chủ sân này
$stmt = $pdo->prepare("SELECT * FROM fields WHERE id = ? AND owner_id = ?");
$stmt->execute([$field_id, $owner_id]);
$field = $stmt->fetch();

if (!$field) {
    header("Location: fields.php");
    exit;
}

// Xử lý Cập Nhật Thông Tin Sân Bóng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_info') {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $district = trim($_POST['district']);
        $price_per_hour = floatval($_POST['price_per_hour']);
        $price_peak = empty($_POST['price_peak']) ? null : floatval($_POST['price_peak']);
        $open_time = $_POST['open_time'] ?? '06:00:00';
        $close_time = $_POST['close_time'] ?? '22:00:00';
        $description = trim($_POST['description'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $lat = floatval($_POST['lat'] ?? 10.0307);
        $lng = floatval($_POST['lng'] ?? 105.7725);
        $type = $_POST['type'] ?? '5v5';
        $surface = $_POST['surface'] ?? 'co_nhan_tao';
        $discount_percent = intval($_POST['discount_percent'] ?? 0);
        if ($discount_percent < 0) $discount_percent = 0;
        if ($discount_percent > 100) $discount_percent = 100;
        
        $deposit_percent = intval($_POST['deposit_percent'] ?? 0);
        if ($deposit_percent < 0) $deposit_percent = 0;
        if ($deposit_percent > 100) $deposit_percent = 100;
        
        $has_lighting = isset($_POST['has_lighting']) ? 1 : 0;
        $has_parking = isset($_POST['has_parking']) ? 1 : 0;
        $has_shower = isset($_POST['has_shower']) ? 1 : 0;
        $has_canteen = isset($_POST['has_canteen']) ? 1 : 0;
        $has_rental = isset($_POST['has_rental']) ? 1 : 0;

        // Process flexible working hours
        $working_hours_json = null;
        if (isset($_POST['working_hours']) && is_array($_POST['working_hours'])) {
            $working_hours_json = json_encode($_POST['working_hours']);
        }

        if (empty($name) || empty($address) || empty($district) || empty($price_per_hour)) {
            $msg = "<span style='color:#dc2626;'>Lỗi: Vui lòng nhập đầy đủ các trường bắt buộc (*).</span>";
        } else {
            try {
                $stmt_update = $pdo->prepare("
                    UPDATE fields SET 
                        name = ?, address = ?, district = ?, price_per_hour = ?, price_peak = ?, 
                        open_time = ?, close_time = ?, working_hours = ?, description = ?, phone = ?, 
                        lat = ?, lng = ?, type = ?, surface = ?,
                        has_lighting = ?, has_parking = ?, has_shower = ?, has_canteen = ?, has_rental = ?,
                        discount_percent = ?, deposit_percent = ?,
                        status = 'pending'
                    WHERE id = ? AND owner_id = ?
                ");
                $stmt_update->execute([
                    $name, $address, $district, $price_per_hour, $price_peak,
                    $open_time, $close_time, $working_hours_json, $description, $phone,
                    $lat, $lng, $type, $surface,
                    $has_lighting, $has_parking, $has_shower, $has_canteen, $has_rental,
                    $discount_percent, $deposit_percent,
                    $field_id, $owner_id
                ]);
                
                // Load lại thông tin
                $stmt->execute([$field_id, $owner_id]);
                $field = $stmt->fetch();
                $msg = "Cập nhật thông tin thành công! Yêu cầu đang đợi quản trị viên phê duyệt lại.";
            } catch (PDOException $e) {
                $msg = "<span style='color:#dc2626;'>Lỗi: " . $e->getMessage() . "</span>";
            }
        }
    }
    
    // Xử lý upload ảnh bìa (Cover Image)
    elseif ($_POST['action'] === 'upload_cover') {
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/fields/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_info = pathinfo($_FILES['cover_image']['name']);
            $file_ext = strtolower($file_info['extension']);
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = 'cover_' . $field_id . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                    // Xóa ảnh bìa cũ nếu có
                    if (!empty($field['cover_image']) && file_exists('../' . $field['cover_image'])) {
                        unlink('../' . $field['cover_image']);
                    }
                    
                    $db_path = 'assets/uploads/fields/' . $new_filename;
                    $stmt_cover = $pdo->prepare("UPDATE fields SET cover_image = ? WHERE id = ?");
                    $stmt_cover->execute([$db_path, $field_id]);
                    
                    $field['cover_image'] = $db_path;
                    $msg = "Đã cập nhật ảnh bìa thành công!";
                } else {
                    $msg = "<span style='color:#dc2626;'>Lỗi khi lưu tệp ảnh lên máy chủ.</span>";
                }
            } else {
                $msg = "<span style='color:#dc2626;'>Định dạng ảnh không hợp lệ (Chỉ nhận JPG, JPEG, PNG, WEBP).</span>";
            }
        } else {
            $msg = "<span style='color:#dc2626;'>Không chọn ảnh bìa hoặc xảy ra lỗi trong quá trình tải ảnh lên.</span>";
        }
    }
    
    // Xử lý upload hình ảnh chi tiết khác (Sub-images)
    elseif ($_POST['action'] === 'upload_sub') {
        if (isset($_FILES['sub_image']) && $_FILES['sub_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/fields/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_info = pathinfo($_FILES['sub_image']['name']);
            $file_ext = strtolower($file_info['extension']);
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = 'sub_' . $field_id . '_' . rand(1000, 9999) . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['sub_image']['tmp_name'], $target_path)) {
                    $db_path = 'assets/uploads/fields/' . $new_filename;
                    $caption = trim($_POST['caption'] ?? '');
                    
                    $stmt_sub = $pdo->prepare("INSERT INTO field_images (field_id, image_path, caption) VALUES (?, ?, ?)");
                    $stmt_sub->execute([$field_id, $db_path, $caption]);
                    
                    $msg = "Đã thêm ảnh chi tiết thành công!";
                } else {
                    $msg = "<span style='color:#dc2626;'>Lỗi khi tải ảnh chi tiết lên máy chủ.</span>";
                }
            } else {
                $msg = "<span style='color:#dc2626;'>Định dạng ảnh không hợp lệ (Chỉ nhận JPG, JPEG, PNG, WEBP).</span>";
            }
        }
    }

    // Xử lý xóa hình ảnh chi tiết
    elseif ($_POST['action'] === 'delete_sub') {
        $img_id = intval($_POST['img_id']);
        $stmt_img = $pdo->prepare("SELECT * FROM field_images WHERE id = ? AND field_id = ?");
        $stmt_img->execute([$img_id, $field_id]);
        $sub_img = $stmt_img->fetch();
        
        if ($sub_img) {
            if (file_exists('../' . $sub_img['image_path'])) {
                unlink('../' . $sub_img['image_path']);
            }
            $stmt_del = $pdo->prepare("DELETE FROM field_images WHERE id = ?");
            $stmt_del->execute([$img_id]);
            $msg = "Đã xóa ảnh chi tiết thành công!";
        }
    }
}

// Lấy danh sách ảnh phụ
$images_stmt = $pdo->prepare("SELECT * FROM field_images WHERE field_id = ? ORDER BY sort_order ASC");
$images_stmt->execute([$field_id]);
$sub_images = $images_stmt->fetchAll();

