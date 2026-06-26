<?php
// ================================================
//  Search.php — หน้าผลการค้นหา Pawland
//
//  URL examples:
//  Search.php?search=กรุงเทพ
//  Search.php?category=โรงแรม
//  Search.php?search=กรุงเทพ&category=โรงแรม  ← "โรงแรมในกรุงเทพ"
// ================================================
session_start();
require_once 'connect.php';


// ── GET Parameters ───────────────────────────────
$search          = trim($_GET['search']   ?? '');
$category_filter = trim($_GET['category'] ?? '');
$page            = max(1, (int)($_GET['page'] ?? 1));
$per_page        = 7;

// ── Pet-type keywords ────────────────────────
// If the user mixes a place/province term with a pet keyword, both filters apply
$PET_KEYWORDS = [
    // สุนัข
    'หมา', 'สุนัข', 'น้องหมา', 'หมาเล็ก', 'หมาใหญ่', 'สุนัขพันธุ์', 'dog', 'dogs',
    // แมว
    'แมว', 'น้องแมว', 'แมวน้อย', 'แมวเล็ก', 'แมวใหญ่', 'cat', 'cats',
    // สัตว์อื่นๆ
    'กากกิท', 'นก', 'ปู', 'กิ้ง', 'เต่า', 'ฟันลิน', 'ฮัมสเตอร์', 'กากกิทแครคู',
    'สัตว์เลี้ยง', 'สัตว์บ้าน', 'แมวหมา', 'pet', 'pets',
];

// Extract pet keywords found in a search string; return matched keywords
function extractPetKeywords(string $term, array $keywords): array {
    $found = [];
    foreach ($keywords as $kw) {
        if (mb_strpos($term, $kw) !== false) $found[] = $kw;
    }
    return $found;
}

// Remove pet keywords from a string, return the remaining location/place text
function stripPetKeywords(string $term, array $keywords): string {
    foreach ($keywords as $kw) {
        $term = str_replace($kw, '', $term);
    }
    return trim($term);
}

function isPetKeyword(string $term, array $keywords): bool {
    return count(extractPetKeywords($term, $keywords)) > 0;
}

// ── Build WHERE conditions ───────────────
$results     = [];
$total       = 0;
$total_pages = 1;

if ($pdo) {
    try {
        $conditions = [];
        $params     = [];

        // Always filter approved places only
        $conditions[] = "status = 'approved'";

        if ($search !== '') {
            $petKws      = extractPetKeywords($search, $PET_KEYWORDS);
            $locationStr = stripPetKeywords($search, $PET_KEYWORDS);

            // 1) If there are pet keywords, filter by pet_type_allowed
            if (!empty($petKws)) {
                $petParts = [];
                foreach ($petKws as $i => $kw) {
                    $key = ':pet' . $i;
                    $petParts[] = "pet_type_allowed LIKE $key";
                    $params[$key] = '%' . $kw . '%';
                }
                $conditions[] = '(' . implode(' OR ', $petParts) . ')';
            }

            // 2) If there is remaining location/place text, search those fields
            if ($locationStr !== '') {
                $conditions[] = "(
                    province   LIKE :loc OR
                    place_name LIKE :loc OR
                    address    LIKE :loc OR
                    description LIKE :loc
                )";
                $params[':loc'] = '%' . $locationStr . '%';
            } elseif (empty($petKws)) {
                // Pure free-text search with no pet keywords
                $conditions[] = "(
                    province          LIKE :search OR
                    place_name        LIKE :search OR
                    address           LIKE :search OR
                    description       LIKE :search OR
                    pet_rules         LIKE :search OR
                    amenities         LIKE :search OR
                    extra_cost        LIKE :search OR
                    phone             LIKE :search
                )";
                $params[':search'] = '%' . $search . '%';
            }
        }

        // Filter by category (exact match)
        if ($category_filter !== '') {
            $conditions[] = "category = :category";
            $params[':category'] = $category_filter;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // ── Count total results ──
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM places $where");
        $countStmt->execute($params);
        $total       = (int)$countStmt->fetchColumn();
        $total_pages = max(1, (int)ceil($total / $per_page));
        $page        = min($page, $total_pages);
        $offset      = ($page - 1) * $per_page;

        // ── Fetch paginated results ──
        $stmt = $pdo->prepare("SELECT * FROM places $where ORDER BY place_id DESC LIMIT :limit OFFSET :offset");

        // Bind search/category params
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        // Bind LIMIT and OFFSET as integers (required by PDO)
        $stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll();

    } catch (PDOException $e) {
        $results = [];
        $total   = 0;
        error_log("Search query error: " . $e->getMessage());
    }
}

