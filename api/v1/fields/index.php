<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$userLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$district = isset($_GET['district']) ? trim($_GET['district']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

try {
    $whereClauses = ["status = 'active'"];
    $params = [];

    if (!empty($district)) {
        $whereClauses[] = "district = :district";
        $params['district'] = $district;
    }
    if (!empty($type)) {
        $whereClauses[] = "type = :type";
        $params['type'] = $type;
    }
    if (!empty($search)) {
        $whereClauses[] = "name LIKE :search";
        $params['search'] = '%' . $search . '%';
    }

    $whereSql = implode(" AND ", $whereClauses);
    $orderSql = "ORDER BY rating DESC, total_bookings DESC"; // Default order

    $selectDistance = "NULL AS distance_km";
    if ($userLat && $userLng) {
        $selectDistance = "(ST_Distance_Sphere(point(lng, lat), point(:userLng, :userLat)) / 1000) AS distance_km";
        $params['userLng'] = $userLng;
        $params['userLat'] = $userLat;
        $orderSql = "ORDER BY distance_km ASC";
    }

    $sql = "SELECT id, name, slug, address, district, type, surface, price_per_hour, price_peak, 
            rating, total_reviews, total_bookings, cover_image, has_lighting, has_parking, has_shower, has_canteen, has_rental, lat, lng,
            $selectDistance
            FROM fields 
            WHERE $whereSql 
            $orderSql 
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    
    // Bind parameters carefully for limit/offset
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $fields = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $fields
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
