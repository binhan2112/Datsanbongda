<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$owner_id = $_SESSION['user_id'];

$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval(date('Y'));
$selected_field = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;

try {
    // Danh sách sân của owner để đưa vào dropdown filter
    $fields_stmt = $pdo->prepare("SELECT id, name FROM fields WHERE owner_id = ?");
    $fields_stmt->execute([$owner_id]);
    $owner_fields = $fields_stmt->fetchAll();

    // Query điều kiện
    $where_sql = " WHERE f.owner_id = :owner_id AND YEAR(b.booking_date) = :year AND MONTH(b.booking_date) = :month ";
    $params = [
        'owner_id' => $owner_id,
        'year'     => $selected_year,
        'month'    => $selected_month
    ];

    if ($selected_field > 0) {
        $where_sql .= " AND b.field_id = :field_id ";
        $params['field_id'] = $selected_field;
    }

    // Thống kê tổng quan trong tháng chọn
    $stats_sql = "
        SELECT 
            COUNT(b.id) as total_bookings,
            SUM(CASE WHEN b.status IN ('confirmed','completed') THEN 1 ELSE 0 END) as successful_bookings,
            SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN b.status IN ('confirmed','completed') THEN b.total_price ELSE 0 END) as total_revenue,
            AVG(CASE WHEN b.status IN ('confirmed','completed') THEN b.total_price ELSE NULL END) as avg_booking_value
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        $where_sql
    ";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute($params);
    $rev_stats = $stats_stmt->fetch();

    $total_rev = $rev_stats['total_revenue'] ?? 0;
    $succ_bookings = $rev_stats['successful_bookings'] ?? 0;
    $canc_bookings = $rev_stats['cancelled_bookings'] ?? 0;
    $avg_val = $rev_stats['avg_booking_value'] ?? 0;

    // Lấy thống kê doanh thu theo từng ngày trong tháng
    $daily_sql = "
        SELECT DAY(b.booking_date) as day_num, SUM(b.total_price) as day_revenue
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        $where_sql AND b.status IN ('confirmed','completed')
        GROUP BY DAY(b.booking_date)
        ORDER BY day_num ASC
    ";
    $daily_stmt = $pdo->prepare($daily_sql);
    $daily_stmt->execute($params);
    $daily_rows = $daily_stmt->fetchAll();

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
    $daily_chart_data = array_fill(1, $days_in_month, 0);
    foreach ($daily_rows as $row) {
        $daily_chart_data[intval($row['day_num'])] = floatval($row['day_revenue']);
    }

    // Danh sách giao dịch chi tiết
    $transactions_sql = "
        SELECT b.*, f.name as field_name, u.full_name as customer_name, u.phone as customer_phone
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        JOIN users u ON b.user_id = u.id
        $where_sql
        ORDER BY b.booking_date DESC, b.start_time DESC
    ";
    $trans_stmt = $pdo->prepare($transactions_sql);
    $trans_stmt->execute($params);
    $transactions = $trans_stmt->fetchAll();

} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

$page_title = 'Báo Cáo Doanh Thu';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div>
    <!-- Bộ lọc tháng / năm / sân -->
    <div class="card" style="margin-bottom: 24px; padding: 20px;">
        <form method="GET" action="revenue.php" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
            <div>
                <label style="font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Tháng</label>
                <select name="month" class="form-control" style="width: 120px;" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m === $selected_month ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label style="font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Năm</label>
                <select name="year" class="form-control" style="width: 120px;" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $selected_year ? 'selected' : ''; ?>>Năm <?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label style="font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Lọc theo Sân bóng</label>
                <select name="field_id" class="form-control" style="width: 220px;" onchange="this.form.submit()">
                    <option value="0">--- Tất cả sân bóng ---</option>
                    <?php foreach ($owner_fields as $of): ?>
                        <option value="<?php echo $of['id']; ?>" <?php echo $of['id'] === $selected_field ? 'selected' : ''; ?>><?php echo htmlspecialchars($of['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-left: auto; align-self: flex-end;">
                <button type="button" onclick="window.print()" class="btn btn-secondary" style="height: 42px; display: inline-flex; align-items: center; gap: 8px;">
                    <i data-lucide="printer" style="width: 16px; height: 16px;"></i> In báo cáo
                </button>
            </div>
        </form>
    </div>

    <!-- Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 14px; border-radius: 50%;">
                    <i data-lucide="wallet" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--dark);"><?php echo number_format($total_rev, 0, ',', '.'); ?>đ</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Tổng doanh thu</div>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 14px; border-radius: 50%;">
                    <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--dark);"><?php echo $succ_bookings; ?> đơn</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Đã thành công</div>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 14px; border-radius: 50%;">
                    <i data-lucide="x-circle" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--dark);"><?php echo $canc_bookings; ?> đơn</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Số đơn bị hủy</div>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 14px; border-radius: 50%;">
                    <i data-lucide="trending-up" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--dark);"><?php echo number_format($avg_val, 0, ',', '.'); ?>đ</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Giá trị trung bình/đơn</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ doanh thu theo ngày -->
    <div class="card" style="margin-bottom: 30px; padding: 24px;">
        <h3 class="card-title" style="margin-bottom: 20px;">Biểu Đồ Doanh Thu Theo Ngày (Tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?>)</h3>
        <div style="height: 320px; position: relative;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Bảng chi tiết giao dịch -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Chi Tiết Giao Dịch Đặt Sân</h3>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 14px 20px;">Mã Đơn</th>
                        <th style="padding: 14px 20px;">Sân Bóng</th>
                        <th style="padding: 14px 20px;">Khách Hàng</th>
                        <th style="padding: 14px 20px;">Ngày Đặt</th>
                        <th style="padding: 14px 20px;">Khung Giờ</th>
                        <th style="padding: 14px 20px;">Thành Tiền</th>
                        <th style="padding: 14px 20px;">Trạng Thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">Không có giao dịch nào trong tháng này.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 14px 20px; font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($t['booking_code']); ?></td>
                                <td style="padding: 14px 20px; font-weight: 600;"><?php echo htmlspecialchars($t['field_name']); ?></td>
                                <td style="padding: 14px 20px;">
                                    <div><?php echo htmlspecialchars($t['customer_name']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($t['customer_phone']); ?></div>
                                </td>
                                <td style="padding: 14px 20px;"><?php echo date('d/m/Y', strtotime($t['booking_date'])); ?></td>
                                <td style="padding: 14px 20px;"><?php echo date('H:i', strtotime($t['start_time'])); ?> - <?php echo date('H:i', strtotime($t['end_time'])); ?></td>
                                <td style="padding: 14px 20px; font-weight: 700; color: #10b981;"><?php echo number_format($t['total_price'], 0, ',', '.'); ?>đ</td>
                                <td style="padding: 14px 20px;">
                                    <?php if ($t['status'] === 'completed'): ?>
                                        <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Hoàn thành</span>
                                    <?php elseif ($t['status'] === 'confirmed'): ?>
                                        <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã xác nhận</span>
                                    <?php elseif ($t['status'] === 'pending'): ?>
                                        <span style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chờ xử lý</span>
                                    <?php else: ?>
                                        <span style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã hủy</span>
                                    <?php endif; ?>
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
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const labels = <?php echo json_encode(array_map(function($d) { return "Ngày " . $d; }, array_keys($daily_chart_data))); ?>;
    const dataValues = <?php echo json_encode(array_values($daily_chart_data)); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: dataValues,
                backgroundColor: 'rgba(0, 191, 166, 0.75)',
                borderColor: '#00bfa6',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000).toLocaleString('vi-VN') + 'k';
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/dashboard_footer.php'; ?>
