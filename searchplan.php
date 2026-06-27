<?php
// ================================================
//  searchplan.php — ค้นหาสถานที่สำหรับหน้าแพลนทริป
//  GET ?q=keyword&cat=category
//  Returns JSON { places: [...] }
// ================================================
session_start();
require_once 'connect.php';

header('Content-Type: application/json; charset=utf-8');

$q   = trim($_GET['q']   ?? '');
$cat = trim($_GET['cat'] ?? '');

// ถ้าไม่มีเงื่อนไขเลย ส่งค่าว่างกลับทันที
if ($q === '' && $cat === '') {
    echo json_encode(['places' => []]);
    exit;
}

if (!$pdo) {
    echo json_encode(['places' => [], 'error' => 'db_error']);
    exit;
}

$PET_KEYWORDS = ['หมา', 'สุนัข', 'แมว', 'น้องหมา', 'น้องแมว'];
function isPetKw(string $term, array $kws): bool {
    foreach ($kws as $k) {
        if (mb_strpos($term, $k) !== false) return true;
    }
    return false;
}

// mapping: ถ้ากด category นี้ ให้เช็ค amenities คำไหนด้วย
$CAT_AMENITY_KEYWORDS = [
    'อาบน้ำ ตัดขน' => 'บริการอาบน้ำสัตว์เลี้ยง',
];

try {
    $conditions = [];
    $params     = [];

    // ── text search ──────────────────────────────────────────
    if ($q !== '') {
        if (isPetKw($q, $PET_KEYWORDS)) {
            $conditions[] = "pet_type_allowed LIKE :q";
            $params[':q']  = '%' . $q . '%';
        } else {
            $conditions[] = "(place_name LIKE :q OR province LIKE :q OR address LIKE :q OR description LIKE :q)";
            $params[':q']  = '%' . $q . '%';
        }
    }

    // ── category filter (รวม amenities ด้วยถ้ามี mapping) ───
    if ($cat !== '') {
        if (isset($CAT_AMENITY_KEYWORDS[$cat])) {
            $conditions[] = "(category = :cat OR (pet_amenities LIKE :cat_amenity OR amenities LIKE :cat_amenity))";
            $params[':cat']        = $cat;
            $params[':cat_amenity'] = '%' . $CAT_AMENITY_KEYWORDS[$cat] . '%';
        } else {
            $conditions[] = "category = :cat";
            $params[':cat'] = $cat;
        }
    }

    $where = $conditions ? 'WHERE status = \'approved\' AND ' . implode(' AND ', $conditions) : "WHERE status = 'approved'";

    $stmt = $pdo->prepare(
        "SELECT place_id, place_name, category, province, address,
                place_image, pet_type_allowed, pet_size_allowed
         FROM places
         $where
         ORDER BY
             CASE WHEN category = :cat_order THEN 0 ELSE 1 END,
             place_id DESC
         LIMIT 30"
    );

    // :cat_order ต้องมีเสมอ
    $params[':cat_order'] = $cat !== '' ? $cat : '';
    $stmt->execute($params);
    $places = $stmt->fetchAll();

    echo json_encode(['places' => $places]);

} catch (PDOException $e) {
    error_log("searchplan.php error: " . $e->getMessage());
    echo json_encode(['places' => [], 'error' => $e->getMessage()]);
}