// ── Helpers ──────────────────────────────────────
function renderStars(int $count = 4): string {
    $html = '';
    for ($i = 0; $i < 5; $i++) {
        $color = $i < $count ? '#1e293b' : '#cbd5e1';
        $html .= '<span class="iconify" data-icon="material-symbols:star" 
                        data-width="18" data-height="18" style="color:' . $color . '"></span>';
    }
    return $html;
}

// Build URL keeping existing params, allowing overrides
function buildUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    // Remove empty values to keep URL clean
    $params = array_filter($params, fn($v) => $v !== '');
    return 'Search.php?' . http_build_query($params);
}

$cats = [
    ['icon' => 'fa6-solid:hotel',            'label' => 'โรงแรม',          'val' => 'โรงแรม'],
    ['icon' => 'carbon:cafe',                 'label' => 'คาเฟ่',           'val' => 'คาเฟ่'],
    ['icon' => 'material-symbols:restaurant', 'label' => 'ร้านอาหาร',       'val' => 'ร้านอาหาร'],
    ['icon' => 'ion:cut',                     'label' => 'อาบน้ำ ตัดขน',   'val' => 'อาบน้ำ ตัดขน'],
    ['icon' => 'mingcute:hospital-fill',      'label' => 'โรงพยาบาลสัตว์', 'val' => 'โรงพยาบาลสัตว์'],
];

