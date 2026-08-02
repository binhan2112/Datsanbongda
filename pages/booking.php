<?php
// ═══════════════════════════════════════════════════════
// TRANG ĐẶT SÂN BÓNG — CINEMA-STYLE TIME SLOT PICKER
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();

require_once '../controllers/BookingController.php';
$base_url = '../';
$current_page = 'bookings';
$page_title = 'Đặt Sân: ' . $field['name'];
include '../includes/header.php';
?>

<style>
    .booking-cinema { max-width: 800px; margin: 30px auto; padding: 0 15px; }
    .booking-form-card { background: var(--bg-card); border-radius: 20px; padding: 30px; box-shadow: 0 8px 40px rgba(0,0,0,0.06); border: 1px solid var(--border); }
    .booking-form-card h1 { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
    .booking-form-card .subtitle { color: var(--text-muted); margin-bottom: 24px; font-size: 14px; }
    .booking-form-card .subtitle b { color: var(--primary); }

    .slot-legend { display: flex; gap: 20px; margin-bottom: 16px; flex-wrap: wrap; }
    .slot-legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .slot-legend-dot { width: 14px; height: 14px; border-radius: 4px; }

    .slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px; margin-bottom: 24px; }
    .slot-cell {
        position: relative;
        border: 2px solid var(--border);
        border-radius: 10px;
        padding: 10px 6px;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
        user-select: none;
    }
    .slot-cell .slot-time { font-size: 14px; font-weight: 700; line-height: 1.2; }
    .slot-cell .slot-price { font-size: 11px; font-weight: 500; margin-top: 2px; opacity: 0.8; }
    .slot-cell .slot-label { font-size: 10px; font-weight: 600; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Available — green tones */
    .slot-available {
        background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
        border-color: rgba(16, 185, 129, 0.35);
        color: #047857;
    }
    .slot-available:hover {
        border-color: #10b981;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
    }

    /* Selected — uses site primary (red) */
    .slot-available.selected {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        border-color: var(--primary);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px var(--primary-glow);
    }
    .slot-available.selected .slot-price,
    .slot-available.selected .slot-label { color: rgba(255,255,255,0.85); }

    /* Booked — red tones */
    .slot-booked {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border-color: rgba(239, 68, 68, 0.25);
        color: #b91c1c;
        cursor: not-allowed;
        opacity: 0.8;
    }
    .slot-booked::after {
        content: '';
        position: absolute; top: 50%; left: 10%; right: 10%;
        height: 2px; background: #dc2626; opacity: 0.4;
        transform: rotate(-12deg);
    }

    /* Maintenance — gray tones */
    .slot-maintenance {
        background: linear-gradient(135deg, #f5f5f4 0%, #e7e5e4 100%);
        border-color: rgba(120, 113, 108, 0.25);
        color: #78716c;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .slot-peak .slot-time::after { content: '🔥'; font-size: 10px; margin-left: 3px; }

    /* Screen bar */
    .screen-bar {
        background: linear-gradient(to right, transparent 0%, var(--primary) 20%, var(--primary) 80%, transparent 100%);
        height: 4px; border-radius: 50%; margin-bottom: 8px; opacity: 0.25;
    }
    .screen-label {
        text-align: center; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 3px;
        color: var(--text-muted); margin-bottom: 20px;
    }

    /* Summary */
    .booking-summary {
        background: linear-gradient(135deg, #0c2340 0%, #1a365d 100%);
        border-radius: 14px; padding: 20px; color: white; margin-bottom: 20px;
    }
    .booking-summary .summary-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 6px 0; font-size: 14px;
    }
    .booking-summary .summary-row.total {
        border-top: 1px solid rgba(255,255,255,0.15);
        margin-top: 8px; padding-top: 12px; font-size: 18px; font-weight: 800;
    }
    .booking-summary .summary-row .label { color: #94a3b8; }
    .booking-summary .summary-row .value { font-weight: 600; }
    .booking-summary .summary-row.total .value { color: #10b981; font-size: 22px; }

    @media (max-width: 600px) {
        .slot-grid { grid-template-columns: repeat(3, 1fr); }
        .booking-form-card { padding: 20px; }
    }

    /* Payment Method Cards */
    .payment-methods { display: flex; gap: 12px; flex-wrap: wrap; }
    .pm-card {
        flex: 1; min-width: 140px;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--bg-card);
    }
    .pm-card:hover { border-color: var(--primary); background: var(--bg-card-hover); }
    .pm-card input[type="radio"] { display: none; }
    .pm-card input[type="radio"]:checked + .pm-card-content { font-weight: 700; color: var(--primary); }
    .pm-card.active { border-color: var(--primary); border-width: 2px; padding: 11px; background: var(--primary-subtle); box-shadow: 0 4px 12px var(--primary-glow); }
</style>

<div class="booking-cinema">
    <div class="booking-form-card">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
            <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--primary-subtle); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                <i data-lucide="calendar-check-2" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <h1 style="font-size: 24px; font-weight: 800; margin: 0; line-height: 1.2;"><?php echo __trans('booking_title'); ?></h1>
                <p class="subtitle" style="margin: 4px 0 0 0; font-size: 14px; color: var(--text-muted);">
                    <?php echo __trans('field_lbl'); ?> <b style="color: var(--primary);"><?php echo htmlspecialchars($field['name']); ?></b> &bull; <?php echo date('H:i', strtotime($field['open_time'])); ?> – <?php echo date('H:i', strtotime($field['close_time'])); ?>
                </p>
            </div>
        </div>
        <hr style="border: none; border-top: 1px dashed var(--border); margin: 20px 0;">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <i data-lucide="alert-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Date picker that auto-reloads page to refresh slot status -->
        <form action="booking.php?field_id=<?php echo $field['id']; ?>" method="POST" id="dateForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; background: var(--bg-main); padding: 20px; border-radius: 16px; border: 1px solid var(--border);">
                <div class="form-group" style="margin: 0;">
                    <label for="booking_date" style="font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; font-size: 14px; color: var(--text-secondary);">
                        <i data-lucide="calendar" style="width: 16px; height: 16px; color: var(--primary);"></i> <?php echo __trans('select_date'); ?>
                    </label>
                    <input type="date" name="booking_date" id="booking_date" class="form-control" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo htmlspecialchars($selected_date); ?>"
                           onchange="this.form.submit()"
                           style="height: 48px; border-radius: 12px; border-color: var(--border); box-shadow: var(--shadow-sm); cursor: pointer; padding: 0 16px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; font-size: 14px; color: var(--text-secondary);">
                        <i data-lucide="calendar-heart" style="width: 16px; height: 16px; color: var(--primary);"></i> <?php echo __trans('selected_date'); ?>
                    </label>
                    <div style="height: 48px; display: flex; align-items: center; justify-content: center; background: var(--primary-subtle); border: 1px solid rgba(255,0,60,0.15); border-radius: 12px; font-weight: 700; color: var(--primary); font-size: 15px;">
                        <?php 
                        $dayNames = [__trans('sun'), __trans('mon'), __trans('tue'), __trans('wed'), __trans('thu'), __trans('fri'), __trans('sat')];
                        $dayOfWeek = $dayNames[date('w', strtotime($selected_date))];
                        echo $dayOfWeek . ', ' . date('d/m/Y', strtotime($selected_date)); 
                        ?>
                    </div>
                </div>
            </div>
        </form>

        <!-- Main booking form -->
        <form action="booking.php?field_id=<?php echo $field['id']; ?>" method="POST" id="bookingForm">
            <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($selected_date); ?>">
            
            <div style="margin-bottom: 24px;">
                <label for="duration" style="font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; font-size: 14px; color: var(--text-secondary);">
                    <i data-lucide="clock-4" style="width: 16px; height: 16px; color: var(--primary);"></i> <?php echo __trans('rent_duration'); ?>
                </label>
                <div style="position: relative; max-width: 200px;">
                    <select name="duration" id="duration" class="form-control" onchange="recalcSlots()" style="height: 48px; border-radius: 12px; border-color: var(--border); box-shadow: var(--shadow-sm); padding-left: 16px; appearance: none; -webkit-appearance: none; cursor: pointer;">
                        <?php foreach ([1, 1.5, 2, 2.5, 3] as $d): ?>
                            <option value="<?php echo $d; ?>" <?php if (isset($_POST['duration']) && $_POST['duration'] == $d) echo 'selected'; ?>><?php echo $d; ?> <?php echo __trans('hours'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i data-lucide="chevron-down" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted); pointer-events: none;"></i>
                </div>
            </div>

            <!-- Cinema screen bar -->
            <div class="screen-bar"></div>
            <div class="screen-label"><?php echo __trans('select_start_time'); ?></div>
            <div style="text-align:center; font-size: 12px; color: var(--text-muted); margin-bottom: 12px; margin-top: -14px;">
                ⏱ <?php echo __trans('slot_30min_note') ?? 'Mỗi ô = 30 phút · Chọn giờ bắt đầu'; ?>
            </div>

            <!-- Legend -->
            <div class="slot-legend">
                <div class="slot-legend-item"><div class="slot-legend-dot" style="background: linear-gradient(135deg, #e6fff9, #f0fdf4); border: 1.5px solid rgba(0,191,166,0.4);"></div> <?php echo __trans('slot_empty'); ?></div>
                <div class="slot-legend-item"><div class="slot-legend-dot" style="background: linear-gradient(135deg, var(--primary), #009b86);"></div> <?php echo __trans('slot_selecting'); ?></div>
                <div class="slot-legend-item"><div class="slot-legend-dot" style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1.5px solid rgba(239,68,68,0.3);"></div> <?php echo __trans('slot_booked'); ?></div>
                <div class="slot-legend-item"><div class="slot-legend-dot" style="background: linear-gradient(135deg, #f5f5f4, #e7e5e4); border: 1.5px solid rgba(120,113,108,0.3);"></div> <?php echo __trans('slot_maintenance'); ?></div>
                <div class="slot-legend-item"><span>🔥</span> <?php echo __trans('slot_peak'); ?></div>
            </div>

            <!-- Slot Grid -->
            <?php if ($ui_is_closed): ?>
                <div style="text-align: center; padding: 40px 20px; background: #fef2f2; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fca5a5;">
                    <i data-lucide="door-closed" style="width: 48px; height: 48px; color: #ef4444; margin-bottom: 15px;"></i>
                    <h3 style="color: #991b1b; font-size: 18px; margin-bottom: 8px;">Sân đóng cửa</h3>
                    <p style="color: #b91c1c;">Sân không hoạt động vào ngày này. Vui lòng chọn ngày khác.</p>
                </div>
            <?php else: ?>
            <div class="slot-grid" id="slotGrid">
                <?php for ($h = $open_hour; $h < $close_hour; $h += 0.5): 
                    $hour_int = floor($h);
                    $min_str = ($h - $hour_int > 0) ? '30' : '00';
                    $time_str = sprintf('%02d:%s', $hour_int, $min_str);
                    $key = strval($h);
                    
                    $is_booked = isset($booked_slots[$key]);
                    $is_unavail = isset($unavail_slots[$key]);
                    
                    // Khoá các giờ đã qua nếu chọn ngày hôm nay
                    $current_date = date('Y-m-d');
                    if ($selected_date === $current_date) {
                        $current_hour = floatval(date('H')) + (floatval(date('i')) / 60);
                        if ($h <= $current_hour) {
                            $is_unavail = true;
                        }
                    }

                    $is_peak = ($hour_int >= 17 && $hour_int < 21);
                    $hour_price = $is_peak && !empty($field['price_peak']) ? $field['price_peak'] : $field['price_per_hour'];
                    $block_price = $hour_price / 2;
                    if ($field['discount_percent'] > 0) $block_price = $block_price * (1 - $field['discount_percent'] / 100);

                    if ($is_booked) {
                        $slot_class = 'slot-booked';
                        $slot_label = __trans('slot_booked');
                    } elseif ($is_unavail) {
                        $slot_class = 'slot-maintenance';
                        $slot_label = __trans('slot_maintenance');
                    } else {
                        $slot_class = 'slot-available';
                        $slot_label = __trans('slot_empty');
                    }
                ?>
                    <div class="slot-cell <?php echo $slot_class; ?> <?php echo $is_peak ? 'slot-peak' : ''; ?>"
                         data-val="<?php echo $h; ?>"
                         data-time="<?php echo $time_str; ?>"
                         data-price="<?php echo $block_price; ?>"
                         data-status="<?php echo $is_booked ? 'booked' : ($is_unavail ? 'maintenance' : 'available'); ?>"
                         <?php if (!$is_booked && !$is_unavail): ?>onclick="selectSlot(this)"<?php endif; ?>>
                        <div class="slot-time"><?php echo $time_str; ?></div>
                        <div class="slot-price"><?php echo number_format($block_price, 0, ',', '.'); ?>đ</div>
                        <div class="slot-label"><?php echo $slot_label; ?></div>
                    </div>
                <?php endfor; ?>
            </div>

            <input type="hidden" name="start_time" id="selectedStartTime" value="">

            <!-- Booking Summary -->
            <div class="booking-summary" id="bookingSummary" style="display: none;">
                <div class="summary-row">
                    <span class="label"><?php echo __trans('summary_time_range'); ?></span>
                    <span class="value" id="summaryTimeRange">—</span>
                </div>
                <div class="summary-row">
                    <span class="label"><?php echo __trans('summary_duration'); ?></span>
                    <span class="value" id="summaryDuration">—</span>
                </div>
                <div class="summary-row">
                    <span class="label"><?php echo __trans('summary_price_type'); ?></span>
                    <span class="value" id="summaryPriceType">—</span>
                </div>
                <div class="summary-row total">
                    <span class="label"><?php echo __trans('summary_total'); ?></span>
                    <span class="value" id="summaryTotal">0đ</span>
                </div>
                <div class="summary-row" id="depositRow" style="display: none; border-top: 1px dashed var(--border); padding-top: 10px; margin-top: 5px;">
                    <span class="label">Tiền cọc phải thanh toán (<?php echo $field['deposit_percent'] ?? 0; ?>%)</span>
                    <span class="value" id="summaryDeposit" style="color: var(--primary); font-weight: 700;">0đ</span>
                </div>
                <div class="summary-row" id="remainingRow" style="display: none;">
                    <span class="label">Còn lại thanh toán tại sân</span>
                    <span class="value" id="summaryRemaining">0đ</span>
                </div>
            </div>

            <!-- Extra Options -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 16px;">
                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; font-size: 14px; color: var(--text-secondary);">
                        <i data-lucide="wallet" style="width: 16px; height: 16px; color: var(--primary);"></i> <?php echo __trans('payment_method'); ?>
                    </label>
                    <div class="payment-methods">
                        <label class="pm-card active" id="pm-vnpay" onclick="selectPaymentMethod('vnpay')">
                            <input type="radio" name="payment_method" value="vnpay" checked>
                            <img src="https://sandbox.vnpayment.vn/apis/assets/images/bank/vnpay_logo.png" alt="VNPay" style="height: 24px; width: auto; object-fit: contain;">
                            <div class="pm-card-content" style="font-size: 14px;">Thanh toán VNPAY</div>
                        </label>
                        <label class="pm-card" id="pm-bank" onclick="selectPaymentMethod('bank')">
                            <input type="radio" name="payment_method" value="bank">
                            <i data-lucide="landmark" style="width: 24px; height: 24px; color: var(--text-muted);"></i>
                            <div class="pm-card-content" style="font-size: 14px; color: var(--text-main);"><?php echo __trans('pay_bank'); ?></div>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="recurring_weeks" style="font-weight: 600; margin-bottom: 6px; display: block;"><?php echo __trans('recurring_booking'); ?></label>
                    <select name="recurring_weeks" id="recurring_weeks" class="form-control">
                        <option value="1"><?php echo __trans('no_recurring'); ?></option>
                        <option value="2"><?php echo __trans('week_2'); ?></option>
                        <option value="3"><?php echo __trans('week_3'); ?></option>
                        <option value="4"><?php echo __trans('week_4'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="note" style="font-weight: 600; margin-bottom: 6px; display: block;">
                    <?php echo __trans('note_lbl'); ?>
                    <?php if ($_SESSION['user_id'] == $field['owner_id']) echo '<span style="color:red">* (Bắt buộc)</span>'; ?>
                </label>
                <textarea name="note" id="note" class="form-control" rows="2" placeholder="<?php echo ($_SESSION['user_id'] == $field['owner_id']) ? 'Vì bạn là chủ sân, hãy ghi chú đặt sân giùm ai...' : __trans('note_placeholder'); ?>" <?php if ($_SESSION['user_id'] == $field['owner_id']) echo 'required'; ?>><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
            </div>

            <button type="submit" id="btnSubmit" class="btn btn-primary" style="width: 100%; height: 54px; font-size: 16px; font-weight: 700; border-radius: 14px;" disabled>
                <i data-lucide="mouse-pointer-click"></i>&nbsp;&nbsp;<?php echo __trans('pls_select_slot'); ?>
            </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    const fieldPriceNormal = <?php echo floatval($field['price_per_hour'] ?? 0); ?>;
    const fieldPricePeak = <?php echo floatval(!empty($field['price_peak']) ? $field['price_peak'] : ($field['price_per_hour'] ?? 0)); ?>;
    const discountPct = <?php echo floatval($field['discount_percent'] ?? 0); ?>;
    const depositPct = <?php echo intval($field['deposit_percent'] ?? 0); ?>;
    let selectedHour = null;

    function selectSlot(el) {
        document.querySelectorAll('.slot-cell.selected').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        selectedHour = parseFloat(el.dataset.val);
        document.getElementById('selectedStartTime').value = el.dataset.time;
        recalcSlots();
    }

    function recalcSlots() {
        if (selectedHour === null) return;

        const duration = parseFloat(document.getElementById('duration').value);
        const halfHours = duration * 2;
        let totalPrice = 0;
        let hasPeak = false;
        let hasNormal = false;
        let blocked = false;

        // Clear all selections first
        document.querySelectorAll('.slot-cell').forEach(c => c.classList.remove('selected'));

        // Highlight the range of half-hours covered by this booking
        for (let i = 0; i < halfHours; i++) {
            const targetVal = selectedHour + i * 0.5;
            
            const blockHour = Math.floor(targetVal);
            const blockMin = (targetVal - blockHour > 0) ? '30' : '00';
            const timeStr = String(blockHour).padStart(2, '0') + ':' + blockMin;
            
            const cell = document.querySelector('.slot-cell[data-time="' + timeStr + '"]');
            if (cell) {
                if (cell.dataset.status !== 'available') {
                    blocked = true;
                }
                cell.classList.add('selected');
                
                const isPeak = (blockHour >= 17 && blockHour < 21);
                let price = isPeak ? fieldPricePeak : fieldPriceNormal;
                price = price * (1 - discountPct / 100);
                totalPrice += price / 2;
                if (isPeak) hasPeak = true; else hasNormal = true;
            } else {
                blocked = true; // slot doesn't exist (outside hours)
            }
        }

        const endVal = selectedHour + duration;
        let endHour = Math.floor(endVal);
        const endMin = (endVal - endHour > 0) ? '30' : '00';
        if (endHour >= 24) endHour -= 24;
        const endStr = String(endHour).padStart(2, '0') + ':' + endMin;
        const startStr = document.getElementById('selectedStartTime').value;

        document.getElementById('bookingSummary').style.display = 'block';
        document.getElementById('summaryTimeRange').textContent = startStr + ' → ' + endStr;
        document.getElementById('summaryDuration').textContent = duration + ' <?php echo __trans('hours'); ?>';
        
        let priceType = '';
        if (hasPeak && hasNormal) priceType = '<?php echo __trans('normal_price'); ?> + <?php echo __trans('slot_peak'); ?> 🔥';
        else if (hasPeak) priceType = '<?php echo __trans('slot_peak'); ?> 🔥';
        else priceType = '<?php echo __trans('normal_price'); ?>';
        document.getElementById('summaryPriceType').textContent = priceType;
        document.getElementById('summaryTotal').textContent = new Intl.NumberFormat('vi-VN').format(totalPrice) + 'đ';

        if (depositPct > 0) {
            let depositAmount = (totalPrice * depositPct) / 100;
            let remainingAmount = totalPrice - depositAmount;
            document.getElementById('depositRow').style.display = 'flex';
            document.getElementById('remainingRow').style.display = 'flex';
            document.getElementById('summaryDeposit').textContent = new Intl.NumberFormat('vi-VN').format(depositAmount) + 'đ';
            document.getElementById('summaryRemaining').textContent = new Intl.NumberFormat('vi-VN').format(remainingAmount) + 'đ';
        }

        const btn = document.getElementById('btnSubmit');
        if (blocked) {
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="alert-circle"></i>&nbsp;&nbsp;<?php echo __trans('slot_conflict'); ?>';
            btn.style.opacity = '0.5';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check-circle-2"></i>&nbsp;&nbsp;<?php echo __trans('confirm_booking'); ?> ' + startStr + ' → ' + endStr;
            btn.style.opacity = '1';
        }
        lucide.createIcons();
    }

    function selectPaymentMethod(method) {
        document.getElementById('pm-vnpay').classList.remove('active');
        document.getElementById('pm-bank').classList.remove('active');
        document.getElementById('pm-' + method).classList.add('active');
    }

    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (!document.getElementById('selectedStartTime').value) {
            e.preventDefault();
            alert('<?php echo __trans('pls_select_slot_alert'); ?>');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
