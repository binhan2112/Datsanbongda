<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
require_login('admin');

try {
    $total_users     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_owners    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='owner'")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    $total_fields    = $pdo->query("SELECT COUNT(*) FROM fields")->fetchColumn();
    $active_fields   = $pdo->query("SELECT COUNT(*) FROM fields WHERE status='active'")->fetchColumn();
    $pending_fields  = $pdo->query("SELECT COUNT(*) FROM fields WHERE status='pending'")->fetchColumn();
    $total_bookings  = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
    $today_bookings  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE() AND status NOT IN ('cancelled','no_show')")->fetchColumn();
    $total_revenue   = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status='completed'")->fetchColumn();
    $month_revenue   = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE MONTH(booking_date)=MONTH(CURDATE()) AND YEAR(booking_date)=YEAR(CURDATE()) AND status IN ('completed','confirmed')")->fetchColumn();
    $total_reviews   = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    $new_users_month = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

    // Bookings gần đây
    $recent_bookings = $pdo->query("
        SELECT b.booking_code, b.booking_date, b.start_time, b.end_time, b.total_price, b.status,
               u.full_name as customer_name, u.phone, f.name as field_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fields f ON b.field_id = f.id
        ORDER BY b.created_at DESC LIMIT 8
    ")->fetchAll();

    // Sân mới chờ duyệt
    $pending_list = $pdo->query("
        SELECT f.id, f.name, f.district, f.type, f.price_per_hour, f.created_at, u.full_name as owner_name
        FROM fields f JOIN users u ON f.owner_id = u.id
        WHERE f.status='pending'
        ORDER BY f.created_at ASC LIMIT 5
    ")->fetchAll();

    // Biểu đồ doanh thu 6 tháng
    $monthly_rev = $pdo->query("
        SELECT DATE_FORMAT(booking_date, '%m/%Y') as month_label, SUM(total_price) as total
        FROM bookings
        WHERE status IN ('completed','confirmed')
        GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
        ORDER BY booking_date DESC LIMIT 6
    ")->fetchAll();
    $monthly_rev = array_reverse($monthly_rev);

    // Biểu đồ trạng thái đơn
    $status_dist = $pdo->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Top 5 sân được đặt nhiều nhất
    $top_fields = $pdo->query("
        SELECT f.name, f.district, COUNT(b.id) as bookings_count, COALESCE(SUM(b.total_price),0) as revenue
        FROM fields f
        LEFT JOIN bookings b ON f.id = b.field_id AND b.status IN ('completed','confirmed')
        WHERE f.status = 'active'
        GROUP BY f.id ORDER BY bookings_count DESC LIMIT 5
    ")->fetchAll();

    // Hoạt động hôm nay
    $today_activity = $pdo->query("
        SELECT b.booking_code, b.start_time, b.end_time, b.status, b.total_price,
               u.full_name, f.name as field_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fields f ON b.field_id = f.id
        WHERE b.booking_date = CURDATE()
        ORDER BY b.start_time ASC LIMIT 6
    ")->fetchAll();

} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

$page_title = 'Tổng quan Hệ thống';
$base_url = '../';
$active_menu = 'index';
include '../includes/dashboard_header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 16px; margin-bottom: 24px; }
.kpi-box { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); border: 1px solid var(--border); position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
.kpi-box:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.kpi-box::after { content: ''; position: absolute; right: -16px; bottom: -16px; width: 72px; height: 72px; border-radius: 50%; opacity: 0.1; background: var(--accent); }
.kpi-icon { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.kpi-val { font-size: 24px; font-weight: 800; color: var(--dark); }
.kpi-lbl { font-size: 12px; color: var(--text-muted); font-weight: 600; margin-top: 2px; }
.kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 6px; }

.live-badge { display: inline-flex; align-items: center; gap: 5px; background: rgba(16,185,129,0.1); color: #059669; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.live-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }

.dash-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
.dash-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.dash-card { background: white; border-radius: 14px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); border: 1px solid var(--border); overflow: hidden; }
.dash-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.dash-card-header h3 { font-size: 15px; font-weight: 700; color: var(--dark); margin: 0; }
.dash-card-body { padding: 20px; }

.status-pill { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.pill-pending  { background:#fef3c7;color:#d97706; }
.pill-confirmed { background:#dbeafe;color:#2563eb; }
.pill-completed { background:#dcfce7;color:#166534; }
.pill-cancelled { background:#fee2e2;color:#dc2626; }
.pill-no_show   { background:#f1f5f9;color:#64748b; }

.today-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.today-row:last-child { border-bottom: none; }
.time-range { font-size: 13px; font-weight: 700; color: var(--dark); min-width: 90px; }

.field-rank-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.field-rank-row:last-child { border-bottom: none; }
.rank-num { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; }
.r1 { background:#fef3c7;color:#d97706; }
.r2 { background:#f1f5f9;color:#475569; }
.r3 { background:#fff7ed;color:#c2410c; }
.r-else { background:#f8fafc;color:#94a3b8; }

/* Alert indicator */
.alert-count { background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
</style>

<div>
    <!-- Top: Live indicator + Summary -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
            <h2 style="font-size:22px;font-weight:800;color:var(--dark);margin-bottom:4px;">Bảng Điều Khiển</h2>
            <p style="font-size:13px;color:var(--text-muted);">Cập nhật: <span id="lastUpdate"><?=date('H:i:s')?></span></p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <span class="live-badge"><span class="live-dot"></span> LIVE</span>
            <a href="stats.php" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                <i data-lucide="bar-chart-2" style="width:16px;height:16px;"></i> Thống kê chi tiết
            </a>
        </div>
    </div>

    <!-- KPI Grid (8 boxes) -->
    <div class="kpi-grid">
        <?php $kpis = [
            ['icon'=>'users','bg'=>'rgba(59,130,246,0.12)','accent'=>'#3b82f6','val'=>$total_users,'lbl'=>'Tổng người dùng','sub'=>"+{$new_users_month} tháng này"],
            ['icon'=>'map','bg'=>'rgba(0,191,166,0.12)','accent'=>'#00bfa6','val'=>$active_fields,'lbl'=>'Sân đang hoạt động','sub'=>"$total_fields tổng sân"],
            ['icon'=>'clock','bg'=>'rgba(245,158,11,0.12)','accent'=>'#f59e0b','val'=>$pending_fields,'lbl'=>'Sân chờ duyệt','sub'=>'Cần xem xét'],
            ['icon'=>'calendar-check','bg'=>'rgba(139,92,246,0.12)','accent'=>'#8b5cf6','val'=>$total_bookings,'lbl'=>'Tổng lượt đặt','sub'=>"Hôm nay: {$today_bookings}"],
            ['icon'=>'alert-circle','bg'=>'rgba(239,68,68,0.12)','accent'=>'#ef4444','val'=>$pending_bookings,'lbl'=>'Đơn chờ xử lý','sub'=>'Cần duyệt ngay','id'=>'livePending'],
            ['icon'=>'wallet','bg'=>'rgba(16,185,129,0.12)','accent'=>'#10b981','val'=>number_format($month_revenue,0,',','.').'đ','lbl'=>'Doanh thu tháng này','sub'=>'Đã xác nhận + Hoàn thành'],
            ['icon'=>'trending-up','bg'=>'rgba(16,185,129,0.12)','accent'=>'#10b981','val'=>number_format($total_revenue,0,',','.').'đ','lbl'=>'Tổng doanh thu','sub'=>'Trạng thái Completed'],
            ['icon'=>'star','bg'=>'rgba(251,191,36,0.12)','accent'=>'#fbbf24','val'=>$total_reviews,'lbl'=>'Đánh giá nhận được','sub'=>'Tổng đánh giá'],
        ];
        foreach ($kpis as $k): ?>
        <?php
            $box_id  = isset($k['id']) ? " id=\"{$k['id']}Box\"" : '';
            $val_id  = isset($k['id']) ? " id=\"{$k['id']}Val\"" : '';
        ?>
        <div class="kpi-box"<?php echo $box_id; ?> style="--accent:<?=$k['accent']?>;">
            <div class="kpi-icon" style="background:<?=$k['bg']?>;color:<?=$k['accent']?>;">
                <i data-lucide="<?=$k['icon']?>" style="width:20px;height:20px;"></i>
            </div>
            <div class="kpi-val"<?php echo $val_id; ?>><?=htmlspecialchars($k['val'])?></div>
            <div class="kpi-lbl"><?=$k['lbl']?></div>
            <div class="kpi-sub"><?=$k['sub']?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Row 1: Recent Bookings + Pending Fields -->
    <div class="dash-grid-2">
        <!-- Recent Bookings -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3>Đơn đặt sân gần đây</h3>
                <a href="bookings.php" style="font-size:13px;color:var(--primary);text-decoration:none;font-weight:600;">Xem tất cả →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid var(--border);">
                            <th style="padding:10px 16px;font-weight:600;color:var(--text-muted);text-align:left;">Mã đơn</th>
                            <th style="padding:10px 16px;font-weight:600;color:var(--text-muted);text-align:left;">Khách hàng</th>
                            <th style="padding:10px 16px;font-weight:600;color:var(--text-muted);text-align:left;">Sân</th>
                            <th style="padding:10px 16px;font-weight:600;color:var(--text-muted);text-align:left;">Ngày đá</th>
                            <th style="padding:10px 16px;font-weight:600;color:var(--text-muted);text-align:left;">Tiền</th>
                            <th style="padding:10px 16px;font-weight:600;color:var(--text-muted);text-align:left;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $b):
                            $sc = ['pending'=>'pill-pending','confirmed'=>'pill-confirmed','completed'=>'pill-completed','cancelled'=>'pill-cancelled','no_show'=>'pill-no_show'];
                            $sl = ['pending'=>'Chờ','confirmed'=>'Xác nhận','completed'=>'Hoàn thành','cancelled'=>'Đã hủy','no_show'=>'No show'];
                        ?>
                        <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td style="padding:10px 16px;font-weight:700;color:var(--primary);"><?=htmlspecialchars($b['booking_code'])?></td>
                            <td style="padding:10px 16px;"><?=htmlspecialchars($b['customer_name'])?></td>
                            <td style="padding:10px 16px;color:var(--text-muted);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($b['field_name'])?></td>
                            <td style="padding:10px 16px;"><?=date('d/m/Y',strtotime($b['booking_date']))?></td>
                            <td style="padding:10px 16px;font-weight:600;"><?=number_format($b['total_price'],0,',','.')?></td>
                            <td style="padding:10px 16px;"><span class="status-pill <?=$sc[$b['status']]??'pill-pending'?>"><?=$sl[$b['status']]??'?'?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_bookings)): ?>
                        <tr><td colspan="6" style="padding:20px;text-align:center;color:var(--text-muted);">Chưa có đơn nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Fields -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3>Sân chờ duyệt
                    <?php if ($pending_fields > 0): ?>
                    <span class="alert-count"><?=$pending_fields?></span>
                    <?php endif; ?>
                </h3>
                <a href="approve_fields.php" style="font-size:13px;color:var(--primary);text-decoration:none;font-weight:600;">Duyệt →</a>
            </div>
            <div>
                <?php if (empty($pending_list)): ?>
                    <div style="padding:30px;text-align:center;color:var(--text-muted);">
                        <i data-lucide="check-circle-2" style="width:32px;height:32px;color:#10b981;margin-bottom:8px;"></i>
                        <p>Không có sân nào chờ duyệt!</p>
                    </div>
                <?php else: foreach ($pending_list as $f): ?>
                <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:flex-start;">
                    <div style="width:36px;height:36px;background:rgba(245,158,11,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="map-pin" style="width:18px;height:18px;color:#f59e0b;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($f['name'])?></div>
                        <div style="font-size:12px;color:var(--text-muted);"><?=htmlspecialchars($f['owner_name'])?> · <?=$f['district']?></div>
                        <div style="font-size:11px;margin-top:3px;color:#f59e0b;">Gửi <?=date('d/m/Y',strtotime($f['created_at']))?></div>
                    </div>
                    <a href="approve_fields.php" style="font-size:11px;background:rgba(245,158,11,0.1);color:#d97706;padding:4px 10px;border-radius:6px;text-decoration:none;font-weight:600;flex-shrink:0;">Duyệt</a>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Row 2: Charts + Today Activity -->
    <div class="dash-grid-2" style="margin-bottom:20px;">
        <!-- Charts -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3>Biểu đồ Doanh thu 6 tháng</h3>
                <a href="stats.php" style="font-size:12px;color:var(--primary);text-decoration:none;">Chi tiết →</a>
            </div>
            <div class="dash-card-body">
                <div style="height:220px;position:relative;margin-bottom:16px;">
                    <canvas id="adminRevenueChart"></canvas>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="height:180px;position:relative;">
                        <canvas id="adminStatusChart"></canvas>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;justify-content:center;">
                        <?php
                        $status_labels = ['completed'=>['Hoàn thành','#10b981'],'confirmed'=>['Xác nhận','#3b82f6'],'pending'=>['Chờ xử lý','#f59e0b'],'cancelled'=>['Đã hủy','#ef4444'],'no_show'=>['No show','#94a3b8']];
                        foreach ($status_labels as $key=>$sl):
                            $cnt = $status_dist[$key] ?? 0;
                        ?>
                        <div style="display:flex;align-items:center;gap:8px;font-size:12px;">
                            <div style="width:10px;height:10px;border-radius:50%;background:<?=$sl[1]?>;"></div>
                            <span style="flex:1;color:var(--text-muted);"><?=$sl[0]?></span>
                            <span style="font-weight:700;"><?=$cnt?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today Activity -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3>Lịch đặt hôm nay</h3>
                <span style="font-size:12px;color:var(--text-muted);"><?=date('d/m/Y')?></span>
            </div>
            <div class="dash-card-body" style="padding:0;">
                <?php if (empty($today_activity)): ?>
                    <div style="padding:30px;text-align:center;color:var(--text-muted);">
                        <i data-lucide="calendar" style="width:32px;height:32px;color:#cbd5e1;margin-bottom:8px;"></i>
                        <p>Chưa có đơn nào hôm nay</p>
                    </div>
                <?php else: foreach ($today_activity as $a):
                    $sc = ['pending'=>'pill-pending','confirmed'=>'pill-confirmed','completed'=>'pill-completed','cancelled'=>'pill-cancelled','no_show'=>'pill-no_show'];
                    $sl = ['pending'=>'Chờ','confirmed'=>'Xác nhận','completed'=>'Hoàn thành','cancelled'=>'Đã hủy','no_show'=>'No show'];
                ?>
                <div class="today-row" style="padding:12px 20px;">
                    <div class="time-range"><?=date('H:i',strtotime($a['start_time']))?> – <?=date('H:i',strtotime($a['end_time']))?></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($a['full_name'])?></div>
                        <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($a['field_name'])?></div>
                    </div>
                    <span class="status-pill <?=$sc[$a['status']]??'pill-pending'?>"><?=$sl[$a['status']]??'?'?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Row 3: Top Fields -->
    <div class="dash-card" style="margin-bottom:20px;">
        <div class="dash-card-header">
            <h3>🏆 Top 5 Sân Được Đặt Nhiều Nhất</h3>
        </div>
        <div class="dash-card-body">
            <?php if (empty($top_fields)): ?>
                <p style="color:var(--text-muted);">Chưa có dữ liệu.</p>
            <?php else:
                $max_b = max(array_column($top_fields, 'bookings_count')) ?: 1;
                foreach ($top_fields as $i => $f):
                    $rank = $i+1;
                    $pct = round($f['bookings_count']/$max_b*100);
                    $rc = $rank==1?'r1':($rank==2?'r2':($rank==3?'r3':'r-else'));
            ?>
            <div class="field-rank-row">
                <div class="rank-num <?=$rc?>">#<?=$rank?></div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:14px;font-weight:600;"><?=htmlspecialchars($f['name'])?></span>
                        <span style="font-size:12px;color:var(--text-muted);"><?=$f['bookings_count']?> đơn · <?=number_format($f['revenue'],0,',','.')?>đ</span>
                    </div>
                    <div style="background:#f1f5f9;border-radius:4px;height:8px;">
                        <div style="width:<?=$pct?>%;height:8px;border-radius:4px;background:linear-gradient(90deg,var(--primary),var(--primary-dark));transition:width 1s ease;"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue line chart
    new Chart(document.getElementById('adminRevenueChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_rev, 'month_label')); ?>,
            datasets: [{
                label: 'Doanh thu',
                data: <?php echo json_encode(array_column($monthly_rev, 'total')); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                fill: true, tension: 0.4, pointRadius: 5,
                pointBackgroundColor: '#3b82f6',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: ctx => new Intl.NumberFormat('vi-VN').format(ctx.raw) + 'đ' }}
            },
            scales: {
                y: { ticks: { callback: v => (v/1e6).toFixed(1)+'M' }, grid: { color: '#f8fafc' } }
            }
        }
    });

    // Status doughnut chart
    new Chart(document.getElementById('adminStatusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Hoàn thành','Xác nhận','Chờ','Đã hủy','No show'],
            datasets: [{
                data: [
                    <?=$status_dist['completed']??0?>,
                    <?=$status_dist['confirmed']??0?>,
                    <?=$status_dist['pending']??0?>,
                    <?=$status_dist['cancelled']??0?>,
                    <?=$status_dist['no_show']??0?>
                ],
                backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ef4444','#94a3b8'],
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' }
    });

    // ─── Live Polling mỗi 15 giây ─────────────────────────
    function pollLiveStats() {
        fetch('../api/notifications.php?action=admin_live')
            .then(r => r.json())
            .then(data => {
                if (data.pending_bookings !== undefined) {
                    document.getElementById('livePendingVal').textContent = data.pending_bookings;
                }
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('vi-VN');
            })
            .catch(() => {});
    }
    setInterval(pollLiveStats, 15000);
});
</script>

<?php include '../includes/dashboard_footer.php'; ?>
