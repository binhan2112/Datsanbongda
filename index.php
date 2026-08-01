<?php
require_once 'config/db.php';
require_once 'includes/auth_helper.php';

$district   = isset($_GET['district'])   ? trim($_GET['district'])   : '';
$type       = isset($_GET['type'])       ? trim($_GET['type'])       : '';
$keyword    = isset($_GET['keyword'])    ? trim($_GET['keyword'])    : '';
$min_price  = isset($_GET['min_price'])  ? intval($_GET['min_price']): 0;
$max_price  = isset($_GET['max_price'])  ? intval($_GET['max_price']): 0;
$min_rating = isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0;
$sort       = isset($_GET['sort'])       ? trim($_GET['sort'])       : 'popular';
$user_lat   = isset($_GET['user_lat'])   ? (float)$_GET['user_lat']  : null;
$user_lng   = isset($_GET['user_lng'])   ? (float)$_GET['user_lng']  : null;

$has_lighting = !empty($_GET['has_lighting']);
$has_parking  = !empty($_GET['has_parking']);
$has_shower   = !empty($_GET['has_shower']);
$has_canteen  = !empty($_GET['has_canteen']);
$has_rental   = !empty($_GET['has_rental']);

try {
    $districts_stmt = $pdo->query("SELECT DISTINCT district FROM fields WHERE status = 'active' ORDER BY district");
    $districts = $districts_stmt->fetchAll(PDO::FETCH_COLUMN);

    $select_clause = "*";
    if ($user_lat && $user_lng) {
        $select_clause = "*, (6371 * acos(cos(radians($user_lat)) * cos(radians(lat)) * cos(radians(lng) - radians($user_lng)) + sin(radians($user_lat)) * sin(radians(lat)))) AS distance";
    }

    $sql = "SELECT $select_clause FROM fields WHERE status = 'active'";
    $params = [];

    if ($district !== '') { $sql .= " AND district = :district"; $params['district'] = $district; }
    if ($type     !== '') { $sql .= " AND type = :type";         $params['type']     = $type; }
    if ($keyword  !== '') {
        $sql .= " AND (name LIKE :keyword_name OR address LIKE :keyword_address OR description LIKE :keyword_desc)";
        $params['keyword_name'] = '%' . $keyword . '%';
        $params['keyword_address'] = '%' . $keyword . '%';
        $params['keyword_desc'] = '%' . $keyword . '%';
    }
    if ($min_price > 0) { $sql .= " AND price_per_hour >= :min_price"; $params['min_price'] = $min_price; }
    if ($max_price > 0) { $sql .= " AND price_per_hour <= :max_price"; $params['max_price'] = $max_price; }
    if ($min_rating > 0) { $sql .= " AND rating >= :min_rating"; $params['min_rating'] = $min_rating; }

    if ($has_lighting) $sql .= " AND has_lighting = 1";
    if ($has_parking)  $sql .= " AND has_parking = 1";
    if ($has_shower)   $sql .= " AND has_shower = 1";
    if ($has_canteen)  $sql .= " AND has_canteen = 1";
    if ($has_rental)   $sql .= " AND has_rental = 1";

    // Ordering logic
    if ($sort === 'price_asc') {
        $sql .= " ORDER BY price_per_hour ASC";
    } elseif ($sort === 'price_desc') {
        $sql .= " ORDER BY price_per_hour DESC";
    } elseif ($sort === 'rating_desc') {
        $sql .= " ORDER BY rating DESC";
    } elseif ($sort === 'distance_asc' && $user_lat && $user_lng) {
        $sql .= " ORDER BY distance ASC";
    } else {
        $sql .= " ORDER BY total_bookings DESC, rating DESC";
    }
    
    $stmt  = $pdo->prepare($sql);
    $stmt->execute($params);
    $fields = $stmt->fetchAll();

    // Stats
    $total_fields = $pdo->query("SELECT COUNT(*) FROM fields WHERE status='active'")->fetchColumn();
    $total_districts = count($districts);

    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
    $fields = []; $districts = [];
    $total_fields = 0; $total_districts = 0;
}

