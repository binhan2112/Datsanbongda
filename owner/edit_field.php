<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_once '../controllers/OwnerFieldController.php';
$page_title = 'Chi tiết & Quản lý Hình ảnh Sân';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h2 style="margin:0;">Chi tiết & Cập nhật Sân bóng</h2>
        <a href="fields.php" class="btn btn-outline" style="text-decoration:none; display:inline-flex; align-items:center; gap:5px; font-size:14px; font-weight:500;">
            <i data-lucide="arrow-left"></i> Quay lại
        </a>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid var(--border); font-weight: 500;">
            <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 25px;">
        <!-- Cột trái: Form Thông tin sân bóng -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">Cập nhật thông tin</h3></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_info">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Tên sân bóng <span style="color:red;">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($field['name']); ?>" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Địa chỉ cụ thể <span style="color:red;">*</span></label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($field['address']); ?>" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Quận / Huyện <span style="color:red;">*</span></label>
                            <select name="district" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                                <option value="Ninh Kiều" <?php if($field['district'] === 'Ninh Kiều') echo 'selected'; ?>>Ninh Kiều</option>
                                <option value="Bình Thủy" <?php if($field['district'] === 'Bình Thủy') echo 'selected'; ?>>Bình Thủy</option>
                                <option value="Cái Răng" <?php if($field['district'] === 'Cái Răng') echo 'selected'; ?>>Cái Răng</option>
                                <option value="Ô Môn" <?php if($field['district'] === 'Ô Môn') echo 'selected'; ?>>Ô Môn</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Số điện thoại sân</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($field['phone'] ?? ''); ?>" placeholder="Số ĐT sân..." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Loại sân <span style="color:red;">*</span></label>
                            <select name="type" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                                <option value="5v5" <?php if($field['type'] === '5v5') echo 'selected'; ?>>Sân 5</option>
                                <option value="7v7" <?php if($field['type'] === '7v7') echo 'selected'; ?>>Sân 7</option>
                                <option value="11v11" <?php if($field['type'] === '11v11') echo 'selected'; ?>>Sân 11</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Bề mặt cỏ</label>
                            <select name="surface" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                                <option value="co_nhan_tao" <?php if($field['surface'] === 'co_nhan_tao') echo 'selected'; ?>>Cỏ nhân tạo</option>
                                <option value="co_tu_nhien" <?php if($field['surface'] === 'co_tu_nhien') echo 'selected'; ?>>Cỏ tự nhiên</option>
                                <option value="san_xi_mang" <?php if($field['surface'] === 'san_xi_mang') echo 'selected'; ?>>Sân xi măng</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Giá giờ thường (đ/h) <span style="color:red;">*</span></label>
                            <input type="number" name="price_per_hour" value="<?php echo $field['price_per_hour']; ?>" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Giá giờ cao điểm (đ/h)</label>
                            <input type="number" name="price_peak" value="<?php echo $field['price_peak'] ?? ''; ?>" placeholder="Từ 17h-21h..." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Mức giảm giá (%)</label>
                            <input type="number" name="discount_percent" value="<?php echo $field['discount_percent']; ?>" min="0" max="100" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Phần trăm cọc (%) <span style="color:red;">*</span></label>
                            <input type="number" name="deposit_percent" required value="<?php echo $field['deposit_percent'] ?? 0; ?>" min="0" max="100" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                        </div>
                    </div>

                    <?php 
                        $wh = [];
                        if (!empty($field['working_hours'])) {
                            $wh = json_decode($field['working_hours'], true);
                        }
                        $days = [
                            1 => 'Thứ Hai',
                            2 => 'Thứ Ba',
                            3 => 'Thứ Tư',
                            4 => 'Thứ Năm',
                            5 => 'Thứ Sáu',
                            6 => 'Thứ Bảy',
                            0 => 'Chủ Nhật'
                        ];
                    ?>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:10px; font-weight:500;">Giờ hoạt động theo ngày <span style="color:var(--text-muted); font-size:12px; font-weight:normal;">(Ghi đè giờ mặc định)</span></label>
                        <div style="border: 1px solid var(--border); border-radius: 8px; padding: 15px; background: #fafafa;">
                            <div style="display:flex; gap:15px; margin-bottom:15px; padding-bottom:15px; border-bottom: 1px solid var(--border);">
                                <div style="flex:1;">
                                    <label style="display:block; margin-bottom:5px; font-weight:500; font-size: 13px;">Giờ mở cửa mặc định</label>
                                    <input type="time" name="open_time" value="<?php echo $field['open_time']; ?>" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px;" required>
                                </div>
                                <div style="flex:1;">
                                    <label style="display:block; margin-bottom:5px; font-weight:500; font-size: 13px;">Giờ đóng cửa mặc định</label>
                                    <input type="time" name="close_time" value="<?php echo $field['close_time']; ?>" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px;" required>
                                </div>
                            </div>

                            <?php foreach ($days as $day_index => $day_name): 
                                $day_data = $wh[$day_index] ?? null;
                                $d_open = $day_data ? ($day_data['open'] ?? '') : '';
                                $d_close = $day_data ? ($day_data['close'] ?? '') : '';
                                $d_closed = ($day_data && !empty($day_data['closed'])) ? 'checked' : '';
                            ?>
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom: 10px;">
                                <div style="width: 80px; font-weight: 500; font-size: 14px;"><?php echo $day_name; ?></div>
                                <input type="time" name="working_hours[<?php echo $day_index; ?>][open]" value="<?php echo $d_open; ?>" style="flex:1; padding:6px; border:1px solid var(--border); border-radius:4px;" title="Giờ mở cửa">
                                <span>-</span>
                                <input type="time" name="working_hours[<?php echo $day_index; ?>][close]" value="<?php echo $d_close; ?>" style="flex:1; padding:6px; border:1px solid var(--border); border-radius:4px;" title="Giờ đóng cửa">
                                <label style="display:flex; align-items:center; gap:4px; font-size: 13px; margin-left: 10px; cursor: pointer;">
                                    <input type="checkbox" name="working_hours[<?php echo $day_index; ?>][closed]" value="1" <?php echo $d_closed; ?>> Nghỉ
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <p style="font-size: 12px; color: var(--text-muted); margin-top: 10px; font-style: italic;">* Nếu để trống giờ mở/đóng ở từng ngày, hệ thống sẽ tự dùng giờ mặc định bên trên.</p>
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Vĩ độ GPS (Lat)</label>
                            <input type="text" name="lat" value="<?php echo $field['lat']; ?>" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Kinh độ GPS (Lng)</label>
                            <input type="text" name="lng" value="<?php echo $field['lng']; ?>" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:10px; font-weight:500;">Tiện ích sẵn có</label>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="has_lighting" <?php if($field['has_lighting']) echo 'checked'; ?>> Đèn chiếu sáng
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="has_parking" <?php if($field['has_parking']) echo 'checked'; ?>> Bãi giữ xe
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="has_shower" <?php if($field['has_shower']) echo 'checked'; ?>> Phòng tắm/thay đồ
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="has_canteen" <?php if($field['has_canteen']) echo 'checked'; ?>> Căn tin/Nước uống
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="has_rental" <?php if($field['has_rental']) echo 'checked'; ?>> Cho thuê giày/áo
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Mô tả chi tiết</label>
                        <textarea name="description" rows="5" placeholder="Thông tin giới thiệu về sân, đường đi, liên hệ..." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-family:inherit; resize:vertical;"><?php echo htmlspecialchars($field['description'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; font-weight:600; height:45px;">Lưu thay đổi & Đợi duyệt lại</button>
                </form>
            </div>
        </div>

        <!-- Cột phải: Quản lý Ảnh (Bìa & Chi tiết) -->
        <div style="display:flex; flex-direction:column; gap:25px;">
            <!-- Ảnh bìa -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Ảnh bìa (Cover Image)</h3></div>
                <div class="card-body" style="text-align:center;">
                    <?php if (!empty($field['cover_image'])): ?>
                        <img src="../<?php echo $field['cover_image']; ?>" alt="Ảnh bìa" style="width:100%; height:180px; object-fit:cover; border-radius:8px; margin-bottom:15px; border:1px solid var(--border);">
                    <?php else: ?>
                        <div style="width:100%; height:180px; background:#f1f5f9; border-radius:8px; margin-bottom:15px; display:flex; align-items:center; justify-content:center; color:var(--text-muted);">
                            Chưa có ảnh bìa
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_cover">
                        <input type="file" name="cover_image" required style="display:block; margin-bottom:10px; width:100%;">
                        <button type="submit" class="btn btn-outline" style="width:100%; display:inline-flex; align-items:center; justify-content:center; gap:5px;">
                            <i data-lucide="upload-cloud"></i> Cập nhật ảnh bìa
                        </button>
                    </form>
                </div>
            </div>

            <!-- Album ảnh chi tiết -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Hình ảnh chi tiết (Album)</h3></div>
                <div class="card-body">
                    <!-- Album grid -->
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap:10px; margin-bottom:20px;">
                        <?php foreach ($sub_images as $img): ?>
                            <div style="position:relative; width:80px; height:80px; border-radius:6px; overflow:hidden; border:1px solid var(--border);">
                                <img src="../<?php echo $img['image_path']; ?>" alt="Album" style="width:100%; height:100%; object-fit:cover;">
                                <form method="POST" style="position:absolute; top:2px; right:2px;" onsubmit="return confirm('Xóa ảnh này?');">
                                    <input type="hidden" name="action" value="delete_sub">
                                    <input type="hidden" name="img_id" value="<?php echo $img['id']; ?>">
                                    <button type="submit" style="background:#ef4444; color:white; border:none; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:10px;">
                                        <i data-lucide="x" style="width:12px; height:12px;"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($sub_images)): ?>
                            <p style="color:var(--text-muted); font-size:13px; grid-column: 1/-1; text-align:center;">Chưa có ảnh chi tiết nào.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Thêm ảnh -->
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_sub">
                        <div style="margin-bottom:10px;">
                            <input type="file" name="sub_image" required style="width:100%; margin-bottom:5px;">
                            <input type="text" name="caption" placeholder="Mô tả ảnh (không bắt buộc)..." style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:4px; font-size:13px;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; display:inline-flex; align-items:center; justify-content:center; gap:5px;">
                            <i data-lucide="plus"></i> Thêm ảnh vào album
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
