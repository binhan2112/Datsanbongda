<?php
// ═══════════════════════════════════════════════════════
// TRANG VIẾT ĐÁNH GIÁ SÂN BÓNG
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
$error = '';
$success = '';

$is_verified = 0;
$field_name = '';
$booking_text = 'Đánh giá tự do (Chưa xác thực đặt sân)';

if ($booking_id > 0) {
    // Lấy thông tin booking (phải là của user và đã completed)
    $stmt = $pdo->prepare("
        SELECT b.*, f.name as field_name, f.address as field_address
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        WHERE b.id = :id AND b.user_id = :uid AND b.status = 'completed'
    ");
    $stmt->execute(['id' => $booking_id, 'uid' => $user_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header("Location: my_bookings.php");
        exit;
    }

    $field_id = $booking['field_id'];
    $field_name = $booking['field_name'];
    $is_verified = 1;
    $booking_text = "Ngày đá: " . date('d/m/Y', strtotime($booking['booking_date'])) . " | Khung: " . date('H:i', strtotime($booking['start_time'])) . " - " . date('H:i', strtotime($booking['end_time']));

    // Kiểm tra đã review chưa
    $check_review = $pdo->prepare("SELECT id FROM reviews WHERE user_id = :uid AND booking_id = :bid");
    $check_review->execute(['uid' => $user_id, 'bid' => $booking_id]);
    if ($check_review->fetch()) {
        header("Location: my_bookings.php?error=already_reviewed");
        exit;
    }
} elseif ($field_id > 0) {
    // Đánh giá tự do
    $stmt = $pdo->prepare("SELECT name FROM fields WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $field_id]);
    $field = $stmt->fetch();
    if (!$field) {
        header("Location: ../index.php");
        exit;
    }
    $field_name = $field['name'];
} else {
    header("Location: ../index.php");
    exit;
}

// Xử lý gửi đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Vui lòng chọn số sao đánh giá (1-5).';
    } elseif (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề đánh giá.';
    } else {
        try {
            $pdo->beginTransaction();

            // Thêm review
            $insert = $pdo->prepare("
                INSERT INTO reviews (field_id, user_id, booking_id, rating, title, comment, is_verified)
                VALUES (:field_id, :uid, :bid, :rating, :title, :comment, :is_verified)
            ");
            $insert->execute([
                'field_id' => $field_id,
                'uid' => $user_id,
                'bid' => $booking_id > 0 ? $booking_id : null,
                'rating' => $rating,
                'title' => $title,
                'comment' => !empty($comment) ? $comment : null,
                'is_verified' => $is_verified
            ]);

            $review_id = $pdo->lastInsertId();

            // Xử lý upload ảnh
            $uploaded_images = [];
            if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
                $uploadFileDir = '../assets/uploads/reviews/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                foreach ($_FILES['review_images']['name'] as $key => $name) {
                    $tmpName = $_FILES['review_images']['tmp_name'][$key];
                    $error = $_FILES['review_images']['error'][$key];
                    $size = $_FILES['review_images']['size'][$key];
                    
                    if ($error === UPLOAD_ERR_OK && $size <= 2 * 1024 * 1024) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowedExtensions)) {
                            $newFileName = 'rev_' . $review_id . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($tmpName, $uploadFileDir . $newFileName)) {
                                $uploaded_images[] = $newFileName;
                            }
                        }
                    }
                }
                
                if (!empty($uploaded_images)) {
                    $json_images = json_encode($uploaded_images);
                    $pdo->prepare("UPDATE reviews SET images = :images WHERE id = :id")->execute([
                        'images' => $json_images,
                        'id' => $review_id
                    ]);
                }
            }

            // Cập nhật rating trung bình và tổng số review của sân
            $avg_stmt = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE field_id = :fid");
            $avg_stmt->execute(['fid' => $field_id]);
            $avg_data = $avg_stmt->fetch();

            $pdo->prepare("UPDATE fields SET rating = :rating, total_reviews = :total WHERE id = :id")
                ->execute([
                    'rating' => round($avg_data['avg_r'], 1),
                    'total' => $avg_data['cnt'],
                    'id' => $field_id
                ]);

            $pdo->commit();

            if ($booking_id > 0) {
                header("Location: my_bookings.php?reviewed=1");
            } else {
                header("Location: detail.php?id=" . $field_id . "&reviewed=1");
            }
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