$type_labels = ['5v5' => 'Sân 5 người', '7v7' => 'Sân 7 người', '11v11' => 'Sân 11 người'];
$amenity_map = [
    'has_lighting' => ['lightbulb', 'Đèn LED'],
    'has_shower'   => ['bath',      'Phòng tắm'],
    'has_canteen'  => ['coffee',    'Căn tin'],
    'has_parking'  => ['shield',    'Bãi xe'],
    'has_rental'   => ['shirt',     'Cho thuê'],
];
$field_images = [
    'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1540747913346-19212a4b423e?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1518063319789-7217e6706b04?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1574629810360-7efbbe195018?q=80&w=700&auto=format&fit=crop',
];

$base_url = '';
$current_page = 'home';
$page_title = 'Đặt Sân Bóng Đá Cần Thơ';
include 'includes/header.php';
?>

<!-- ══ HERO ═════════════════════════════════════════════════════ -->
<section class="hero">
    <!-- Background Slider -->
    <div class="hero-slider">
        <div class="hero-slide active slide-mu"></div>
        <div class="hero-slide slide-mc"></div>
        <div class="hero-slide slide-lfc"></div>
        <div class="hero-slide slide-cfc"></div>
        <div class="hero-slide slide-afc"></div>
        <div class="hero-overlay"></div>
    </div>

    <div class="container hero-wrapper" style="position: relative; z-index: 10;">
        <!-- Centered Content -->
        <div class="hero-text-col" style="width: 100%; max-width: 900px; margin: 0 auto; text-align: center;">
            <!-- Search Bar -->
            <form action="index.php" method="GET" class="hero-search" id="hero-search-form">
                <div class="hero-search-inner" style="max-width: 100%; margin: 0 auto;">
                    <i data-lucide="search" style="width:18px;height:18px;color:var(--text-muted);flex-shrink:0;"></i>
                    <input type="text" name="keyword" placeholder="<?php echo __trans('search_placeholder'); ?>" value="<?php echo htmlspecialchars($keyword); ?>" id="keyword-input" autocomplete="off">
    
                    <div class="search-divider"></div>
    
                    <div style="position:relative;">
                        <select name="district" style="background:transparent;border:none;outline:none;font-family:var(--font);font-size:14px;color:var(--text-secondary);padding:8px 24px 8px 4px;cursor:pointer;appearance:none;-webkit-appearance:none;min-width:130px;">
                            <option value=""><?php echo __trans('all_districts'); ?></option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php if ($district === $d) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($d); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
    
                    <button type="submit" class="btn btn-primary" style="border-radius:40px;padding:12px 24px;">
                        <?php echo __trans('search_btn'); ?>
                    </button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 16px;">
                <button type="button" class="btn btn-ghost" onclick="getLocationAndSearch()" style="background-color: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 600; border-radius: 40px; padding: 10px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: all 0.3s;">
                    <i data-lucide="map-pin"></i> <?php echo __trans('find_near_me'); ?>
                </button>
            </div>

            <!-- Stats -->
            <div class="stats-bar-new" style="justify-content: center; gap: 60px;">
                <div class="stat-item-new" style="text-align: center;">
                    <div class="stat-number-new"><?php echo $total_fields; ?>+</div>
                    <div class="stat-label-new"><?php echo __trans('active_fields'); ?></div>
                </div>
                <div class="stat-item-new" style="text-align: center;">
                    <div class="stat-number-new"><?php echo $total_districts; ?></div>
                    <div class="stat-label-new"><?php echo __trans('districts'); ?></div>
                </div>
                <div class="stat-item-new" style="text-align: center;">
                    <div class="stat-number-new">5★</div>
                    <div class="stat-label-new"><?php echo __trans('rating'); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Hero Background Slider
    document.addEventListener('DOMContentLoaded', () => {
        const slides = document.querySelectorAll('.hero-slide');
        let currentSlide = 0;
        
        if(slides.length > 0) {
            setInterval(() => {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }, 4000); // Đổi ảnh mỗi 4 giây
        }
    });