// Build page title
if ($search && $category_filter) {
    if (isPetKeyword($search, $PET_KEYWORDS)) {
        $pageTitle = $category_filter . 'ที่รับ' . $search;  // เช่น "โรงแรมที่รับหมา"
    } else {
        $pageTitle = $category_filter . 'ใน' . $search;      // เช่น "โรงแรมในกรุงเทพ"
    }
} elseif ($search && isPetKeyword($search, $PET_KEYWORDS)) {
    $pageTitle = 'สถานที่ที่รับ "' . $search . '"';
} elseif ($search) {
    $pageTitle = 'ค้นหา "' . $search . '"';
} elseif ($category_filter) {
    $pageTitle = $category_filter . 'ทั้งหมด';
} else {
    $pageTitle = 'สถานที่ทั้งหมด';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — <?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="Search.css">
    <link rel="stylesheet" href="footer.css">
</head>
<body>

<!-- ══════════ HEADER ══════════ -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <img src="logo.png" alt="Logo" width="136" height="136">
            </div>
            <nav class="nav">
                <a href="home.php" class="nav-link">หน้าแรก</a>
                <a href="plantrip.php" class="nav-link">แผนเที่ยว</a>
                <a href="petinfo.php" class="nav-link">ข้อมูลสัตว์เลี้ยง</a>
                <a href="#" class="nav-link">ใกล้ฉัน</a>
            </nav>
            <div class="header-right">
                <div class="language-switch">
                    <span class="lang-active">TH</span>
                    <span class="lang-separator">|</span>
                    <span class="lang-inactive">EN</span>
                </div>
                <?php include 'header_user_icon.php'; ?>
            </div>
        </div>
    </div>
</header>

<!-- ══════════ MAIN ══════════ -->
<main class="search-main">
    <div class="search-page-container">

        <!-- ── SEARCH BAR (retains current search value) ── -->
        <section class="search-bar-section">
            <form method="GET" action="Search.php" id="searchForm">
                <div class="search-container">
                    <input
                        type="text"
                        name="search"
                        id="searchInput"
                        class="search-input"
                        placeholder="จังหวัด / สถานที่ท่องเที่ยว / โรงแรม"
                        value="<?= htmlspecialchars($search) ?>"
                        autocomplete="off"
                    >
                    <!-- Keep category when re-searching -->
                    <?php if ($category_filter): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                    <?php endif; ?>
                    <button type="button" class="filter-btn"
                            onclick="window.location.href='home.php'"
                            title="กลับหน้าแรก">
                        <span class="iconify" data-icon="mage:filter" data-width="24" data-height="24"></span>
                    </button>
                    <button type="submit" class="search-btn">
                        <span class="iconify" data-icon="material-symbols:search-rounded" data-width="28" data-height="28"></span>
                    </button>
                </div>
            </form>
        </section>

        <!-- ── CATEGORY TABS ── -->
        <section class="search-categories">
            <div class="search-category-grid">
                <?php foreach ($cats as $c):
                    $isActive = ($category_filter === $c['val']);
                    // Toggle: clicking active tab removes it, clicking inactive sets it
                    $url = $isActive
                        ? buildUrl(['category' => '', 'page' => 1])
                        : buildUrl(['category' => $c['val'], 'page' => 1]);
                ?>
                <a href="<?= $url ?>" class="search-category-card <?= $isActive ? 'active' : '' ?>">
                    <div class="search-category-icon">
                        <span class="iconify" data-icon="<?= $c['icon'] ?>" data-width="40" data-height="40"></span>
                    </div>
                    <span class="search-category-label"><?= $c['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ── RESULTS ── -->
        <section class="results-section">

            <!-- Result label / count -->
            <div class="results-meta">
                <span class="results-count">
                    <?php if ($search && $category_filter): ?>
                        <strong><?= htmlspecialchars($category_filter) ?></strong>
                        <?= isPetKeyword($search, $PET_KEYWORDS) ? 'ที่รับ' : 'ใน' ?>
                        "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php elseif ($search && isPetKeyword($search, $PET_KEYWORDS)): ?>
                        สถานที่ที่รับ "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php elseif ($search): ?>
                        ผลการค้นหา "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php elseif ($category_filter): ?>
                        หมวดหมู่: <strong><?= htmlspecialchars($category_filter) ?></strong>
                    <?php else: ?>
                        สถานที่ทั้งหมด
                    <?php endif; ?>
                    &nbsp;— พบ <strong><?= number_format($total) ?></strong> สถานที่
                </span>
                <?php if ($search || $category_filter): ?>
                    <a href="Search.php" class="clear-btn">✕ ล้างการค้นหา</a>
                <?php endif; ?>
            </div>

            <!-- ── RESULT CARDS ── -->
            <?php if (count($results) > 0): ?>
            <div class="result-list">
                <?php foreach ($results as $place): ?>
                <a class="result-card" href="place_detail.php?id=<?= (int)$place['place_id'] ?>">

                    <!-- Thumbnail -->
                    <div class="result-card-thumb">
                        <?php if (!empty($place['place_image'])): ?>
                            <img
                                src="<?= htmlspecialchars($place['place_image']) ?>"
                                alt="<?= htmlspecialchars($place['place_name']) ?>"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                            >
                            <div class="thumb-placeholder" style="display:none;">
                                <span class="iconify" data-icon="mdi:storefront-outline"></span>
                            </div>
                        <?php else: ?>
                            <div class="thumb-placeholder">
                                <span class="iconify" data-icon="mdi:storefront-outline"></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="result-card-body">
                        <div class="result-card-name"><?= htmlspecialchars($place['place_name']) ?></div>
                        <div class="result-card-location">
                            จังหวัด · <?= htmlspecialchars($place['province'] ?? '-') ?>
                        </div>
                        <div class="result-card-stars">
                            <?= renderStars(4) ?>
                            <span class="result-card-reviews">แสดงความคิดเห็น</span>
                        </div>
                        <div class="result-card-footer">
                            <span class="result-badge">pet-friendly</span>
                            <?php if (!empty($place['pet_size_allowed'])): ?>
                                <span class="result-pet-size">
                                    รองรับ : <?= htmlspecialchars($place['pet_size_allowed']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                </a>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <!-- ── NO RESULTS ── -->
            <div class="no-results">
                <span class="iconify" data-icon="mdi:magnify-remove-outline" data-width="64" data-height="64"></span>
                <p>
                    ไม่พบ
                    <?= $category_filter ? '<strong>' . htmlspecialchars($category_filter) . '</strong>' : 'สถานที่' ?>
                    <?= $search ? ' ใน "<strong>' . htmlspecialchars($search) . '</strong>"' : '' ?>
                </p>
                <small>ลองค้นหาด้วยคำอื่น หรือเลือกหมวดหมู่ด้านบน</small>
                <a href="home.php" class="back-home-btn">← กลับหน้าแรก</a>
            </div>
            <?php endif; ?>

            <!-- ── PAGINATION ── -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= buildUrl(['page' => $page - 1]) ?>" class="page-btn">‹</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <a href="<?= buildUrl(['page' => $p]) ?>"
                       class="page-btn <?= $p === $page ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= buildUrl(['page' => $page + 1]) ?>" class="page-btn">›</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </section>

    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    // Submit on Enter key
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchForm').submit();
        }
    });
</script>
</body>
</html>