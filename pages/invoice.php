<?php
// ═══════════════════════════════════════════════════════
// HÓA ĐƠN ĐẶT SÂN
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) {
    header("Location: my_bookings.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, f.name as field_name, f.address as field_address, f.phone as field_phone,
               u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :id AND (b.user_id = :user_id OR :role IN ('owner','admin'))
    ");
    $stmt->execute([
        'id' => $booking_id,
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role']
    ]);
    $b = $stmt->fetch();

    if (!$b) {
        die("Không tìm thấy đơn đặt sân phù hợp hoặc bạn không có quyền xem.");
    }
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa Đơn #<?php echo htmlspecialchars($b['booking_code']); ?> — CanThoSport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 40px 20px;
        }
        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #0c2340;
            text-decoration: none;
        }
        .logo span { color: #ff003c; }
        .invoice-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-table th, .invoice-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .invoice-table th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: #64748b;
        }
        .total-row {
            font-size: 18px;
            font-weight: 800;
            color: #ff003c;
        }
        .print-btn {
            background: #0c2340;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }
        .print-btn:hover { background: #1e3a8a; }

        @media print {
            body { background: white; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div style="max-width: 800px; margin: 0 auto 20px; text-align: right;" class="no-print">
        <button onclick="window.print()" class="print-btn">
            <i data-lucide="printer"></i> In Hóa Đơn / Tải PDF
        </button>
        <a href="my_bookings.php" style="margin-left: 12px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600;">Quay lại</a>
    </div>

    <div class="invoice-card">
        <!-- Header -->
        <div class="invoice-header">
            <div>
                <div class="logo"><i data-lucide="trophy" style="width: 24px; height: 24px;"></i> CanTho<span>Sport</span></div>
                <div style="font-size: 13px; color: #64748b; margin-top: 6px;">Hệ thống đặt sân bóng đá mini uy tín hàng đầu Cần Thơ</div>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0; font-size: 20px; color: #0c2340;">HÓA ĐƠN ĐẶT SÂN</h2>
                <div style="font-size: 14px; font-weight: 700; color: #ff003c; margin-top: 4px;">#<?php echo htmlspecialchars($b['booking_code']); ?></div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Ngày lập: <?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="invoice-details-grid">
            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #64748b; text-transform: uppercase;">Thông tin khách hàng</h4>
                <div style="font-weight: 700; font-size: 16px;"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                <div style="font-size: 14px; color: #475569; margin-top: 4px;">SĐT: <?php echo htmlspecialchars($b['customer_phone']); ?></div>
                <div style="font-size: 14px; color: #475569; margin-top: 2px;">Email: <?php echo htmlspecialchars($b['customer_email']); ?></div>
            </div>

            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #64748b; text-transform: uppercase;">Địa điểm sân bóng</h4>
                <div style="font-weight: 700; font-size: 16px; color: #0c2340;"><?php echo htmlspecialchars($b['field_name']); ?></div>
                <div style="font-size: 14px; color: #475569; margin-top: 4px;"><?php echo htmlspecialchars($b['field_address']); ?></div>
                <div style="font-size: 14px; color: #475569; margin-top: 2px;">Hotline sân: <?php echo htmlspecialchars($b['field_phone']); ?></div>
            </div>
        </div>

        <!-- Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Dịch vụ</th>
                    <th>Ngày sử dụng</th>
                    <th>Khung giờ</th>
                    <th>Thời lượng</th>
                    <th style="text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;">Thuê sân bóng đá mini</td>
                    <td><?php echo date('d/m/Y', strtotime($b['booking_date'])); ?></td>
                    <td><?php echo date('H:i', strtotime($b['start_time'])); ?> - <?php echo date('H:i', strtotime($b['end_time'])); ?></td>
                    <td><?php echo $b['duration']; ?> giờ</td>
                    <td style="text-align: right; font-weight: 600;"><?php echo number_format($b['total_price'] + $b['discount_amount'], 0, ',', '.'); ?>đ</td>
                </tr>

                <?php if ($b['discount_amount'] > 0): ?>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: 600; color: #10b981;">Giảm giá (Đã dùng <?php echo $b['points_used']; ?> điểm tích lũy):</td>
                        <td style="text-align: right; font-weight: 600; color: #10b981;">-<?php echo number_format($b['discount_amount'], 0, ',', '.'); ?>đ</td>
                    </tr>
                <?php endif; ?>

                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">TỔNG CỘNG THANH TOÁN:</td>
                    <td style="text-align: right;"><?php echo number_format($b['total_price'], 0, ',', '.'); ?>đ</td>
                </tr>
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; background: #f8fafc; padding: 16px; border-radius: 8px;">
            <div>Phương thức: <strong><?php echo strtoupper($b['payment_method']); ?></strong></div>
            <div>Trạng thái thanh toán: <strong style="color: <?php echo $b['payment_status'] === 'paid' ? '#10b981' : '#f59e0b'; ?>;"><?php echo $b['payment_status'] === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán (Thanh toán tại sân)'; ?></strong></div>
        </div>

        <div style="margin-top: 40px; text-align: center; font-size: 13px; color: #94a3b8;">
            Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của <strong>CanThoSport</strong>! Chúc bạn có một trận đấu tuyệt vời.
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
