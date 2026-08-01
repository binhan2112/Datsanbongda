<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
require_login('admin');

// Bộ lọc thời gian
$period = $_GET['period'] ?? 'month';
$year   = intval($_GET['year'] ?? date('Y'));
$month  = intval($_GET['month'] ?? date('n'));

// ─── Doanh thu theo ngày trong tháng ─────────────────────────────
$daily_rev = $pdo->prepare("
    SELECT DAY(booking_date) as day_num, SUM(total_price) as total, COUNT(*) as cnt
    FROM bookings
    WHERE YEAR(booking_date) = :y AND MONTH(booking_date) = :m
      AND status IN ('completed','confirmed')
    GROUP BY DAY(booking_date)
    ORDER BY day_num
");
$daily_rev->execute(['y' => $year, 'm' => $month]);
$daily_rows = $daily_rev->fetchAll();

// Tạo array đủ 31 ngày (pad 0 nếu không có data)
$daily_data  = array_fill(1, 31, 0);
$daily_count = array_fill(1, 31, 0);
foreach ($daily_rows as $r) {
    $daily_data[$r['day_num']]  = floatval($r['total']);
    $daily_count[$r['day_num']] = intval($r['cnt']);
}

// ─── Doanh thu 12 tháng trong năm ─────────────────────────────────
$monthly_rev = $pdo->prepare("
    SELECT MONTH(booking_date) as mon, SUM(total_price) as total, COUNT(*) as cnt
    FROM bookings
    WHERE YEAR(booking_date) = :y AND status IN ('completed','confirmed')
    GROUP BY MONTH(booking_date)
    ORDER BY mon
");
$monthly_rev->execute(['y' => $year]);
$monthly_rows = $monthly_rev->fetchAll();

$monthly_data  = array_fill(1, 12, 0);
$monthly_count = array_fill(1, 12, 0);
foreach ($monthly_rows as $r) {
    $monthly_data[$r['mon']]  = floatval($r['total']);
    $monthly_count[$r['mon']] = intval($r['cnt']);
}

// ─── Tổng quan kỳ này vs kỳ trước ────────────────────────────────
$this_month_rev  = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE YEAR(booking_date)=:y AND MONTH(booking_date)=:m AND status IN ('completed','confirmed')");
$this_month_rev->execute(['y'=>$year,'m'=>$month]);
$rev_this  = floatval($this_month_rev->fetchColumn());

$prev_m = $month == 1 ? 12 : $month-1;
$prev_y = $month == 1 ? $year-1 : $year;
$prev_month_rev = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE YEAR(booking_date)=:y AND MONTH(booking_date)=:m AND status IN ('completed','confirmed')");
$prev_month_rev->execute(['y'=>$prev_y,'m'=>$prev_m]);
$rev_prev  = floatval($prev_month_rev->fetchColumn());
$rev_change = $rev_prev > 0 ? round(($rev_this - $rev_prev) / $rev_prev * 100, 1) : 0;

$cnt_this = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE YEAR(booking_date)=:y AND MONTH(booking_date)=:m AND status NOT IN ('cancelled','no_show')");
$cnt_this->execute(['y'=>$year,'m'=>$month]);
$bookings_this = intval($cnt_this->fetchColumn());

$cnt_prev = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE YEAR(booking_date)=:y AND MONTH(booking_date)=:m AND status NOT IN ('cancelled','no_show')");
$cnt_prev->execute(['y'=>$prev_y,'m'=>$prev_m]);
$bookings_prev = intval($cnt_prev->fetchColumn());
$bookings_change = $bookings_prev > 0 ? round(($bookings_this - $bookings_prev) / $bookings_prev * 100, 1) : 0;

// ─── Top 5 sân doanh thu cao nhất tháng ──────────────────────────
$top_fields_rev = $pdo->prepare("
    SELECT f.name, SUM(b.total_price) as revenue, COUNT(b.id) as bookings
    FROM bookings b JOIN fields f ON b.field_id = f.id
    WHERE YEAR(b.booking_date)=:y AND MONTH(b.booking_date)=:m
      AND b.status IN ('completed','confirmed')
    GROUP BY f.id ORDER BY revenue DESC LIMIT 5
");
$top_fields_rev->execute(['y'=>$year,'m'=>$month]);
$top_fields = $top_fields_rev->fetchAll();

// ─── Phân bổ thanh toán ──────────────────────────────────────────
$pay_dist = $pdo->query("SELECT payment_method, COUNT(*) as cnt FROM bookings GROUP BY payment_method")->fetchAll(PDO::FETCH_KEY_PAIR);

// ─── Top khách hàng ───────────────────────────────────────────────
$top_customers = $pdo->query("
    SELECT u.full_name, u.phone, COUNT(b.id) as bookings, SUM(b.total_price) as spent
    FROM bookings b JOIN users u ON b.user_id = u.id
    WHERE b.status IN ('completed','confirmed')
    GROUP BY b.user_id ORDER BY spent DESC LIMIT 5
")->fetchAll();

// ─── Xuất CSV ────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="baocao_'.$year.'_'.$month.'.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    echo "Ngày,Số đơn,Doanh thu (đ)\n";
    for ($d = 1; $d <= 31; $d++) {
        if ($daily_data[$d] > 0 || $daily_count[$d] > 0) {
            echo "$d/$month/$year,{$daily_count[$d]},{$daily_data[$d]}\n";
        }
    }
    exit;
}

$page_title = 'Thống kê Chi tiết';
$base_url = '../';
$active_menu = 'stats';
include '../includes/dashboard_header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.stats-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px; }
.kpi-card { background: white; border-radius: 16px; padding: 22px 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid var(--border); position: relative; overflow: hidden; }
.kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--kpi-color, var(--primary)); }
.kpi-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.kpi-value { font-size: 26px; font-weight: 800; color: var(--dark); line-height: 1; }
.kpi-change { margin-top: 8px; font-size: 12px; font-weight: 600; }
.kpi-change.up { color: #10b981; }
.kpi-change.down { color: #ef4444; }
.kpi-change.flat { color: var(--text-muted); }

.filter-bar { background: white; border-radius: 12px; padding: 16px 20px; border: 1px solid var(--border); display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 24px; }
.filter-bar select, .filter-bar input { padding: 8px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; }
.btn-export { background: linear-gradient(135deg,#10b981,#059669); color: white; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
.btn-export:hover { opacity: 0.9; }

.chart-grid { display: grid; grid-template-columns: 3fr 2fr; gap: 20px; margin-bottom: 24px; }
.chart-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid var(--border); }
.chart-card h3 { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 4px; }
.chart-card .chart-sub { font-size: 12px; color: var(--text-muted); margin-bottom: 20px; }

.table-section { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid var(--border); margin-bottom: 24px; }
.table-section h3 { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 20px; }
.ranking-row { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.ranking-row:last-child { border-bottom: none; }
.rank-badge { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0; }
.rank-1 { background: #fef3c7; color: #d97706; }
.rank-2 { background: #f1f5f9; color: #64748b; }
.rank-3 { background: #fff7ed; color: #ea580c; }
.rank-other { background: #f8fafc; color: #94a3b8; }
.bar-bg { flex: 1; background: #f1f5f9; border-radius: 4px; height: 8px; }
.bar-fill { height: 8px; border-radius: 4px; background: linear-gradient(90deg, var(--primary), #009b86); }
</style>

<div>
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;">
            <i data-lucide="filter" style="width:18px;height:18px;color:var(--text-muted);"></i>
            <label style="font-size:13px;font-weight:600;">Năm:</label>
            <select name="year" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
                    <option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option>
                <?php endfor; ?>
            </select>
            <label style="font-size:13px;font-weight:600;">Tháng:</label>
            <select name="month" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?=$m?>" <?=$m==$month?'selected':''?>>Tháng <?=$m?></option>
                <?php endfor; ?>
            </select>
            <div style="margin-left:auto; display:flex; gap:10px;">
                <button type="button" onclick="exportExcel()" class="btn-export" style="background:#10b981; color:white; border:none; cursor:pointer;">
                    <i data-lucide="file-spreadsheet" style="width:16px;height:16px;"></i> Xuất Excel
                </button>
                <button type="button" onclick="exportPDF()" class="btn-export" style="background:#ef4444; color:white; border:none; cursor:pointer;">
                    <i data-lucide="file-text" style="width:16px;height:16px;"></i> Xuất PDF
                </button>
            </div>
        </form>
    </div>

    <!-- Container for PDF export -->
    <div id="pdf-content" style="background: var(--bg-main); padding: 15px;">

    <!-- KPI Cards -->
    <div class="stats-kpi-grid">
        <div class="kpi-card" style="--kpi-color:#10b981;">
            <div class="kpi-label">Doanh thu tháng <?=$month?>/<?=$year?></div>
            <div class="kpi-value"><?=number_format($rev_this,0,',','.')?><span style="font-size:14px;font-weight:500;">đ</span></div>
            <div class="kpi-change <?=$rev_change>0?'up':($rev_change<0?'down':'flat')?>">
                <?=$rev_change>0?'▲':'▼'?> <?=abs($rev_change)?>% so với tháng trước
            </div>
        </div>
        <div class="kpi-card" style="--kpi-color:#3b82f6;">
            <div class="kpi-label">Số đơn tháng <?=$month?>/<?=$year?></div>
            <div class="kpi-value"><?=$bookings_this?><span style="font-size:14px;font-weight:500;"> đơn</span></div>
            <div class="kpi-change <?=$bookings_change>0?'up':($bookings_change<0?'down':'flat')?>">
                <?=$bookings_change>0?'▲':'▼'?> <?=abs($bookings_change)?>% so với tháng trước
            </div>
        </div>
        <div class="kpi-card" style="--kpi-color:#8b5cf6;">
            <div class="kpi-label">Trung bình / đơn</div>
            <div class="kpi-value"><?=$bookings_this>0?number_format($rev_this/$bookings_this,0,',','.'):0?><span style="font-size:14px;">đ</span></div>
            <div class="kpi-change flat">Tháng <?=$month?>/<?=$year?></div>
        </div>
        <div class="kpi-card" style="--kpi-color:#f59e0b;">
            <div class="kpi-label">Doanh thu cả năm <?=$year?></div>
            <div class="kpi-value"><?=number_format(array_sum($monthly_data),0,',','.')?><span style="font-size:14px;">đ</span></div>
            <div class="kpi-change flat">Tổng <?=array_sum($monthly_count)?> đơn</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="chart-grid">
        <!-- Daily Revenue Bar Chart -->
        <div class="chart-card">
            <h3>Doanh thu theo ngày — Tháng <?=$month?>/<?=$year?></h3>
            <p class="chart-sub">Biểu đồ cột doanh thu từng ngày trong tháng</p>
            <div style="height: 260px; position: relative;">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- Monthly Trend for the year -->
        <div class="chart-card">
            <h3>Xu hướng cả năm <?=$year?></h3>
            <p class="chart-sub">Doanh thu từng tháng</p>
            <div style="height: 260px; position: relative;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Top Fields + Top Customers + Payment Distribution -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Top 5 Sân -->
        <div class="table-section">
            <h3>🏆 Top Sân Doanh Thu Tháng <?=$month?></h3>
            <?php if (empty($top_fields)): ?>
                <p style="color:var(--text-muted);font-size:14px;">Chưa có dữ liệu.</p>
            <?php else:
                $max_rev = max(array_column($top_fields,'revenue'));
                foreach ($top_fields as $i => $f):
                    $rank = $i+1;
                    $pct = $max_rev > 0 ? round($f['revenue']/$max_rev*100) : 0;
                    $rc = $rank==1?'rank-1':($rank==2?'rank-2':($rank==3?'rank-3':'rank-other'));
            ?>
            <div class="ranking-row">
                <div class="rank-badge <?=$rc?>">#<?=$rank?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($f['name'])?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?=$f['bookings']?> đơn · <?=number_format($f['revenue'],0,',','.')?>đ</div>
                    <div class="bar-bg" style="margin-top:6px;"><div class="bar-fill" style="width:<?=$pct?>%;"></div></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Top 5 Khách hàng -->
        <div class="table-section">
            <h3>👤 Top Khách Hàng VIP</h3>
            <?php if (empty($top_customers)): ?>
                <p style="color:var(--text-muted);font-size:14px;">Chưa có dữ liệu.</p>
            <?php else:
                $max_spent = max(array_column($top_customers,'spent'));
                foreach ($top_customers as $i => $c):
                    $rank = $i+1;
                    $pct = $max_spent > 0 ? round($c['spent']/$max_spent*100) : 0;
                    $rc = $rank==1?'rank-1':($rank==2?'rank-2':($rank==3?'rank-3':'rank-other'));
            ?>
            <div class="ranking-row">
                <div class="rank-badge <?=$rc?>">#<?=$rank?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($c['full_name'])?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?=$c['bookings']?> đơn · <?=number_format($c['spent'],0,',','.')?>đ</div>
                    <div class="bar-bg" style="margin-top:6px;"><div class="bar-fill" style="width:<?=$pct?>%;background:linear-gradient(90deg,#8b5cf6,#6d28d9);"></div></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Phân bổ thanh toán -->
        <div class="table-section">
            <h3>💳 Phương Thức Thanh Toán</h3>
            <div style="height:200px;position:relative;margin-bottom:12px;">
                <canvas id="payChart"></canvas>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <?php
                $pay_labels = ['cash'=>'Tiền mặt','momo'=>'MoMo','vnpay'=>'VNPay','bank'=>'Ngân hàng'];
                $pay_colors = ['cash'=>'#10b981','momo'=>'#ec4899','vnpay'=>'#3b82f6','bank'=>'#f59e0b'];
                $total_pay = array_sum($pay_dist);
                foreach ($pay_labels as $key => $label):
                    $cnt = $pay_dist[$key] ?? 0;
                    $pct = $total_pay > 0 ? round($cnt/$total_pay*100) : 0;
                ?>
                <div style="display:flex;align-items:center;gap:8px;font-size:12px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:<?=$pay_colors[$key]?>;flex-shrink:0;"></div>
                    <span style="flex:1;color:var(--text-muted);"><?=$label?></span>
                    <span style="font-weight:600;"><?=$cnt?> (<?=$pct?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Chart
    const days = Array.from({length:31},(_,i)=>i+1);
    const dailyRevData = <?php echo json_encode(array_values($daily_data)); ?>;
    const dailyCntData = <?php echo json_encode(array_values($daily_count)); ?>;

    new Chart(document.getElementById('dailyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: days,
            datasets: [{
                label: 'Doanh thu',
                data: dailyRevData,
                backgroundColor: dailyRevData.map(v => v > 0 ? 'rgba(0,191,166,0.7)' : 'rgba(0,0,0,0.04)'),
                borderColor: dailyRevData.map(v => v > 0 ? '#00bfa6' : 'transparent'),
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false },
                tooltip: { callbacks: {
                    label: ctx => new Intl.NumberFormat('vi-VN').format(ctx.raw) + 'đ'
                }}
            },
            scales: {
                y: { ticks: { callback: v => (v/1e6).toFixed(1) + 'M' }, grid: { color: '#f1f5f9' } }
            }
        }
    });

    // Monthly Trend Chart
    const months = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'];
    const monthlyData = <?php echo json_encode(array_values($monthly_data)); ?>;
    new Chart(document.getElementById('monthlyChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Doanh thu',
                data: monthlyData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                fill: true, tension: 0.4, pointRadius: 5,
                pointBackgroundColor: '#3b82f6',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false },
                tooltip: { callbacks: {
                    label: ctx => new Intl.NumberFormat('vi-VN').format(ctx.raw) + 'đ'
                }}
            },
            scales: {
                y: { ticks: { callback: v => (v/1e6).toFixed(1) + 'M' }, grid: { color: '#f1f5f9' } }
            }
        }
    });

    // Payment Chart
    new Chart(document.getElementById('payChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Tiền mặt','MoMo','VNPay','Ngân hàng'],
            datasets: [{
                data: [
                    <?=$pay_dist['cash']??0?>,
                    <?=$pay_dist['momo']??0?>,
                    <?=$pay_dist['vnpay']??0?>,
                    <?=$pay_dist['bank']??0?>
                ],
                backgroundColor: ['#10b981','#ec4899','#3b82f6','#f59e0b'],
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '65%'
        }
    });
    lucide.createIcons();
    });

    // ─── Export Excel (SheetJS) ──────────────────────────────────
    function exportExcel() {
        const wb = XLSX.utils.book_new();
        
        // Data Doanh thu theo ngày
        let dailyData = [["Ngày", "Số đơn", "Doanh thu (đ)"]];
        <?php for ($d = 1; $d <= 31; $d++): ?>
            <?php if ($daily_data[$d] > 0 || $daily_count[$d] > 0): ?>
                dailyData.push(["<?php echo "$d/$month/$year"; ?>", <?php echo $daily_count[$d]; ?>, <?php echo $daily_data[$d]; ?>]);
            <?php endif; ?>
        <?php endfor; ?>
        const wsDaily = XLSX.utils.aoa_to_sheet(dailyData);
        XLSX.utils.book_append_sheet(wb, wsDaily, "Doanh Thu Hàng Ngày");

        // Data Sân nổi bật
        let fieldData = [["Tên Sân", "Số lượt đặt", "Doanh thu (đ)"]];
        <?php foreach ($top_fields as $f): ?>
            fieldData.push(["<?php echo htmlspecialchars($f['name']); ?>", <?php echo $f['bookings']; ?>, <?php echo floatval($f['revenue']); ?>]);
        <?php endforeach; ?>
        const wsFields = XLSX.utils.aoa_to_sheet(fieldData);
        XLSX.utils.book_append_sheet(wb, wsFields, "Sân Nổi Bật");

        XLSX.writeFile(wb, "baocao_doanhthu_<?=$month?>_<?=$year?>.xlsx");
    }

    // ─── Export PDF (html2pdf) ───────────────────────────────────
    function exportPDF() {
        const element = document.getElementById('pdf-content');
        
        // Hide elements not needed in PDF
        document.querySelector('.filter-bar').style.display = 'none';
        
        const opt = {
            margin:       0.5,
            filename:     'baocao_doanhthu_<?=$month?>_<?=$year?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            // Restore elements
            document.querySelector('.filter-bar').style.display = 'flex';
        });
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</div> <!-- Close pdf-content -->
<?php include '../includes/dashboard_footer.php'; ?>
