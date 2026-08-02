<?php
// ═══════════════════════════════════════════════════════
// TRANG SỰ KIỆN / GIẢI ĐẤU
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

$success = '';
$error = '';

// Xử lý đăng ký tham gia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    if (!is_logged_in()) {
        header("Location: ../auth/login.php");
        exit;
    }

    $event_id = intval($_POST['register_event']);
    $team_name = trim($_POST['team_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if (empty($team_name) || empty($contact)) {
        $error = 'Vui lòng nhập tên đội và số điện thoại liên hệ.';
    } else {
        try {
            $pdo->beginTransaction();

            // Kiểm tra đã đăng ký chưa (khóa để tránh gửi yêu cầu đồng thời)
            $check = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = :eid AND user_id = :uid FOR UPDATE");
            $check->execute(['eid' => $event_id, 'uid' => $_SESSION['user_id']]);
            if ($check->fetch()) {
                $error = 'Bạn đã đăng ký tham gia sự kiện này rồi.';
                $pdo->rollBack();
            } else {
                // Kiểm tra còn slot (sử dụng FOR UPDATE để tránh race condition)
                $event_check = $pdo->prepare("SELECT max_teams, current_teams, title FROM events WHERE id = :id FOR UPDATE");
                $event_check->execute(['id' => $event_id]);
                $evt = $event_check->fetch();

                if ($evt && $evt['max_teams'] !== null && $evt['current_teams'] >= $evt['max_teams']) {
                    $error = 'Sự kiện đã đủ số đội tham gia.';
                    $pdo->rollBack();
                } else {
                    $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, team_name, contact) VALUES (:eid, :uid, :team, :contact)")
                        ->execute(['eid' => $event_id, 'uid' => $_SESSION['user_id'], 'team' => $team_name, 'contact' => $contact]);

                    $pdo->prepare("UPDATE events SET current_teams = current_teams + 1 WHERE id = :id")
                        ->execute(['id' => $event_id]);

                    // Tạo thông báo
                    $evt_title = $evt['title'] ?? 'Giải đấu';

                    $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) VALUES (:uid, 'event_reminder', '🎉 Đăng ký thành công!', :body, 'event', :eid, 'trophy')")
                        ->execute([
                            'uid' => $_SESSION['user_id'],
                            'body' => 'Bạn đã đăng ký đội "' . $team_name . '" cho sự kiện ' . $evt_title . '.',
                            'eid' => $event_id
                        ]);

                    $pdo->commit();
                    $success = 'Đăng ký tham gia giải đấu thành công!';
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách sự kiện
try {
    $stmt = $pdo->query("
        SELECT e.*, f.name as field_name, f.address as field_address, f.district as field_district,
               u.full_name as organizer_name
        FROM events e
        JOIN fields f ON e.field_id = f.id
        JOIN users u ON e.organizer_id = u.id
        WHERE e.status IN ('upcoming', 'ongoing') AND e.approval_status = 'approved'
        ORDER BY e.start_datetime ASC
    ");
    $events = $stmt->fetchAll();

    // Kiểm tra user đã đăng ký những event nào
    $registered_events = [];
    if (is_logged_in()) {
        $reg_stmt = $pdo->prepare("SELECT event_id FROM event_registrations WHERE user_id = :uid");
        $reg_stmt->execute(['uid' => $_SESSION['user_id']]);
        $registered_events = $reg_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

$event_type_labels = [
    'giai_dau' => ['trophy', 'Giải đấu', '#f59e0b'],
    'friendly' => ['handshake', 'Giao hữu', '#3b82f6'],
    'training' => ['dumbbell', 'Tập luyện', '#10b981'],
    'other'    => ['calendar', 'Khác', '#8b5cf6'],
];

$base_url = '../';
$current_page = 'events';
$page_title = 'Sự Kiện';
include '../includes/header.php';
?>

<div class="container" style="margin-top:40px;margin-bottom:80px;">
    <!-- Page Header -->
    <div style="text-align:center;margin-bottom:48px;">
        <div class="hero-eyebrow" style="display:inline-flex;">
            <span></span> Các sự kiện bóng đá
        </div>
        <h1 style="font-size:clamp(28px,5vw,48px);font-weight:900;letter-spacing:-1.5px;margin-bottom:16px;">
            Sự Kiện <span class="gradient-text">Bóng Đá Cần Thơ</span>
        </h1>
        <p style="font-size:17px;color:var(--text-secondary);max-width:560px;margin:0 auto;">
            Tham gia các sự kiện, giao hữu hấp dẫn tại các sân bóng tốt nhất Cần Thơ. Đăng ký ngay!
        </p>
    </div>

    <!-- Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><i data-lucide="check-circle"></i><span><?php echo htmlspecialchars($success); ?></span></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i data-lucide="alert-circle"></i><span><?php echo htmlspecialchars($error); ?></span></div>
    <?php endif; ?>

    <?php if (count($events) > 0): ?>
        <div class="events-grid fade-in-up">
            <?php foreach ($events as $evt): ?>
                <?php
                    $type_info = $event_type_labels[$evt['event_type']] ?? $event_type_labels['other'];
                    $is_registered = in_array($evt['id'], $registered_events);
                    $slots_left = ($evt['max_teams'] !== null) ? ($evt['max_teams'] - $evt['current_teams']) : null;
                    $is_full = ($slots_left !== null && $slots_left <= 0);
                ?>
                <div class="event-card">
                    <div class="event-card-header">
                        <div class="event-type-badge" style="background:<?php echo $type_info[2]; ?>15;color:<?php echo $type_info[2]; ?>;border-color:<?php echo $type_info[2]; ?>40;">
                            <i data-lucide="<?php echo $type_info[0]; ?>" style="width:14px;height:14px;"></i>
                            <?php echo $type_info[1]; ?>
                        </div>
                        <span class="event-status-badge event-status-<?php echo $evt['status']; ?>">
                            <?php echo $evt['status'] === 'upcoming' ? 'Sắp diễn ra' : 'Đang diễn ra'; ?>
                        </span>
                    </div>

                    <h3 class="event-title"><?php echo htmlspecialchars($evt['title']); ?></h3>

                    <div class="event-meta">
                        <div class="event-meta-item">
                            <i data-lucide="map-pin"></i>
                            <span><?php echo htmlspecialchars($evt['field_name']); ?> — Q. <?php echo htmlspecialchars($evt['field_district']); ?></span>
                        </div>
                        <div class="event-meta-item">
                            <i data-lucide="calendar"></i>
                            <span><?php echo date('d/m/Y', strtotime($evt['start_datetime'])); ?>
                            <?php if (date('Y-m-d', strtotime($evt['start_datetime'])) !== date('Y-m-d', strtotime($evt['end_datetime']))): ?>
                                — <?php echo date('d/m/Y', strtotime($evt['end_datetime'])); ?>
                            <?php endif; ?>
                            </span>
                        </div>
                        <div class="event-meta-item">
                            <i data-lucide="clock"></i>
                            <span><?php echo date('H:i', strtotime($evt['start_datetime'])); ?> — <?php echo date('H:i', strtotime($evt['end_datetime'])); ?></span>
                        </div>
                        <div class="event-meta-item">
                            <i data-lucide="user"></i>
                            <span>Tổ chức: <?php echo htmlspecialchars($evt['organizer_name']); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($evt['description'])): ?>
                        <p class="event-desc"><?php echo htmlspecialchars(mb_strimwidth($evt['description'], 0, 150, '...')); ?></p>
                    <?php endif; ?>

                    <div class="event-footer">
                        <div class="event-stats">
                            <?php if ($evt['max_teams'] !== null): ?>
                                <div class="event-stat-chip">
                                    <i data-lucide="users" style="width:13px;height:13px;"></i>
                                    <?php echo $evt['current_teams']; ?>/<?php echo $evt['max_teams']; ?> đội
                                </div>
                            <?php endif; ?>
                            <div class="event-stat-chip">
                                <i data-lucide="ticket" style="width:13px;height:13px;"></i>
                                <?php echo $evt['entry_fee'] > 0 ? number_format($evt['entry_fee'], 0, ',', '.') . 'đ' : 'Miễn phí'; ?>
                            </div>
                            <?php if (!empty($evt['prize_pool'])): ?>
                                <div class="event-stat-chip" style="color:var(--rating-color);">
                                    <i data-lucide="award" style="width:13px;height:13px;"></i>
                                    <?php echo number_format($evt['prize_pool'], 0, ',', '.'); ?>đ
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_registered): ?>
                            <span class="btn btn-sm" style="background:var(--primary-subtle);color:var(--primary);border:1px solid var(--border-primary);cursor:default;">
                                <i data-lucide="check-circle" style="width:14px;height:14px;"></i> Đã đăng ký
                            </span>
                        <?php elseif ($is_full): ?>
                            <span class="btn btn-sm" style="background:rgba(148,163,184,0.1);color:var(--text-muted);border:1px solid var(--border);cursor:default;">
                                Đã đầy
                            </span>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openRegisterModal(<?php echo $evt['id']; ?>, '<?php echo htmlspecialchars(addslashes($evt['title'])); ?>')">
                                <i data-lucide="plus-circle" style="width:14px;height:14px;"></i> Đăng ký
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Progress bar for slots -->
                    <?php if ($evt['max_teams'] !== null): ?>
                        <div class="event-progress-bar">
                            <div class="event-progress-fill" style="width:<?php echo min(100, ($evt['current_teams'] / $evt['max_teams']) * 100); ?>%;"></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state fade-in-up">
            <i data-lucide="trophy"></i>
            <p>Hiện tại chưa có sự kiện hoặc giải đấu nào sắp tới.</p>
            <a href="../index.php" class="btn btn-primary">Về trang chủ</a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Đăng Ký -->
<div id="registerModal" class="modal-overlay" style="display:none;">
    <div class="auth-card" style="max-width:450px;position:relative;">
        <button type="button" onclick="closeRegisterModal()" style="position:absolute;top:16px;right:16px;background:transparent;border:none;color:var(--text-muted);cursor:pointer;">
            <i data-lucide="x"></i>
        </button>
        <h2 class="auth-title" style="font-size:22px;">Đăng Ký Tham Gia</h2>
        <p class="auth-subtitle" id="modalEventTitle" style="margin-bottom:20px;"></p>

        <form action="events.php" method="POST">
            <input type="hidden" name="register_event" id="modalEventId" value="">

            <div class="form-group" style="margin-bottom:20px;">
                <label for="team_name">Tên đội *</label>
                <input type="text" name="team_name" id="team_name" class="form-control" placeholder="VD: FC Cần Thơ" required>
            </div>

            <div class="form-group" style="margin-bottom:24px;">
                <label for="contact_phone">Số điện thoại liên hệ *</label>
                <input type="text" name="contact" id="contact_phone" class="form-control" placeholder="0901234567" required
                    value="<?php echo is_logged_in() ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;height:48px;">
                <i data-lucide="check-circle"></i> Xác nhận đăng ký
            </button>
        </form>
    </div>
</div>

<script>
    function openRegisterModal(eventId, eventTitle) {
        <?php if (!is_logged_in()): ?>
            window.location.href = '../auth/login.php';
            return;
        <?php endif; ?>
        document.getElementById('modalEventId').value = eventId;
        document.getElementById('modalEventTitle').textContent = eventTitle;
        document.getElementById('registerModal').style.display = 'flex';
    }

    function closeRegisterModal() {
        document.getElementById('registerModal').style.display = 'none';
    }

    document.getElementById('registerModal').addEventListener('click', function(e) {
        if (e.target === this) closeRegisterModal();
    });
</script>

<?php include '../includes/footer.php'; ?>