$base_url = '../';
$current_page = 'bookings';
$page_title = 'Viết Đánh Giá';
include '../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:600px;">
        <h1 class="auth-title">Viết Đánh Giá</h1>
        <p class="auth-subtitle" style="margin-bottom:20px;">
            Sân: <b style="color:var(--primary);"><?php echo htmlspecialchars($field_name); ?></b><br>
            <span style="font-size:13px;"><?php echo $booking_text; ?></span>
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i data-lucide="alert-circle"></i><span><?php echo htmlspecialchars($error); ?></span></div>
        <?php endif; ?>

        <form action="review.php?<?php echo $booking_id > 0 ? 'booking_id='.$booking_id : 'field_id='.$field_id; ?>" method="POST" enctype="multipart/form-data">
            <!-- Star Rating -->
            <div class="form-group" style="margin-bottom:24px;">
                <label>Đánh giá tổng thể *</label>
                <div class="star-rating-input" id="starRating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="star-label" data-value="<?php echo $i; ?>">
                            <input type="radio" name="rating" value="<?php echo $i; ?>" <?php if (isset($_POST['rating']) && $_POST['rating'] == $i) echo 'checked'; ?> required>
                            <i data-lucide="star"></i>
                        </label>
                    <?php endfor; ?>
                    <span class="star-text" id="starText">Chọn số sao</span>
                </div>
            </div>

            <!-- Tiêu đề -->
            <div class="form-group" style="margin-bottom:20px;">
                <label for="title">Tiêu đề đánh giá *</label>
                <input type="text" name="title" id="title" class="form-control" placeholder="VD: Sân rất tốt, đáng tiền!" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <!-- Nội dung -->
            <div class="form-group" style="margin-bottom:20px;">
                <label for="comment">Chi tiết đánh giá</label>
                <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="Chia sẻ trải nghiệm của bạn về mặt cỏ, dịch vụ, tiện ích..."><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
            </div>

            <!-- Hình ảnh -->
            <div class="form-group" style="margin-bottom:24px;">
                <label for="review_images">Đính kèm hình ảnh (Tùy chọn)</label>
                <input type="file" name="review_images[]" id="review_images" class="form-control" multiple accept="image/*" style="padding: 8px 12px; border: 1px dashed var(--border-color); background: rgba(255,255,255,0.01);">
                <small style="font-size:12px;color:var(--text-muted);">Tối đa 2MB mỗi ảnh. Định dạng: JPG, PNG, WEBP.</small>
            </div>

            <div style="display:flex;gap:12px;">
                <a href="<?php echo $booking_id > 0 ? 'my_bookings.php' : 'detail.php?id='.$field_id; ?>" class="btn btn-ghost" style="flex:1;">
                    <i data-lucide="arrow-left"></i> Quay lại
                </a>
                <button type="submit" class="btn btn-primary" style="flex:2;">
                    <i data-lucide="send"></i> Gửi đánh giá
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Star Rating Interactive
    const starLabels = document.querySelectorAll('.star-label');
    const starText = document.getElementById('starText');
    const starTexts = ['', 'Rất tệ', 'Tệ', 'Bình thường', 'Tốt', 'Xuất sắc'];

    starLabels.forEach(label => {
        label.addEventListener('mouseenter', function() {
            const val = parseInt(this.dataset.value);
            highlightStars(val);
            starText.textContent = starTexts[val];
        });

        label.addEventListener('click', function() {
            const val = parseInt(this.dataset.value);
            this.querySelector('input').checked = true;
            highlightStars(val);
            starText.textContent = starTexts[val];
            // Mark as selected
            starLabels.forEach(l => l.classList.remove('selected'));
            for (let i = 0; i < val; i++) {
                starLabels[i].classList.add('selected');
            }
        });
    });

    document.querySelector('.star-rating-input').addEventListener('mouseleave', function() {
        const checked = document.querySelector('.star-label input:checked');
        if (checked) {
            const val = parseInt(checked.value);
            highlightStars(val);
            starText.textContent = starTexts[val];
        } else {
            highlightStars(0);
            starText.textContent = 'Chọn số sao';
        }
    });

    function highlightStars(count) {
        starLabels.forEach((label, i) => {
            const icon = label.querySelector('i, svg');
            if (i < count) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
        });
    }

    // Init if already selected
    const checkedStar = document.querySelector('.star-label input:checked');
    if (checkedStar) {
        const val = parseInt(checkedStar.value);
        highlightStars(val);
        for (let i = 0; i < val; i++) starLabels[i].classList.add('selected');
        starText.textContent = starTexts[val];
    }
</script>

<?php include '../includes/footer.php'; ?>