</script>

<!-- ══ FILTER BAR ════════════════════════════════════════════════ -->
<section class="container">
    <div class="filter-bar-section" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <form action="index.php" method="GET" id="filter-form">
            <?php if (!empty($keyword)): ?>
                <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
            <?php endif; ?>

            <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
                
                <!-- District & Type -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <select name="district" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); outline: none;">
                        <option value=""><?php echo __trans('all_districts_filter'); ?></option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php if ($district === $d) echo 'selected'; ?>>
                                Quận <?php echo htmlspecialchars($d); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="type" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); outline: none;">
                        <option value=""><?php echo __trans('all_field_types'); ?></option>
                        <option value="5v5" <?php echo $type === '5v5' ? 'selected' : ''; ?>><?php echo __trans('field_5v5'); ?></option>
                        <option value="7v7" <?php echo $type === '7v7' ? 'selected' : ''; ?>><?php echo __trans('field_7v7'); ?></option>
                        <option value="11v11" <?php echo $type === '11v11' ? 'selected' : ''; ?>><?php echo __trans('field_11v11'); ?></option>
                    </select>

                    <select name="sort" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); outline: none;">
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>><?php echo __trans('sort_popular'); ?></option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>><?php echo __trans('sort_price_asc'); ?></option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>><?php echo __trans('sort_price_desc'); ?></option>
                        <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>><?php echo __trans('sort_rating_desc'); ?></option>
                        <option value="distance_asc" <?php echo $sort === 'distance_asc' ? 'selected' : ''; ?>><?php echo __trans('sort_distance'); ?></option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('advanced-filters').classList.toggle('hidden')" style="border-radius: 8px; padding: 10px 15px;">
                        <i data-lucide="sliders-horizontal"></i> <?php echo __trans('advanced_filters'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px; padding: 10px 20px;">
                        <?php echo __trans('filter_btn'); ?>
                    </button>
                    <?php if ($district || $type || $keyword || $min_price || $max_price || $min_rating || $has_lighting || $has_parking || $has_shower || $has_canteen || $has_rental || $sort !== 'popular'): ?>
                        <a href="index.php" class="btn" style="border: 1px solid var(--error-color); color: var(--error-color); border-radius: 8px; padding: 10px 15px;">
                            <i data-lucide="x"></i> <?php echo __trans('clear_filter'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Advanced Filters (Hidden by default) -->
            <div id="advanced-filters" class="<?php echo ($min_price || $max_price || $min_rating || $has_lighting || $has_parking || $has_shower || $has_canteen || $has_rental) ? '' : 'hidden'; ?>" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    
                    <!-- Price Range -->
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 8px;"><?php echo __trans('price_range'); ?></label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="min_price" placeholder="<?php echo __trans('from'); ?>" value="<?php echo $min_price > 0 ? $min_price : ''; ?>" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <span>-</span>
                            <input type="number" name="max_price" placeholder="<?php echo __trans('to'); ?>" value="<?php echo $max_price > 0 ? $max_price : ''; ?>" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                        </div>
                    </div>

                    <!-- Rating -->
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 8px;"><?php echo __trans('min_rating'); ?></label>
                        <select name="min_rating" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <option value="0"><?php echo __trans('all_stars'); ?></option>
                            <option value="4.5" <?php echo $min_rating == 4.5 ? 'selected' : ''; ?>><?php echo __trans('stars_45'); ?></option>
                            <option value="4" <?php echo $min_rating == 4 ? 'selected' : ''; ?>><?php echo __trans('stars_4'); ?></option>
                            <option value="3" <?php echo $min_rating == 3 ? 'selected' : ''; ?>><?php echo __trans('stars_3'); ?></option>
                        </select>
                    </div>

                    <!-- Amenities -->
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 8px;"><?php echo __trans('amenities'); ?></label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;"><input type="checkbox" name="has_lighting" value="1" <?php echo $has_lighting ? 'checked' : ''; ?>> <?php echo __trans('led_light'); ?></label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;"><input type="checkbox" name="has_parking" value="1" <?php echo $has_parking ? 'checked' : ''; ?>> <?php echo __trans('parking'); ?></label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;"><input type="checkbox" name="has_shower" value="1" <?php echo $has_shower ? 'checked' : ''; ?>> <?php echo __trans('shower'); ?></label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;"><input type="checkbox" name="has_canteen" value="1" <?php echo $has_canteen ? 'checked' : ''; ?>> <?php echo __trans('canteen'); ?></label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;"><input type="checkbox" name="has_rental" value="1" <?php echo $has_rental ? 'checked' : ''; ?>> <?php echo __trans('rental'); ?></label>
                        </div>
                    </div>

                </div>
            </div>
            
            <?php if (isset($_GET['user_lat'])) echo '<input type="hidden" name="user_lat" id="hidden_user_lat" value="'.$_GET['user_lat'].'">'; ?>
            <?php if (isset($_GET['user_lng'])) echo '<input type="hidden" name="user_lng" id="hidden_user_lng" value="'.$_GET['user_lng'].'">'; ?>
        </form>
    </div>
</section>

<style>
    .hidden { display: none !important; }
</style>

<!-- ══ FIELDS LIST ═══════════════════════════════════════════════ -->
<main class="container" id="fields-list">
    <div class="section-header">
        <h2 class="section-title">
            <?php if ($type || $district || $keyword): ?>
                <?php echo __trans('search_results'); ?>
            <?php else: ?>
                <?php echo __trans('all_fields'); ?>
            <?php endif; ?>
            <span class="section-count" style="margin-left:10px;">(<?php echo count($fields); ?> <?php echo __trans('fields_count'); ?>)</span>
        </h2>
        <?php if (!empty($keyword)): ?>
            <span style="font-size:14px;color:var(--text-muted);"><?php echo __trans('keyword_lbl'); ?> <b style="color:var(--text-main);"><?php echo htmlspecialchars($keyword); ?></b></span>
        <?php endif; ?>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-danger">
            <i data-lucide="alert-circle"></i>
            <span>Không thể kết nối cơ sở dữ liệu: <?php echo htmlspecialchars($db_error); ?></span>
        </div>
    <?php endif; ?>

    <?php if (count($fields) > 0): ?>
        <div class="fields-grid fade-in-up">
            <?php foreach ($fields as $i => $field): ?>
                <?php 
                $img = !empty($field['cover_image']) ? $field['cover_image'] : $field_images[$field['id'] % count($field_images)]; 
                ?>
                <article class="field-card clickable-card" onclick="window.location.href='pages/detail.php?id=<?php echo $field['id']; ?>'">

                    <!-- Image -->
                    <div class="field-image">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($field['name']); ?>" loading="lazy">
                        <div class="field-image-overlay"></div>
                        <span class="field-badge"><?php echo htmlspecialchars($field['type']); ?></span>
                        
                        <?php if (isset($field['discount_percent']) && $field['discount_percent'] > 0): ?>
                            <span class="field-badge" style="background-color: var(--error-color); color: white; left: 14px; right: auto;"><?php echo __trans('discount'); ?> <?php echo $field['discount_percent']; ?>%</span>
                        <?php endif; ?>
                        
                        <?php if (isset($field['distance'])): ?>
                            <span class="field-rating-badge" style="top: 50px; bottom: auto; right: 14px; background-color: var(--primary); color: white; border: none; box-shadow: var(--shadow-sm);">
                                <i data-lucide="navigation" style="width:12px;height:12px;fill:white;"></i>
                                <?php echo number_format($field['distance'], 1); ?> km
                            </span>
                        <?php endif; ?>

                        <span class="field-rating-badge">
                            <i data-lucide="star" style="fill:var(--rating-color);width:12px;height:12px;"></i>
                            <?php echo number_format($field['rating'], 1); ?>
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="field-info">
                        <h3 class="field-name"><?php echo htmlspecialchars($field['name']); ?></h3>

                        <p class="field-address">
                            <i data-lucide="map-pin"></i>
                            <?php echo htmlspecialchars($field['address']); ?>
                        </p>

                        <!-- Amenities -->
                        <div class="field-amenities">
                            <?php foreach ($amenity_map as $key => [$icon, $label]): ?>
                                <?php if ($field[$key]): ?>
                                    <span class="amenity-pill">
                                        <i data-lucide="<?php echo $icon; ?>"></i>
                                        <?php echo $label; ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Footer -->
                        <div class="field-footer">
                            <div class="field-price">
                                <?php if ($field['discount_percent'] > 0): 
                                    $discounted_price = $field['price_per_hour'] * (1 - $field['discount_percent'] / 100);
                                ?>
                                    <span class="field-price-amount">
                                        <?php echo number_format($discounted_price, 0, ',', '.'); ?>đ
                                    </span>
                                    <span style="text-decoration: line-through; color: var(--text-muted); font-size: 13px; margin-left: 6px;">
                                        <?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?>đ
                                    </span>
                                <?php else: ?>
                                    <span class="field-price-amount">
                                        <?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?>đ
                                    </span>
                                <?php endif; ?>
                                <span class="field-price-unit"><?php echo __trans('per_hour_label'); ?></span>
                            </div>

                            <a href="pages/detail.php?id=<?php echo $field['id']; ?>" class="btn btn-primary btn-sm">
                                <?php echo __trans('book_now'); ?> <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="empty-state fade-in-up">
            <i data-lucide="search-x"></i>
            <p><?php echo __trans('no_fields_found_filter'); ?></p>
            <a href="index.php" class="btn btn-primary"><?php echo __trans('view_all_fields'); ?></a>
        </div>
    <?php endif; ?>
</main>

<script>
function getLocationAndSearch() {
    if (navigator.geolocation) {
        // Show loading state on button
        const btn = document.querySelector('button[onclick="getLocationAndSearch()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader" class="spin"></i> <?php echo __trans('getting_location'); ?>';
        
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('user_lat', lat);
            urlParams.set('user_lng', lng);
            urlParams.set('sort', 'distance_asc');
            window.location.href = `index.php?` + urlParams.toString();
        }, function(error) {
            let msg = 'Không thể lấy vị trí của bạn. ';
            if (error.code === error.PERMISSION_DENIED) {
                msg += 'Bạn đã từ chối/chặn quyền truy cập. Vui lòng nhấn vào biểu tượng ổ khóa 🔒 (hoặc cài đặt) bên cạnh thanh địa chỉ trình duyệt, chọn "Cho phép (Allow)" quyền Vị trí và thử lại.';
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                msg += 'Thông tin vị trí không khả dụng. Vui lòng kiểm tra kết nối mạng hoặc GPS.';
            } else if (error.code === error.TIMEOUT) {
                msg += 'Quá thời gian lấy thông tin vị trí.';
            } else {
                msg += 'Lỗi: ' + error.message;
            }
            alert(msg);
            btn.innerHTML = originalText;
            lucide.createIcons();
        });
    } else {
        alert("Trình duyệt của bạn không hỗ trợ định vị (Geolocation).");
    }
}
</script>

<?php if (is_logged_in() && empty($_GET)): ?>
<!-- Modal Gợi ý Tìm Kiếm Premium -->
<style>
@keyframes popIn {
    0% { opacity: 0; transform: scale(0.95) translateY(15px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
.premium-modal {
    max-width: 480px; 
    width: 90%;
    background: linear-gradient(145deg, #ffffff 0%, #f9fafb 100%);
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 24px 48px rgba(0,0,0,0.12), 0 0 0 1px rgba(255,255,255,0.7) inset;
    animation: popIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    position: relative;
    overflow: hidden;
    text-align: center;
}
.premium-modal::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 6px;
    background: linear-gradient(90deg, #10b981, #3b82f6);
}
.premium-select {
    width: 100%; 
    border-radius: 12px; 
    padding: 14px 16px; 
    border: 2px solid #e5e7eb;
    background-color: #f9fafb;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    transition: all 0.2s ease;
    cursor: pointer;
}
.premium-select:focus {
    background-color: #ffffff;
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16,185,129,0.1);
    outline: none;
}
.premium-btn {
    border-radius: 12px;
    padding: 14px 24px;
    font-weight: 700;
    font-size: 15px;
    transition: all 0.2s;
}
.premium-btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    box-shadow: 0 8px 20px rgba(16,185,129,0.3);
}
.premium-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(16,185,129,0.4);
}
.premium-btn-outline {
    background: white;
    color: #6b7280;
    border: 2px solid #e5e7eb;
}
.premium-btn-outline:hover {
    background: #f3f4f6;
    color: #374151;
}
</style>
<div id="onboardingModal" class="modal-overlay" style="display: none; z-index: 9999; backdrop-filter: blur(8px); background: rgba(17, 24, 39, 0.6);">
    <div class="premium-modal">
        <button onclick="skipOnboarding()" style="position: absolute; top: 16px; right: 16px; background: #f3f4f6; border: none; color: #9ca3af; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
            <i data-lucide="x" style="width: 18px; height: 18px;"></i>
        </button>
        
        <div style="margin-bottom: 28px;">
            <div style="width: 72px; height: 72px; background: linear-gradient(135deg, rgba(16,185,129,0.1) 0%, rgba(59,130,246,0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="search" style="width: 32px; height: 32px; color: #10b981;"></i>
            </div>
            <h3 style="font-size: 24px; font-weight: 800; color: #111827; margin-bottom: 8px; font-family: 'Outfit', sans-serif;">Tìm Sân Nhanh Chóng</h3>
            <p style="font-size: 15px; color: #6b7280; line-height: 1.5;">Chọn khu vực và loại sân để chúng tôi gợi ý cho bạn những lựa chọn tuyệt vời nhất hôm nay.</p>
        </div>

        <form action="index.php" method="GET" id="onboardingForm" style="text-align: left;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;">Khu Vực</label>
                <select name="district" class="premium-select">
                    <option value="">Tất cả khu vực</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 32px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;">Loại Sân</label>
                <select name="type" class="premium-select">
                    <option value="">Tất cả các loại sân</option>
                    <option value="5v5">Sân 5 người</option>
                    <option value="7v7">Sân 7 người</option>
                    <option value="11v11">Sân 11 người</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px; justify-content: space-between;">
                <button type="button" class="premium-btn premium-btn-outline" onclick="skipOnboarding()" style="flex: 1;">Bỏ qua</button>
                <button type="submit" class="premium-btn premium-btn-primary" onclick="skipOnboarding()" style="flex: 2;">Tìm Sân Ngay <i data-lucide="arrow-right" style="width: 16px; height: 16px; margin-left: 4px; vertical-align: middle;"></i></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.getElementById('onboardingModal').style.display = 'flex';
        // Re-init lucide icons for modal
        lucide.createIcons();
    }, 400); // slight delay for smooth appearance
});

function skipOnboarding() {
    document.getElementById('onboardingModal').style.opacity = '0';
    document.getElementById('onboardingModal').style.transition = 'opacity 0.3s ease';
    setTimeout(() => {
        document.getElementById('onboardingModal').style.display = 'none';
    }, 300);
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
