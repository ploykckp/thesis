<?php
// ================================================
//  admin_dashboard.php — Pawlands Admin Panel
// ================================================
session_start();
require_once 'connect.php';
require_once 'cloudinary_config.php';

// Auth check — ต้องล็อกอินเป็น admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$REJECTION_REASONS = [
    'ไม่แนบใบประกอบการ',
    'รายละเอียดสถานที่ไม่ครบ (เช่น ไม่มีคำอธิบาย / เวลาเปิด-ปิด)',
    'ไม่มีรูปภาพประกอบ',
    'ข้อมูลติดต่อไม่ถูกต้อง (เบอร์ / เว็บไซต์ใช้ไม่ได้)',
    'พิกัดแผนที่ (Latitude/Longitude) ไม่ตรง',
    'สถานที่ไม่อนุญาตให้นำสัตว์เลี้ยงเข้า',
    'อนุญาตเฉพาะบางพื้นที่ แต่ไม่ได้ระบุชัดเจน',
    'ไม่มีนโยบายเกี่ยวกับสัตว์เลี้ยง',
    'คำอธิบายสั้นเกินไป / ไม่สื่อความหมาย',
    'มีคำไม่สุภาพ หรือ Spam',
    'ภาพไม่ชัด / แตก / คุณภาพต่ำ',
    'ไม่ใช่ภาพของสถานที่จริง',
    'มีสถานที่นี้อยู่แล้วในระบบ',
    'ไม่สามารถยืนยันการมีอยู่จริงของสถานที่',
    'ไม่มีข้อมูลอ้างอิง (Google Maps / Social)',
    'ใช้ข้อมูลปลอม',
    'มีรายงานปัญหาด้านความปลอดภัย',
];

//  Handle AJAX: update status 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'update_place_status') {
        $place_id = (int)$_POST['place_id'];
        $status   = $_POST['status'];
        $reason   = $_POST['reason'] ?? '';
        $allowed  = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'invalid status']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE places SET status = :s, rejection_reason = :r, updated_at = NOW() WHERE place_id = :id");
            $stmt->execute([':s' => $status, ':r' => ($status === 'rejected' ? $reason : ''), ':id' => $place_id]);
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
    if ($_POST['action'] === 'update_status') {
        $entre_id  = (int)$_POST['entre_id'];
        $status    = $_POST['status'];    // pending | approved | rejected
        $reason    = $_POST['reason'] ?? '';
        $allowed   = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'invalid status']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE account_entre SET approval_status = :s, rejection_reason = :r, updated_at = NOW() WHERE entre_id = :id");
            $stmt->execute([':s' => $status, ':r' => ($status === 'rejected' ? $reason : ''), ':id' => $entre_id]);
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    // News: add
    if ($_POST['action'] === 'add_news') {
        try {
            $image = handleImageUpload('news_image', 'pawland/news');
            $stmt = $pdo->prepare("INSERT INTO news (badge,title,description,highlights_title,highlights,source,image,reverse_layout,status) VALUES (?,?,?,?,?,?,?,?,?) RETURNING id");
            $stmt->execute([
                    trim($_POST['badge']            ?? ''),
                    trim($_POST['title']            ?? ''),
                    trim($_POST['description']      ?? ''),
                    trim($_POST['highlights_title'] ?? ''),
                    trim($_POST['highlights']       ?? ''),
                    trim($_POST['source']           ?? ''),
                    $image,
                    (int)($_POST['reverse_layout']  ?? 0),
                    trim($_POST['status']           ?? 'published'),
                ]);
            echo json_encode(['ok'=>true,'id'=>(int)$stmt->fetchColumn()]);
        } catch (Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    // Page config: save
    if ($_POST['action'] === 'save_news_config') {
        try {
            $keys = ['header_tag','header_title','header_desc',
                     'stat1_number','stat1_label','stat2_number','stat2_label','stat3_number','stat3_label',
                     'highlight_section_title','highlight_box_title','highlight_items'];
            $stmt = $pdo->prepare("INSERT INTO news_page_config (cfg_key,cfg_value) VALUES (?,?) ON DUPLICATE KEY UPDATE cfg_value=VALUES(cfg_value)");
            foreach ($keys as $k) {
                $stmt->execute([$k, trim($_POST[$k] ?? '')]);
            }
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    // News: fetch single
    if ($_POST['action'] === 'fetch_news') {
        $stmt = $pdo->prepare("SELECT * FROM news WHERE id=?");
        $stmt->execute([(int)$_POST['news_id']]);
        echo json_encode($stmt->fetch() ?: []);
        exit;
    }

    // News: update
    if ($_POST['action'] === 'update_news') {
        try {
            $nid = (int)$_POST['news_id'];
            $row = $pdo->prepare("SELECT image FROM news WHERE id=?"); $row->execute([$nid]); $old = $row->fetch();
            $image = handleImageUpload('news_image', 'pawland/news', $old['image'] ?? '');
            $pdo->prepare("UPDATE news SET badge=?,title=?,description=?,highlights_title=?,highlights=?,source=?,image=?,reverse_layout=?,status=?,updated_at=NOW() WHERE id=?")
                ->execute([
                    trim($_POST['badge']            ?? ''),
                    trim($_POST['title']            ?? ''),
                    trim($_POST['description']      ?? ''),
                    trim($_POST['highlights_title'] ?? ''),
                    trim($_POST['highlights']       ?? ''),
                    trim($_POST['source']           ?? ''),
                    $image,
                    (int)($_POST['reverse_layout']  ?? 0),
                    trim($_POST['status']           ?? 'published'),
                    $nid,
                ]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    // News: delete
    if ($_POST['action'] === 'delete_news') {
        try {
            $nid = (int)$_POST['news_id'];
            $row = $pdo->prepare("SELECT image FROM news WHERE id=?"); $row->execute([$nid]); $old = $row->fetch();
            if ($old && $old['image'] && str_starts_with($old['image'],'uploads/') && file_exists($old['image'])) unlink($old['image']);
            $pdo->prepare("DELETE FROM news WHERE id=?")->execute([$nid]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    // ── Events: add ──────────────────────────────────────────────────────────
    if ($_POST['action'] === 'add_event') {
        try {
            $image = handleImageUpload('event_image', 'pawland/events');
            $stmt = $pdo->prepare("INSERT INTO events (title,date_start,date_end,location,description,tags,image,link_url,status,featured) VALUES (?,?,?,?,?,?,?,?,?,?) RETURNING id");
            $stmt->execute([
                    trim($_POST['title']       ?? ''),
                    trim($_POST['date_start']   ?? '') ?: null,
                    trim($_POST['date_end']     ?? '') ?: null,
                    trim($_POST['location']     ?? ''),
                    trim($_POST['description']  ?? ''),
                    trim($_POST['tags']         ?? ''),
                    $image,
                    trim($_POST['link_url']     ?? ''),
                    trim($_POST['status']       ?? 'published'),
                    isset($_POST['featured']) ? 1 : 0,
                ]);
            echo json_encode(['ok'=>true,'id'=>(int)$stmt->fetchColumn()]);
        } catch (Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    // Events: fetch single
    if ($_POST['action'] === 'fetch_event') {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id=?");
        $stmt->execute([(int)$_POST['event_id']]);
        echo json_encode($stmt->fetch() ?: []);
        exit;
    }

    // Events: update
    if ($_POST['action'] === 'update_event') {
        try {
            $eid   = (int)$_POST['event_id'];
            $row   = $pdo->prepare("SELECT image FROM events WHERE id=?"); $row->execute([$eid]); $old = $row->fetch();
            $image = handleImageUpload('event_image', 'pawland/events', $old['image'] ?? '');
            $pdo->prepare("UPDATE events SET title=?,date_start=?,date_end=?,location=?,description=?,tags=?,image=?,link_url=?,status=?,featured=?,updated_at=NOW() WHERE id=?")
                ->execute([
                    trim($_POST['title']       ?? ''),
                    trim($_POST['date_start']   ?? '') ?: null,
                    trim($_POST['date_end']     ?? '') ?: null,
                    trim($_POST['location']     ?? ''),
                    trim($_POST['description']  ?? ''),
                    trim($_POST['tags']         ?? ''),
                    $image,
                    trim($_POST['link_url']     ?? ''),
                    trim($_POST['status']       ?? 'published'),
                    isset($_POST['featured']) ? 1 : 0,
                    $eid,
                ]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    // Events: delete
    if ($_POST['action'] === 'delete_event') {
        try {
            $eid = (int)$_POST['event_id'];
            $row = $pdo->prepare("SELECT image FROM events WHERE id=?"); $row->execute([$eid]); $old = $row->fetch();
            if ($old && $old['image'] && str_starts_with($old['image'],'uploads/') && file_exists($old['image'])) unlink($old['image']);
            $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$eid]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    exit;
}

//  Image AJAX handler

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['img_ajax'])) {
    header('Content-Type: application/json');
    $pid = (int)$_GET['pid'];
    $row = $pdo->prepare("SELECT place_id,place_name,province,category,place_image,all_images FROM places WHERE place_id=?");
    $row->execute([$pid]);
    echo json_encode($row->fetch() ?: []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload images
    if (isset($_POST['img_upload'])) {
        header('Content-Type: application/json');
        $pid = (int)$_POST['place_id'];
        $row = $pdo->prepare("SELECT place_image, all_images FROM places WHERE place_id=?");
        $row->execute([$pid]); $place = $row->fetch();
        if (!$place) { echo json_encode(['ok'=>false,'msg'=>'ไม่พบสถานที่']); exit; }
        $uploaded = []; $errors = [];
        $files = $_FILES['images'] ?? [];
        $count = is_array($files['name']) ? count($files['name']) : 0;
        $allowed  = ['image/jpeg','image/jpg','image/png','image/webp','image/avif','image/gif'];
        for ($i=0; $i<$count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > 5*1024*1024) { $errors[] = $files['name'][$i].' เกิน 5MB'; continue; }
            if (!in_array($files['type'][$i], $allowed)) { $errors[] = $files['name'][$i].' ไม่รองรับ'; continue; }
            $url = cloudinaryUpload($files['tmp_name'][$i], 'pawland/places');
            if ($url) $uploaded[] = $url;
            else $errors[] = 'อัปโหลด '.$files['name'][$i].' ไม่สำเร็จ';
        }
        if (!empty($uploaded)) {
            $existing  = !empty($place['all_images']) ? array_filter(array_map('trim', explode(',', $place['all_images']))) : [];
            $allImages = array_values(array_unique(array_merge($existing, $uploaded)));
            $mainImage = $place['place_image'] ?: $uploaded[0];
            $pdo->prepare("UPDATE places SET place_image=?,all_images=?,updated_at=NOW() WHERE place_id=?")
                ->execute([$mainImage, implode(',', $allImages), $pid]);
            $msg = 'อัปโหลดสำเร็จ '.count($uploaded).' รูป';
            if ($errors) $msg .= ' (ข้อผิดพลาด: '.implode(', ', $errors).')';
            echo json_encode(['ok'=>true,'msg'=>$msg,'main_image'=>$mainImage]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>implode(', ', $errors) ?: 'ไม่มีไฟล์ที่อัปโหลดได้']);
        }
        exit;
    }
    // Set main image
    if (isset($_POST['img_set_main'])) {
        header('Content-Type: application/json');
        $pid = (int)$_POST['place_id']; $img = $_POST['set_main'];
        $pdo->prepare("UPDATE places SET place_image=?,updated_at=NOW() WHERE place_id=?")->execute([$img,$pid]);
        echo json_encode(['ok'=>true]);
        exit;
    }
    // Delete image
    if (isset($_POST['img_delete'])) {
        header('Content-Type: application/json');
        $pid = (int)$_POST['place_id']; $delImg = $_POST['delete_image'];
        $row = $pdo->prepare("SELECT place_image, all_images FROM places WHERE place_id=?");
        $row->execute([$pid]); $place = $row->fetch();
        if ($place) {
            $all = array_values(array_filter(array_map('trim', explode(',', $place['all_images']??'')), fn($i)=>$i!==$delImg));
            $main = $place['place_image']===$delImg ? ($all[0]??'') : $place['place_image'];
            $pdo->prepare("UPDATE places SET place_image=?,all_images=?,updated_at=NOW() WHERE place_id=?")->execute([$main,implode(',',$all),$pid]);
            if (str_starts_with($delImg,'uploads/') && file_exists($delImg)) unlink($delImg);
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    // ปิด wrapper "if (action)" ตรงนี้ — บล็อกด้านล่าง (delete_place, fetch_place,
    // update_place, add_place) ใช้ isset($_POST['xxx']) ของตัวเอง ไม่ได้พึ่ง 'action'
    // เดิมโค้ดลืมปิดวงเล็บตรงนี้ ทำให้ 4 ฟีเจอร์นี้รันไม่ถึงเลย
    }

    // delete place (admin only)
    if (isset($_POST['delete_place'])) {
        header('Content-Type: application/json');
        $pid = (int)$_POST['place_id'];
        if ($pid <= 0) { echo json_encode(['ok'=>false,'msg'=>'invalid']); exit; }
        try {
            $row = $pdo->prepare('SELECT place_image, all_images FROM places WHERE place_id=?');
            $row->execute([$pid]); $place = $row->fetch();
            if ($place) {
                $imgs = array_filter(array_map('trim', explode(',', $place['all_images'] ?? '')));
                if (!empty($place['place_image'])) $imgs[] = $place['place_image'];
                foreach (array_unique($imgs) as $img) {
                    if ($img && str_starts_with($img,'uploads/') && file_exists($img)) unlink($img);
                }
            }
            $pdo->prepare('DELETE FROM reviews WHERE place_id=?')->execute([$pid]);
            $pdo->prepare('DELETE FROM favorite WHERE place_id=?')->execute([$pid]);
            $pdo->prepare('DELETE FROM travel_plan_place WHERE place_id=?')->execute([$pid]);
            $pdo->prepare('DELETE FROM place_views WHERE place_id=?')->execute([$pid]);
            $pdo->prepare('DELETE FROM places WHERE place_id=?')->execute([$pid]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
        }
        exit;
    }

    // Fetch single place for edit
    if (isset($_POST['fetch_place'])) {
        header('Content-Type: application/json');
        $pid = (int)$_POST['place_id'];
        $stmt = $pdo->prepare("SELECT * FROM places WHERE place_id=?");
        $stmt->execute([$pid]);
        echo json_encode($stmt->fetch() ?: []);
        exit;
    }

    // Update place
    if (isset($_POST['update_place'])) {
        header('Content-Type: application/json');
        try {
            $pid = (int)$_POST['place_id'];
            $fields = [
                'place_name'       => trim($_POST['place_name']       ?? ''),
                'category'         => trim($_POST['category']         ?? ''),
                'address'          => trim($_POST['address']          ?? ''),
                'province'         => trim($_POST['province']         ?? ''),
                'latitude'         => (float)($_POST['latitude']      ?? 0),
                'longitude'        => (float)($_POST['longitude']     ?? 0),
                'description'      => trim($_POST['description']      ?? ''),
                'pet_type_allowed' => trim($_POST['pet_type_allowed'] ?? ''),
                'pet_size_allowed' => trim($_POST['pet_size_allowed'] ?? ''),
                'open_time'        => trim($_POST['open_time']        ?? ''),
                'close_time'       => trim($_POST['close_time']       ?? ''),
                'phone'            => trim($_POST['phone']            ?? ''),
                'extra_cost'       => trim($_POST['extra_cost']       ?? ''),
                'pet_rules'        => trim($_POST['pet_rules']        ?? ''),
                'amenities'        => trim($_POST['amenities']        ?? ''),
                'pet_amenities'    => trim($_POST['pet_amenities']    ?? ''),
                'status'           => trim($_POST['status']           ?? 'approved'),
            ];
            if (empty($fields['place_name']) || empty($fields['category']) || empty($fields['province'])) {
                echo json_encode(['ok'=>false,'msg'=>'กรุณากรอกชื่อสถานที่ ประเภท และจังหวัด']);
                exit;
            }
            $sets = implode(',', array_map(fn($k)=>"$k=?", array_keys($fields)));
            $vals = array_values($fields);
            $vals[] = $pid;
            $pdo->prepare("UPDATE places SET $sets, updated_at=NOW() WHERE place_id=?")->execute($vals);

            // Handle new image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $row = $pdo->prepare("SELECT place_image, all_images FROM places WHERE place_id=?");
                $row->execute([$pid]); $place = $row->fetch();
                $allowed  = ['image/jpeg','image/jpg','image/png','image/webp','image/avif','image/gif'];
                $newImgs  = [];
                $files    = $_FILES['images']; $count = count($files['name']);
                for ($i=0; $i<$count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($files['size'][$i]  > 5*1024*1024) continue;
                    if (!in_array($files['type'][$i], $allowed)) continue;
                    $url = cloudinaryUpload($files['tmp_name'][$i], 'pawland/places');
                    if ($url) $newImgs[] = $url;
                }
                if ($newImgs) {
                    $existing  = !empty($place['all_images']) ? array_filter(array_map('trim',explode(',',$place['all_images']))) : [];
                    $allImages = array_values(array_unique(array_merge($existing, $newImgs)));
                    $main      = $place['place_image'] ?: $newImgs[0];
                    $pdo->prepare("UPDATE places SET place_image=?,all_images=? WHERE place_id=?")->execute([$main,implode(',',$allImages),$pid]);
                }
            }
            echo json_encode(['ok'=>true,'msg'=>'บันทึกข้อมูลแล้ว']);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
        }
        exit;
    }

    // Add new place
    if (isset($_POST['add_place'])) {
        header('Content-Type: application/json');
        try {
            $data = [
                'place_name'       => trim($_POST['place_name']       ?? ''),
                'category'         => trim($_POST['category']         ?? ''),
                'address'          => trim($_POST['address']          ?? ''),
                'province'         => trim($_POST['province']         ?? ''),
                'latitude'         => (float)($_POST['latitude']      ?? 0),
                'longitude'        => (float)($_POST['longitude']     ?? 0),
                'description'      => trim($_POST['description']      ?? ''),
                'pet_type_allowed' => trim($_POST['pet_type_allowed'] ?? ''),
                'pet_size_allowed' => trim($_POST['pet_size_allowed'] ?? ''),
                'open_time'        => trim($_POST['open_time']        ?? ''),
                'close_time'       => trim($_POST['close_time']       ?? ''),
                'phone'            => trim($_POST['phone']            ?? ''),
                'extra_cost'       => trim($_POST['extra_cost']       ?? ''),
                'pet_rules'        => trim($_POST['pet_rules']        ?? ''),
                'amenities'        => trim($_POST['amenities']        ?? ''),
                'pet_amenities'    => trim($_POST['pet_amenities']    ?? ''),
                'status'           => 'approved',
                'pet_allowed'      => 'yes',
            ];
            if (empty($data['place_name']) || empty($data['category']) || empty($data['province'])) {
                echo json_encode(['ok'=>false,'msg'=>'กรุณากรอกชื่อสถานที่ ประเภท และจังหวัด']);
                exit;
            }
            $cols = implode(',', array_map(fn($k)=>"$k", array_keys($data)));
            $phs  = implode(',', array_fill(0, count($data), '?'));
            $stmt = $pdo->prepare("INSERT INTO places ($cols) VALUES ($phs) RETURNING place_id");
            $stmt->execute(array_values($data));
            $newId = (int)$stmt->fetchColumn();

            $mainImage = ''; $allImages = [];
            if (!empty($_FILES['images']['name'][0])) {
                $allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/avif','image/gif'];
                $files = $_FILES['images']; $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($files['size'][$i]  > 5*1024*1024) continue;
                    if (!in_array($files['type'][$i], $allowed)) continue;
                    $url = cloudinaryUpload($files['tmp_name'][$i], 'pawland/places');
                    if ($url) $allImages[] = $url;
                }
                if ($allImages) {
                    $mainImage = $allImages[0];
                    $pdo->prepare("UPDATE places SET place_image=?,all_images=? WHERE place_id=?")
                        ->execute([$mainImage, implode(',', $allImages), $newId]);
                }
            }
            echo json_encode(['ok'=>true,'msg'=>'เพิ่มสถานที่สำเร็จ','place_id'=>$newId]);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
        }
        exit;
    }



//  Fetch stats 
$totalPlaces    = 0;
$totalPlacesAll = 0;
$cntApproved  = 0;
$cntPending   = 0;
$cntRejected  = 0;
$entreCntApproved = 0;
$entreCntPending  = 0;
$entreCntRejected = 0;
$operators    = [];
$userGrowth   = [];
$totalUsers   = 0;
$allReviews   = [];
$reviewPending  = [];
$reviewApproved = [];
$reviewRejected = [];
$filterMode   = 'month';
$filterMonth  = 0;
$filterYear   = 0;
$YEAR_START_CE = 2026;
$totalUsersFiltered = 0;
$categoryData = [];
$categoryDataJson = '[]';

if ($pdo) {
    try {
        // place status counts (from places table)
        $placeRow = $pdo->query("SELECT
            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status='pending' OR status IS NULL OR status='' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected
            FROM places")->fetch();
        $cntApproved = (int)($placeRow['approved'] ?? 0);
        $cntPending  = (int)($placeRow['pending']  ?? 0);
        $cntRejected = (int)($placeRow['rejected'] ?? 0);

        // count approved and all places from places table
        $totalPlaces    = $cntApproved;
        $totalPlacesAll = (int)$pdo->query("SELECT COUNT(*) FROM places")->fetchColumn();

        // operator (account_entre) status counts
        $entreRow = $pdo->query("SELECT
            SUM(CASE WHEN approval_status='approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN approval_status='pending' OR approval_status IS NULL OR approval_status='' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN approval_status='rejected' THEN 1 ELSE 0 END) AS rejected
            FROM account_entre")->fetch();
        $entreCntApproved = (int)($entreRow['approved'] ?? 0);
        $entreCntPending  = (int)($entreRow['pending']  ?? 0);
        $entreCntRejected = (int)($entreRow['rejected'] ?? 0);

        // auto-add reg_docs column ถ้ายังไม่มี
        try { $pdo->exec("ALTER TABLE account_entre ADD COLUMN reg_docs TEXT DEFAULT NULL"); } catch (Throwable $e) {}

        // all operators
        $operators = $pdo->query("SELECT * FROM account_entre ORDER BY entre_id DESC")->fetchAll();

        // fetch pending places for admin review
        $pendingPlaces = $pdo->query("
            SELECT p.*, ae.business_name, ae.entre_firstname, ae.entre_lastname
            FROM places p
            LEFT JOIN account_entre ae ON p.entre_id = ae.entre_id
            WHERE p.status = 'pending' OR p.status IS NULL OR p.status = ''
            ORDER BY p.created_at DESC
        ")->fetchAll();

        // fetch approved places แยกออกมา
        $approvedPlaces = $pdo->query("
            SELECT p.*, ae.business_name, ae.entre_firstname, ae.entre_lastname
            FROM places p
            LEFT JOIN account_entre ae ON p.entre_id = ae.entre_id
            WHERE p.status = 'approved'
            ORDER BY p.created_at DESC
        ")->fetchAll();

        // ── กรองตาม เดือน / ปี ที่ admin เลือก ──────────────────────────
        // ปีพุทธศักราชเริ่มต้น 2569 = ค.ศ. 2026
        $YEAR_START_CE = 2026;   // ค.ศ.ของปีเริ่มต้น (พ.ศ. 2569)

        $filterMode  = trim($_GET['filter_mode'] ?? 'month');   // 'month' | 'year'
        $filterMonth = (int)($_GET['filter_month'] ?? 0);       // 1-12, 0 = ทุกเดือน
        $filterYear  = (int)($_GET['filter_year']  ?? 0);       // ค.ศ., 0 = ทุกปี

        // สถานที่ที่ลงทะเบียนใหม่ในแต่ละเดือน (approved + pending)
        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM account_user")->fetchColumn();
        $totalPlacesAll = (int)$pdo->query("SELECT COUNT(*) FROM places")->fetchColumn();
        $userGrowth = [];

        if ($filterMode === 'month' && $filterYear > 0 && $filterMonth > 0) {
            // รายวัน: สถานที่ที่เพิ่มในแต่ละวันของเดือนที่เลือก
            $ugStmt = $pdo->prepare(
                "SELECT DATE_FORMAT(created_at,'%Y-%m-%d') AS ym, COUNT(*) AS cnt
                 FROM places
                 WHERE YEAR(created_at)=? AND MONTH(created_at)=?
                 GROUP BY ym ORDER BY ym ASC"
            );
            $ugStmt->execute([$filterYear, $filterMonth]);
        } elseif ($filterYear > 0) {
            // รายเดือน: สถานที่ที่เพิ่มในแต่ละเดือนของปีที่เลือก
            $ugStmt = $pdo->prepare(
                "SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym, COUNT(*) AS cnt
                 FROM places
                 WHERE YEAR(created_at)=?
                 GROUP BY ym ORDER BY ym ASC"
            );
            $ugStmt->execute([$filterYear]);
        } else {
            // ไม่ได้เลือก: 6 เดือนล่าสุด
            $ugStmt = $pdo->prepare(
                "SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym, COUNT(*) AS cnt
                 FROM places
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                 GROUP BY ym ORDER BY ym ASC"
            );
            $ugStmt->execute([]);
        }
        $userGrowth = $ugStmt->fetchAll();
        $totalUsersFiltered = (int)array_sum(array_column($userGrowth, 'cnt'));
    } catch (Throwable $e) { /* silently skip chart */ }

    //  fetch reviews แยกออกมา ไม่ให้ outer catch กลืน 
    try {
        $allReviews = $pdo->query("
            SELECT r.*,
                   CONCAT(u.firstname_account, ' ', u.lastname_account) AS username,
                   p.place_name
            FROM reviews r
            LEFT JOIN account_user u ON r.user_id = u.user_id
            LEFT JOIN places p ON r.place_id = p.place_id
            ORDER BY r.created_at DESC
        ")->fetchAll();
        $reviewPending  = array_filter($allReviews, fn($r) => $r['status'] === 'pending');
        $reviewApproved = array_filter($allReviews, fn($r) => $r['status'] === 'approved');
        $reviewRejected = array_filter($allReviews, fn($r) => $r['status'] === 'rejected');
    } catch (Throwable $e) {
        $reviewQueryError = $e->getMessage();
    }
}

// Fetch news list
$newsList = [];
if ($pdo) {
    try {
        $newsList = $pdo->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();
    } catch (Throwable $e) { $newsList = []; }
}

// Fetch events list
$eventsList = [];
if ($pdo) {
    try {
        $eventsList = $pdo->query("SELECT * FROM events ORDER BY date_start ASC")->fetchAll();
    } catch (Throwable $e) { $eventsList = []; }
}

// Fetch news page config
$pageCfg = [];
if ($pdo) {
    try {
        $rows = $pdo->query("SELECT cfg_key, cfg_value FROM news_page_config")->fetchAll();
        foreach ($rows as $r) $pageCfg[$r['cfg_key']] = $r['cfg_value'];
    } catch (Throwable $e) {}
}
$pageCfg += [
    'header_tag'              => 'ข่าวและความเคลื่อนไหว',
    'header_title'            => 'ข่าวท่องเที่ยวเชิงสัตว์เลี้ยง (Pet Tourism)',
    'header_desc'             => '',
    'stat1_number' => '', 'stat1_label' => '',
    'stat2_number' => '', 'stat2_label' => '',
    'stat3_number' => '', 'stat3_label' => '',
    'highlight_section_title' => 'ไฮไลท์ข่าวท่องเที่ยว Pet-friendly',
    'highlight_box_title'     => 'สรุปความเคลื่อนไหวสำคัญ',
    'highlight_items'         => '',
];

// JSON for chart
$chartLabels = [];
$chartData   = [];

$thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

foreach ($userGrowth as $r) {
    $ym = $r['ym'];
    if ($filterMode === 'month' && $filterYear > 0 && $filterMonth > 0) {
        // format: YYYY-MM-DD → แสดงแค่วันที่
        $chartLabels[] = ltrim(substr($ym, 8), '0') ?: $ym;
    } else {
        // format: YYYY-MM → แสดงเป็น เดือน(ไทย) + ปี พ.ศ.
        $parts = explode('-', $ym);
        if (count($parts) === 2) {
            $mIdx = (int)$parts[1];
            $bYear = (int)$parts[0] + 543;
            $chartLabels[] = ($thMonths[$mIdx] ?? $ym) . ' ' . $bYear;
        } else {
            $chartLabels[] = $ym;
        }
    }
    $chartData[] = (int)$r['cnt'];
}
// no fallback: show real data only
$chartLabelsJson = json_encode($chartLabels, JSON_UNESCAPED_UNICODE);
$chartDataJson   = json_encode($chartData);

// Category breakdown data for province chart
$categoryData = [];
if ($pdo) {
    try {
        $cats = ['โรงแรม', 'คาเฟ่', 'ร้านอาหาร', 'อาบน้ำ ตัดขน', 'โรงพยาบาลสัตว์'];
        foreach ($cats as $cat) {
            $stmt = $pdo->prepare("SELECT TRIM(REPLACE(REPLACE(province, '\r', ''), '\n', '')) AS province, COUNT(*) as cnt FROM places WHERE category = ? AND status = 'approved' GROUP BY TRIM(REPLACE(REPLACE(province, '\r', ''), '\n', '')) ORDER BY cnt DESC");
            $stmt->execute([$cat]);
            $rows = $stmt->fetchAll();
            if ($rows) {
                $categoryData[$cat] = [
                    'labels' => array_column($rows, 'province'),
                    'data'   => array_map('intval', array_column($rows, 'cnt')),
                    'total'  => array_sum(array_column($rows, 'cnt')),
                ];
            } else {
                $categoryData[$cat] = ['labels'=>[],'data'=>[],'total'=>0];
            }
        }
    } catch (Throwable $e) { $categoryData = []; }
}
$categoryDataJson = json_encode($categoryData, JSON_UNESCAPED_UNICODE);

// ส่ง filter state ไปให้ HTML
$filterModeJson  = json_encode($filterMode);
$filterMonthJson = json_encode($filterMonth);
$filterYearJson  = json_encode($filterYear);
$yearStartCE     = $YEAR_START_CE ?? 2026;
$totalUsersFiltered = $totalUsersFiltered ?? $totalUsers;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — Pawlands</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#123451;--navy-dark:#0d2640;
  --bg:#f0f6ff;--card:#ffffff;
  --text:#1a2a3a;--muted:#6b8099;
  --border:rgba(18,52,81,0.12);
  --sidebar-w:220px;
  --green:#2e9e6e;--amber:#e09a1e;--red:#d44040;
}
body{font-family:'Kanit',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}

/*  SIDEBAR  */
.sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--navy);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100}
.sidebar-logo{padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:10px}
.logo-icon{width:36px;height:36px;background:rgba(255,255,255,.12);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.logo-text{font-size:15px;font-weight:600;color:#fff}
.logo-sub{font-size:10px;color:rgba(255,255,255,.4);letter-spacing:1px;text-transform:uppercase}
.sidebar-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:3px}
.nav-item{display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:10px;cursor:pointer;color:rgba(255,255,255,.6);font-size:14px;font-family:'Kanit',sans-serif;border:none;background:none;width:100%;text-align:left;transition:all .15s}
.nav-item:hover{background:rgba(255,255,255,.08);color:rgba(255,255,255,.9)}
.nav-item.active{background:rgba(255,255,255,.15);color:#fff;font-weight:500}
.nav-icon{width:17px;height:17px;flex-shrink:0;opacity:.8}
.sidebar-bottom{padding:14px 10px;border-top:1px solid rgba(255,255,255,.08)}
.logout-btn{display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:10px;cursor:pointer;color:rgba(255,255,255,.5);font-size:14px;font-family:'Kanit',sans-serif;background:none;border:none;width:100%;text-decoration:none;transition:all .15s}
.logout-btn:hover{color:rgba(255,255,255,.9);background:rgba(255,255,255,.08)}

/*  MAIN  */
.main{margin-left:var(--sidebar-w);flex:1;padding:28px;overflow-x:hidden}
.page{display:none}.page.active{display:block}
.page-header{margin-bottom:22px}
.page-title{font-size:22px;font-weight:600;color:var(--navy)}
.page-sub{font-size:13px;color:var(--muted);margin-top:3px}

/*  DASHBOARD STATS  */
.stats-row{display:grid;grid-template-columns:200px 200px 1fr;gap:16px;margin-bottom:20px}
.stat-main{background:var(--card);border-radius:16px;padding:22px 28px}
.stat-main-title{font-size:13px;font-weight:500;color:var(--text);margin-bottom:8px}
.stat-main-num{font-size:50px;font-weight:600;color:var(--navy);line-height:1}
.stat-main-label{font-size:12px;color:var(--muted);margin-top:5px}
.stat-group-wrap{display:flex;flex-direction:column;gap:8px}
.stat-group-header{font-size:13px;font-weight:500;color:var(--text)}
.stat-group{background:var(--card);border-radius:16px;padding:18px 24px;display:flex;align-items:center;flex:1}
.stat-item{flex:1;text-align:center;padding:6px 12px;border-right:1px solid var(--border)}
.stat-item:last-child{border-right:none}
.stat-item-num{font-size:40px;font-weight:600;line-height:1}
.stat-item-label{font-size:12px;color:var(--muted);margin-top:4px}
.s-green{color:var(--green)}.s-amber{color:var(--amber)}.s-red{color:var(--red)}

/*  CHART CARD  */
.card{background:var(--card);border-radius:16px;padding:22px;margin-bottom:16px}
.card-title{font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px}

/*  TABLE  */
.search-row{display:flex;gap:10px;margin-bottom:14px}
.search-input{flex:1;padding:9px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:'Kanit',sans-serif;background:var(--card);color:var(--text);outline:none}
.search-input:focus{border-color:#4a7aad}
.filter-sel{padding:9px 13px;border:1px solid var(--border);border-radius:10px;font-size:13px;font-family:'Kanit',sans-serif;background:var(--card);color:var(--text);outline:none;cursor:pointer}
.table-card{background:var(--card);border-radius:16px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{background:#f5f8fc;padding:11px 15px;text-align:left;font-size:11px;font-weight:500;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border)}
tbody tr{border-bottom:1px solid var(--border);transition:background .1s;cursor:pointer}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:#f8fbff}
td{padding:12px 15px;font-size:14px;color:var(--text);vertical-align:middle}

/*  BADGES  */
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500}
.badge-pending{background:#fff7e0;color:#a06c00}
.badge-approved{background:#e3f8ee;color:#1a6e42}
.badge-rejected{background:#fdecea;color:#b02a2a}
.type-badge{background:#eef3fa;color:var(--navy);padding:3px 10px;border-radius:6px;font-size:12px}

/*  INLINE SELECTS  */
.status-sel{padding:5px 9px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-family:'Kanit',sans-serif;cursor:pointer;background:var(--card);color:var(--text);outline:none}
.reason-sel{padding:5px 9px;border-radius:8px;border:1px solid #f5c0c0;font-size:12px;font-family:'Kanit',sans-serif;cursor:pointer;background:#fdecea;color:#9b2222;outline:none;max-width:220px}

/*  DETAIL PANEL  */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.22);z-index:200;display:none}
.overlay.show{display:block}
.detail-panel{position:fixed;top:0;right:0;width:500px;max-width:95vw;height:100vh;background:var(--card);box-shadow:-4px 0 24px rgba(0,0,0,.12);z-index:201;overflow-y:auto;transform:translateX(100%);transition:transform .25s ease}
.detail-panel.open{transform:translateX(0)}
.dp-header{background:var(--navy);padding:18px 22px;color:#fff;display:flex;justify-content:space-between;align-items:center}
.dp-title{font-size:16px;font-weight:500}
.dp-sub{font-size:12px;opacity:.6;margin-top:2px}
.dp-close{width:28px;height:28px;background:rgba(255,255,255,.15);border:none;border-radius:8px;cursor:pointer;color:#fff;font-size:15px;display:flex;align-items:center;justify-content:center}
.dp-body{padding:22px}
.dp-section{margin-bottom:22px}
.dp-sec-title{font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);font-weight:500;margin-bottom:10px;padding-bottom:5px;border-bottom:1px solid var(--border)}
.dp-row{display:flex;gap:10px;margin-bottom:9px;font-size:14px}
.dp-label{color:var(--muted);width:140px;flex-shrink:0;font-size:13px}
.dp-val{color:var(--text);flex:1}
.tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:5px}
.tag{background:#eef3fa;color:var(--navy);padding:4px 12px;border-radius:20px;font-size:12px}
.map-box{width:100%;height:110px;background:#d4e8f5;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--navy);font-size:13px;border:1px solid var(--border);margin-top:8px;text-decoration:none}
.map-box:hover{background:#c8dff0}

/*  PLACE DETAIL PANEL  */
.place-detail-panel{position:fixed;top:0;right:0;width:560px;max-width:95vw;height:100vh;background:var(--card);box-shadow:-4px 0 24px rgba(0,0,0,.15);z-index:202;overflow-y:auto;transform:translateX(100%);transition:transform .25s ease}
.place-detail-panel.open{transform:translateX(0)}
.pdp-header{background:var(--navy);padding:20px 24px;color:#fff}
.pdp-header-top{display:flex;justify-content:space-between;align-items:flex-start}
.pdp-title{font-size:17px;font-weight:600;margin-bottom:4px}
.pdp-sub{font-size:12px;opacity:.6}
.pdp-close{width:30px;height:30px;background:rgba(255,255,255,.15);border:none;border-radius:8px;cursor:pointer;color:#fff;font-size:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.pdp-gallery{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:16px 24px;background:#f5f8fc;border-bottom:1px solid var(--border)}
.pdp-img-main{grid-column:1/-1;height:180px;background:#dde8f5;border-radius:10px;overflow:hidden}
.pdp-img-main img{width:100%;height:100%;object-fit:cover}
.pdp-img-thumb{height:90px;background:#dde8f5;border-radius:8px;overflow:hidden}
.pdp-img-thumb img{width:100%;height:100%;object-fit:cover}
.pdp-no-img{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px}
.pdp-body{padding:22px 24px}
.pdp-section{margin-bottom:20px}
.pdp-sec-title{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);font-weight:600;margin-bottom:10px;padding-bottom:5px;border-bottom:1px solid var(--border)}
.pdp-row{display:flex;gap:10px;margin-bottom:8px;font-size:14px}
.pdp-label{color:var(--muted);width:130px;flex-shrink:0;font-size:13px}
.pdp-val{color:var(--text);flex:1}
.pdp-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px}
.pdp-tag{background:#eef3fa;color:var(--navy);padding:3px 10px;border-radius:20px;font-size:12px}
.pdp-status-row{display:flex;align-items:center;gap:10px;padding:14px 24px;background:#f5f8fc;border-top:1px solid var(--border);position:sticky;bottom:0}
.pdp-status-label{font-size:13px;color:var(--muted);flex-shrink:0}
.pdp-status-sel{padding:7px 12px;border-radius:8px;border:1px solid var(--border);font-size:14px;font-family:'Kanit',sans-serif;cursor:pointer;background:#fff;color:var(--text);outline:none;flex:1}
.pdp-save-btn{padding:7px 18px;background:var(--navy);color:#fff;border:none;border-radius:8px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer}
.pdp-save-btn:hover{background:var(--navy-dark)}
.pdp-lic-img{width:100%;max-height:200px;object-fit:contain;border-radius:8px;border:1px solid var(--border);margin-top:6px;background:#f5f8fc}
.clickable-row{cursor:pointer}
.clickable-row:hover td{background:#f0f7ff}

/* ── ADD PLACE FORM ── */
.ap-input{width:100%;padding:9px 13px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:'Kanit',sans-serif;background:#fff;color:var(--text);outline:none;transition:border-color .15s}
.ap-input:focus{border-color:#4a7aad}
.ap-input::placeholder{color:#b0bec5}
textarea.ap-input{resize:vertical}


.cat-chip:has(input:checked){border-color:var(--navy)!important;background:#dde8f5!important;color:var(--navy);font-weight:500}
</style>
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
</head>
<body>

<!--  SIDEBAR  -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="logo_w.png" alt="Pawlands" style="height:40px;object-fit:contain;">
  </div>

  <nav class="sidebar-nav">
    <button class="nav-item active" onclick="showPage('dashboard')" id="nav-dashboard">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>
      Dashboard
    </button>
    <button class="nav-item" onclick="showPage('operators')" id="nav-operators">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="3"/></svg>
      ผู้ประกอบการ
    </button>
    <button class="nav-item" onclick="showPage('approved')" id="nav-approved">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
      อนุมัติแล้ว
    </button>
    <button class="nav-item" onclick="showPage('places')" id="nav-places">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
      สถานที่รออนุมัติ
    </button>
    <button class="nav-item" onclick="showPage('reviews')" id="nav-reviews">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      รีวิว
      <?php if (count($reviewPending) > 0): ?>
      <span style="margin-left:auto;background:#ef4444;color:#fff;border-radius:999px;font-size:11px;padding:1px 7px;font-weight:700;"><?= count($reviewPending) ?></span>
      <?php endif; ?>
    </button>
    <button class="nav-item" onclick="showPage('images')" id="nav-images" style="display:none">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      รูปภาพสถานที่
    </button>
    <button class="nav-item" onclick="showPage('addplace')" id="nav-addplace">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      เพิ่มสถานที่
    </button>
    <button class="nav-item" onclick="showPage('editplace')" id="nav-editplace">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      แก้ไขสถานที่
    </button>
    <button class="nav-item" onclick="showPage('news')" id="nav-news">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="9"/></svg>
      ข่าว
    </button>
    <button class="nav-item" onclick="showPage('events')" id="nav-events">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      อีเวนต์
    </button>
  </nav>

  <div class="sidebar-bottom">
    <a href="admin_logout.php" class="logout-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Log Out
    </a>
  </div>
</aside>

<!--  MAIN  -->
<main class="main">

  <!--  DASHBOARD  -->
  <div class="page active" id="page-dashboard">
    <div class="page-header">
      <div class="page-title">Dashboard</div>
      <div class="page-sub">ภาพรวมระบบ Pawlands</div>
    </div>

    <div class="stats-row">
      <div class="stat-main">
        <div class="stat-main-title">สถานที่ที่อนุมัติแล้ว</div>
        <div class="stat-main-num"><?= $totalPlaces ?></div>
        <div class="stat-main-label">สถานที่</div>
      </div>
      <div class="stat-main">
        <div class="stat-main-title">สถานที่ทั้งหมด (รวมทุกสถานะ)</div>
        <div class="stat-main-num"><?= $totalPlacesAll ?></div>
        <div class="stat-main-label">สถานที่</div>
      </div>
      <div class="stat-group-wrap">
        <div class="stat-group-header">สถานะสถานที่ (places)</div>
        <div class="stat-group">
          <div class="stat-item">
            <div class="stat-item-num s-green"><?= $cntApproved ?></div>
            <div class="stat-item-label">อนุมัติแล้ว</div>
          </div>
          <div class="stat-item">
            <div class="stat-item-num s-amber"><?= $cntPending ?></div>
            <div class="stat-item-label">รอการยืนยัน</div>
          </div>
          <div class="stat-item">
            <div class="stat-item-num s-red"><?= $cntRejected ?></div>
            <div class="stat-item-label">ถูกปฏิเสธ</div>
          </div>
        </div>
        <div class="stat-group-header" style="margin-top:10px">สถานะผู้ประกอบการ (account_entre)</div>
        <div class="stat-group">
          <div class="stat-item">
            <div class="stat-item-num s-green"><?= $entreCntApproved ?></div>
            <div class="stat-item-label">อนุมัติแล้ว</div>
          </div>
          <div class="stat-item">
            <div class="stat-item-num s-amber"><?= $entreCntPending ?></div>
            <div class="stat-item-label">รอการยืนยัน</div>
          </div>
          <div class="stat-item">
            <div class="stat-item-num s-red"><?= $entreCntRejected ?></div>
            <div class="stat-item-label">ถูกปฏิเสธ</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px">
        <div>
          <div class="card-title" style="margin-bottom:0">สถานที่ที่เพิ่มใหม่</div>
          <div style="font-size:12px;color:var(--muted);margin-top:3px">
            <?php if ($filterMonth > 0 && $filterYear > 0): ?>
              <?php
                $thM=['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
                echo 'เดือน '.$thM[$filterMonth].' '.($filterYear+543).' — สถานที่เพิ่มใหม่ '.$totalUsersFiltered.' แห่ง';
              ?>
            <?php elseif ($filterYear > 0): ?>
              ปี พ.ศ. <?= $filterYear+543 ?> — สถานที่เพิ่มใหม่ <?= $totalUsersFiltered ?> แห่ง
            <?php else: ?>
              6 เดือนล่าสุด — สถานที่ในระบบทั้งหมด <?= $totalPlacesAll ?> แห่ง
            <?php endif; ?>
          </div>
        </div>

        <!-- ── Filter Controls ── -->
        <form method="GET" id="chartFilterForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <!-- dashboard filter: always stay on dashboard -->

          <!-- Mode toggle -->
          <div style="display:flex;border:1px solid var(--border);border-radius:8px;overflow:hidden;font-size:13px">
            <button type="button" id="modeMonthBtn"
              onclick="setFilterMode('month')"
              style="padding:6px 12px;border:none;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;transition:all .15s;
                     background:<?= ($filterMode!=='year') ? 'var(--navy)' : 'var(--card)' ?>;
                     color:<?= ($filterMode!=='year') ? '#fff' : 'var(--text)' ?>">รายเดือน</button>
            <button type="button" id="modeYearBtn"
              onclick="setFilterMode('year')"
              style="padding:6px 12px;border:none;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;transition:all .15s;
                     background:<?= ($filterMode==='year') ? 'var(--navy)' : 'var(--card)' ?>;
                     color:<?= ($filterMode==='year') ? '#fff' : 'var(--text)' ?>">รายปี</button>
          </div>
          <input type="hidden" name="filter_mode" id="filterModeInput" value="<?= htmlspecialchars($filterMode) ?>">

          <!-- Dropdown ปี (พ.ศ.) -->
          <select name="filter_year" id="filterYearSel" class="filter-sel"
            onchange="document.getElementById('chartFilterForm').submit()"
            style="min-width:100px">
            <option value="0">ทุกปี</option>
            <?php
              $currentCE = (int)date('Y');
              for ($y = $yearStartCE; $y <= $currentCE; $y++):
                $be = $y + 543;
                $sel = ($filterYear === $y) ? 'selected' : '';
            ?>
            <option value="<?= $y ?>" <?= $sel ?>>พ.ศ. <?= $be ?></option>
            <?php endfor; ?>
          </select>

          <!-- Dropdown เดือน (ซ่อนเมื่อ mode=year) -->
          <select name="filter_month" id="filterMonthSel" class="filter-sel"
            onchange="document.getElementById('chartFilterForm').submit()"
            style="min-width:110px;<?= ($filterMode==='year') ? 'display:none' : '' ?>">
            <option value="0">ทุกเดือน</option>
            <?php
              $thMonthNames=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                             'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
              for ($m=1; $m<=12; $m++):
                $sel = ($filterMonth===$m) ? 'selected' : '';
            ?>
            <option value="<?= $m ?>" <?= $sel ?>><?= $thMonthNames[$m] ?></option>
            <?php endfor; ?>
          </select>

          <?php if ($filterMonth > 0 || $filterYear > 0): ?>
          <a href="?<?= !empty($_GET['page']) ? 'page='.htmlspecialchars($_GET['page']).'&' : '' ?>filter_mode=month"
             style="font-size:12px;color:var(--muted);text-decoration:none;padding:6px 8px;border-radius:6px;border:1px solid var(--border)">
            ล้าง
          </a>
          <?php endif; ?>
        </form>
      </div>

      <div style="position:relative;width:100%;height:240px">
        <canvas id="userChart" role="img" aria-label="กราฟแสดงจำนวนผู้ใช้งาน">ข้อมูลผู้ใช้งาน</canvas>
      </div>
    </div>

    <!-- Category breakdown chart -->
    <div class="card" style="margin-top:20px">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div>
          <div class="card-title" style="margin-bottom:0">สถานที่ตามประเภท</div>
          <div style="font-size:12px;color:var(--muted);margin-top:3px" id="catChartSub">เลือกประเภทของสถานที่เพื่อดูรายละเอียด</div>
        </div>
        <select id="catChartSel" class="filter-sel" onchange="renderCatChart()" style="min-width:160px">
          <option value="">เลือกประเภท...</option>
          <option value="โรงแรม">โรงแรม</option>
          <option value="คาเฟ่">คาเฟ่</option>
          <option value="ร้านอาหาร">ร้านอาหาร</option>
          <option value="อาบน้ำ ตัดขน">อาบน้ำ ตัดขน</option>
          <option value="โรงพยาบาลสัตว์">โรงพยาบาลสัตว์</option>
        </select>
      </div>
      <div id="catChartWrap" style="position:relative;width:100%;height:260px">
        <div id="catChartEmpty" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:220px;color:#9aa8b7;gap:8px">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="8"/><rect x="13" y="6" width="3" height="12"/></svg>
          <div style="font-size:13px">เลือกประเภทสถานที่เพื่อดูรายละเอียด</div>
        </div>
        <canvas id="catChart" style="display:none"></canvas>
      </div>
    </div>

  </div>

  <!--  OPERATORS  -->
  <div class="page" id="page-operators">
    <div class="page-header">
      <div class="page-title">ผู้ประกอบการ</div>
      <div class="page-sub">รายการสถานที่ทั้งหมดที่ลงทะเบียน</div>
    </div>
    <div class="search-row">
      <input class="search-input" type="text" id="opSearch" placeholder="ค้นหาสถานที่หรือเจ้าของ..." oninput="filterOps()">
      <select class="filter-sel" id="opStatusFilter" onchange="filterOps()">
        <option value="">ทุกสถานะ</option>
        <option value="pending">รอการยืนยัน</option>
        <option value="approved">อนุมัติแล้ว</option>
        <option value="rejected">ถูกปฏิเสธ</option>
      </select>
    </div>
    <div class="table-card">
      <table id="opTable">
        <thead>
          <tr>
            <th>ชื่อสถานที่</th>
            <th>เจ้าของ</th>
            <th>อีเมล</th>
            <th>ประเภท</th>
            <th>สถานะ</th>
            <th>เหตุผล (กรณีปฏิเสธ)</th>
          </tr>
        </thead>
        <tbody id="opTbody">
          <?php foreach ($operators as $op):
            $status  = !empty($op['approval_status']) ? $op['approval_status'] : 'pending';
            $reason  = $op['rejection_reason'] ?? '';
            $name    = htmlspecialchars($op['business_name'] ?? '-');
            $owner   = htmlspecialchars(($op['entre_firstname'] ?? '') . ' ' . ($op['entre_lastname'] ?? ''));
            $email   = htmlspecialchars($op['entre_email'] ?? '');
            $type    = htmlspecialchars($op['business_type'] ?? '-');
            $id      = (int)$op['entre_id'];
          ?>
          <tr onclick="openDetail(<?= $id ?>)" data-id="<?= $id ?>" data-name="<?= $name ?>" data-status="<?= htmlspecialchars($status) ?>">
            <td><strong style="color:var(--navy)"><?= $name ?></strong></td>
            <td><?= $owner ?></td>
            <td style="font-size:13px;color:var(--muted)"><?= $email ?></td>
            <td><span class="type-badge"><?= $type ?></span></td>
            <td onclick="event.stopPropagation()">
              <select class="status-sel" data-id="<?= $id ?>" onchange="updateStatus(<?= $id ?>,this.value)">
                <option value="pending"  <?= $status==='pending'  ?'selected':'' ?>>รอยืนยัน</option>
                <option value="approved" <?= $status==='approved' ?'selected':'' ?>>อนุมัติ</option>
                <option value="rejected" <?= $status==='rejected' ?'selected':'' ?>>ปฏิเสธ</option>
              </select>
            </td>
            <td onclick="event.stopPropagation()" class="reason-cell-<?= $id ?>">
              <?php if ($status === 'rejected'): ?>
              <select class="reason-sel" data-id="<?= $id ?>" onchange="updateReason(<?= $id ?>,this.value)">
                <option value="">เลือกเหตุผล</option>
                <?php foreach ($REJECTION_REASONS as $r): ?>
                <option value="<?= htmlspecialchars($r) ?>" <?= $r===$reason?'selected':'' ?>><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
              </select>
              <?php else: ?>
              <span style="color:var(--muted);font-size:12px">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!--  APPROVED  -->
  <div class="page" id="page-approved">
    <div class="page-header">
      <div class="page-title">สถานที่อนุมัติแล้ว</div>
      <div class="page-sub">กดที่แถวเพื่อดูรายละเอียดที่ผู้ประกอบการกรอกมา</div>
    </div>
    <div class="search-row">
      <input class="search-input" type="text" id="apSearch" placeholder="ค้นหาสถานที่..." oninput="filterApproved()">
    </div>
    <div class="table-card">
      <table id="apTable">
        <thead>
          <tr>
            <th>ชื่อสถานที่</th>
            <th>เจ้าของ</th>
            <th>ประเภท</th>
            <th>จังหวัด</th>
            <th>รับสัตว์เลี้ยง</th>
          </tr>
        </thead>
        <tbody id="apTbody">
          <?php foreach ($approvedPlaces as $pl):
            $pstat = $pl['status'] ?? 'approved';
            $name  = htmlspecialchars($pl['place_name'] ?? '-');
            $owner = htmlspecialchars(($pl['entre_firstname'] ?? '') . ' ' . ($pl['entre_lastname'] ?? ''));
            $bname = htmlspecialchars($pl['business_name'] ?? '-');
            $type  = htmlspecialchars($pl['category'] ?? '-');
            $prov  = htmlspecialchars($pl['province'] ?? '-');
            $pets  = $pl['pet_allowed'] ?? 'ไม่ระบุ';
            $pid   = (int)$pl['place_id'];
          ?>
          <tr onclick="openPlaceDetail(<?= $pid ?>)" data-id="<?= $pid ?>">
            <td><strong style="color:var(--navy)"><?= $name ?></strong></td>
            <td><?= $bname ?><br><small style="color:var(--muted)"><?= $owner ?></small></td>
            <td><span class="type-badge"><?= $type ?></span></td>
            <td><?= $prov ?></td>
            <td><?= strtolower($pets)==='yes'||$pets==='ใช่'
                ? '<span style="color:var(--green)"> รับสัตว์เลี้ยง</span>'
                : (strtolower($pets)==='no'||$pets==='ไม่ใช่' ? '<span style="color:var(--red)"> ไม่รับ</span>' : $pets) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($approvedPlaces)): ?>
          <tr><td colspan="5" style="text-align:center;color:#999;padding:30px">ยังไม่มีสถานที่ที่อนุมัติแล้ว</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!--  PLACES REVIEW  -->
  <div class="page" id="page-places">
    <div class="page-header">
      <div class="page-title">สถานที่รออนุมัติ</div>
      <div class="page-sub">ตรวจสอบและอนุมัติสถานที่ที่ผู้ประกอบการเพิ่มเข้ามา</div>
    </div>
    <div class="search-row">
      <input class="search-input" type="text" id="plSearch" placeholder="ค้นหาสถานที่..." oninput="filterPlaces()">
    </div>
    <div class="table-card">
      <table id="plTable">
        <thead>
          <tr>
            <th>ชื่อสถานที่</th>
            <th>เจ้าของ/ธุรกิจ</th>
            <th>ประเภท</th>
            <th>จังหวัด</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody id="plTbody">
          <?php
          $pendingPlaces = $pendingPlaces ?? [];
          foreach ($pendingPlaces as $pl):
            $pid    = (int)$pl['place_id'];
            $pname  = htmlspecialchars($pl['place_name'] ?? '-');
            $bname  = htmlspecialchars($pl['business_name'] ?? '-');
            $owner  = htmlspecialchars(($pl['entre_firstname'] ?? '') . ' ' . ($pl['entre_lastname'] ?? ''));
            $cat    = htmlspecialchars($pl['category'] ?? '-');
            $prov   = htmlspecialchars($pl['province'] ?? '-');
            $pstat  = $pl['status'] ?? 'pending';
          ?>
          <tr class="clickable-row" data-pid="<?= $pid ?>" onclick="openPlaceDetail(<?= $pid ?>)">
            <td><strong style="color:var(--navy)"><?= $pname ?></strong></td>
            <td><?= $bname ?><br><small style="color:var(--muted)"><?= $owner ?></small></td>
            <td><span class="type-badge"><?= $cat ?></span></td>
            <td><?= $prov ?></td>
            <td><span class="badge badge-<?= $pstat ?>"><?= ['pending'=>'รอยืนยัน','approved'=>'อนุมัติ','rejected'=>'ปฏิเสธ'][$pstat] ?? $pstat ?></span></td>
            <td onclick="event.stopPropagation()">
              <select class="status-sel" onchange="updatePlaceStatus(<?= $pid ?>, this.value)">
                <option value="pending"  <?= $pstat==='pending'  ?'selected':'' ?>>รอยืนยัน</option>
                <option value="approved" <?= $pstat==='approved' ?'selected':'' ?>>อนุมัติ</option>
                <option value="rejected" <?= $pstat==='rejected' ?'selected':'' ?>>ปฏิเสธ</option>
              </select>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($pendingPlaces)): ?>
          <tr><td colspan="6" style="text-align:center;color:#999;padding:30px">ยังไม่มีสถานที่ในระบบ</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 
       PAGE: REVIEWS
   -->
  <div class="page" id="page-reviews">
    <div class="page-title" style="margin-bottom:6px">รีวิว</div>
    <div class="page-sub" style="margin-bottom:20px">จัดการรีวิวจากผู้ใช้งาน</div>

    <?php if (!empty($reviewQueryError)): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:14px 18px;border-radius:10px;margin-bottom:16px;font-size:13px;font-family:'Kanit',sans-serif;">
         ไม่สามารถดึงข้อมูลรีวิวได้: <?= htmlspecialchars($reviewQueryError) ?><br>
        <strong>วิธีแก้:</strong> กรุณา import ไฟล์ SQL ที่มี table <code>reviews</code> เข้า phpMyAdmin
    </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
      <button onclick="filterReviews('pending')"   id="rv-tab-pending"   style="padding:6px 18px;border-radius:20px;border:1.5px solid #f59e0b;background:#fff7e0;color:#a06c00;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;font-weight:600;">รออนุมัติ (<?= count($reviewPending) ?>)</button>
      <button onclick="filterReviews('approved')"  id="rv-tab-approved"  style="padding:6px 18px;border-radius:20px;border:1.5px solid #10b981;background:#d1fae5;color:#065f46;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;">อนุมัติแล้ว (<?= count($reviewApproved) ?>)</button>
      <button onclick="filterReviews('rejected')"  id="rv-tab-rejected"  style="padding:6px 18px;border-radius:20px;border:1.5px solid #ef4444;background:#fee2e2;color:#991b1b;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;">ปฏิเสธแล้ว (<?= count($reviewRejected) ?>)</button>
    </div>

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>ผู้รีวิว</th>
            <th>สถานที่</th>
            <th>คะแนน</th>
            <th>รีวิว</th>
            <th>วันที่</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody id="reviewTableBody">
          <?php foreach ($allReviews as $rv): ?>
          <tr class="review-row" data-status="<?= htmlspecialchars($rv['status']) ?>">
            <td><?= $rv['review_id'] ?></td>
            <td><?= htmlspecialchars($rv['username'] ?? 'ไม่ทราบ') ?></td>
            <td><?= htmlspecialchars($rv['place_name'] ?? '-') ?></td>
            <td>
              <span style="color:#f59e0b;font-size:15px;">
                <?= str_repeat('', (int)$rv['rating']) ?><?= str_repeat('', 5 - (int)$rv['rating']) ?>
              </span>
              <span style="font-size:12px;color:#64748b;margin-left:4px;">(<?= $rv['rating'] ?>)</span>
            </td>
            <td style="max-width:220px;">
              <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;" title="<?= htmlspecialchars($rv['comment'] ?? '') ?>">
                <?= htmlspecialchars(mb_substr($rv['comment'] ?? '', 0, 60)) ?><?= mb_strlen($rv['comment'] ?? '') > 60 ? '...' : '' ?>
              </span>
              <?php
                $rvImgs = !empty($rv['images']) ? json_decode($rv['images'], true) : [];
                if (!empty($rvImgs)):
              ?>
              <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;">
                <?php foreach (array_slice($rvImgs, 0, 3) as $img): ?>
                <img src="<?= htmlspecialchars($img) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid #e2e8f0;cursor:pointer;" onclick="window.open('<?= htmlspecialchars($img) ?>','_blank')">
                <?php endforeach; ?>
                <?php if (count($rvImgs) > 3): ?>
                <span style="font-size:11px;color:#64748b;align-self:center;">+<?= count($rvImgs)-3 ?></span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <?php if ($rv['status'] === 'rejected' && !empty($rv['rejection_reason'])): ?>
              <span style="font-size:11px;color:#ef4444;display:block;margin-top:2px;">เหตุผล: <?= htmlspecialchars($rv['rejection_reason']) ?></span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#64748b;"><?= date('d/m/Y H:i', strtotime($rv['created_at'])) ?></td>
            <td>
              <?php
                $badge = ['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-rejected'];
                $label = ['pending'=>'รออนุมัติ','approved'=>'อนุมัติแล้ว','rejected'=>'ปฏิเสธแล้ว'];
              ?>
              <span class="badge <?= $badge[$rv['status']] ?? 'badge-pending' ?>"><?= $label[$rv['status']] ?? $rv['status'] ?></span>
            </td>
            <td>
              <?php if ($rv['status'] === 'pending'): ?>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button onclick="reviewAction(<?= $rv['review_id'] ?>,'approve')"
                  style="padding:5px 12px;background:#10b981;color:#fff;border:none;border-radius:6px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer;">อนุมัติ</button>
                <button onclick="openRejectModal(<?= $rv['review_id'] ?>)"
                  style="padding:5px 12px;background:#ef4444;color:#fff;border:none;border-radius:6px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer;">ปฏิเสธ</button>
              </div>
              <?php elseif ($rv['status'] === 'approved'): ?>
              <button onclick="reviewAction(<?= $rv['review_id'] ?>,'reject_prompt')"
                style="padding:5px 12px;background:#f59e0b;color:#fff;border:none;border-radius:6px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer;">ถอน</button>
              <?php else: ?>
              <button onclick="reviewAction(<?= $rv['review_id'] ?>,'approve')"
                style="padding:5px 12px;background:#10b981;color:#fff;border:none;border-radius:6px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer;">อนุมัติใหม่</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($allReviews)): ?>
          <tr><td colspan="8" style="text-align:center;color:#999;padding:30px">ยังไม่มีรีวิว</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 
       PAGE: IMAGES
   -->
  <div class="page" id="page-images">
    <div class="page-header">
      <div class="page-title">รูปภาพสถานที่</div>
      <div class="page-sub">เลือกสถานที่จากรายการแล้วอัปโหลดรูปภาพ (รองรับ JPG, PNG, WEBP, AVIF ไม่เกิน 5MB/รูป)</div>
    </div>

    <?php
    $imgPlaces = [];
    $imgGrouped = [];
    if ($pdo) {
      try {
        $imgPlaces = $pdo->query("
          SELECT place_id, place_name, province, category, place_image, all_images
          FROM places WHERE status = 'approved'
          ORDER BY province ASC, place_name ASC
        ")->fetchAll();
        foreach ($imgPlaces as $p) {
          $imgGrouped[$p['province']][] = $p;
        }
      } catch (Throwable $e) {}
    }
    $noImgCount = count(array_filter($imgPlaces, fn($p) => !$p['place_image']));
    ?>

    <div id="imgAlertBox" style="display:none;padding:12px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;align-items:center;gap:10px"></div>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start">

      <!-- Place List -->
      <div style="background:var(--card);border-radius:16px;overflow:hidden;position:sticky;top:24px;max-height:calc(100vh - 140px);display:flex;flex-direction:column">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border)">
          <input class="search-input" type="text" id="imgSearch" placeholder="ค้นหาสถานที่..." oninput="filterImgPlaces()">
          <div style="margin-top:8px;font-size:12px;color:var(--muted)">
            ทั้งหมด <?= count($imgPlaces) ?> สถานที่ &nbsp;&bull;&nbsp;
            <span style="color:var(--amber)"><?= $noImgCount ?> ยังไม่มีรูป</span>
          </div>
        </div>
        <div style="overflow-y:auto;flex:1" id="imgPlaceList">
          <?php foreach ($imgGrouped as $prov => $plist): ?>
          <div class="img-prov-label" style="padding:8px 16px 2px;font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);font-weight:600;margin-top:4px"><?= htmlspecialchars($prov) ?></div>
          <?php foreach ($plist as $p): ?>
          <div class="img-place-row" data-pid="<?= $p['place_id'] ?>"
               data-name="<?= htmlspecialchars(strtolower($p['place_name'])) ?>"
               data-prov="<?= htmlspecialchars(strtolower($prov)) ?>"
               onclick="selectImgPlace(<?= $p['place_id'] ?>)"
               style="display:flex;align-items:center;gap:10px;padding:10px 16px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s">
            <?php if ($p['place_image']): ?>
            <img src="<?= htmlspecialchars($p['place_image']) ?>" alt=""
                 style="width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid var(--border)"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div style="display:none;width:40px;height:40px;border-radius:8px;background:#dde8f5;flex-shrink:0;align-items:center;justify-content:center;border:1px solid var(--border)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <?php else: ?>
            <div style="width:40px;height:40px;border-radius:8px;background:#dde8f5;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:1px solid var(--border)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <?php endif; ?>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:500;color:var(--text);line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($p['place_name']) ?></div>
              <div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($p['category']) ?></div>
              <?php if (!$p['place_image']): ?>
              <span style="font-size:10px;background:#fff3cd;color:#856404;padding:1px 7px;border-radius:10px;display:inline-block;margin-top:2px">ยังไม่มีรูป</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Upload Panel -->
      <div id="imgUploadPanel">
        <div style="background:var(--card);border-radius:16px;padding:60px 24px;text-align:center;color:var(--muted)">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2" style="margin-bottom:14px;display:block;margin-left:auto;margin-right:auto"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          <div style="font-size:15px;font-weight:500;color:var(--text);margin-bottom:6px">เลือกสถานที่จากรายการทางซ้าย</div>
          <div style="font-size:13px">เพื่อดูรูปภาพที่มีอยู่และอัปโหลดรูปใหม่</div>
        </div>
      </div>

    </div>
  </div>

  <!-- ══════════════════════════════════
       PAGE: ADD PLACE
  ══════════════════════════════════ -->
  <div class="page" id="page-addplace">
    <div class="page-header">
      <div class="page-title">เพิ่มสถานที่</div>
      <div class="page-sub">กรอกข้อมูลสถานที่ใหม่ สถานะจะตั้งเป็น "อนุมัติ" โดยอัตโนมัติ</div>
    </div>

    <div id="apAlertBox" style="display:none;padding:12px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;align-items:center;gap:10px"></div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

      <!-- LEFT COL -->
      <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Basic Info -->
        <div style="background:var(--card);border-radius:16px;padding:22px">
          <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">ข้อมูลหลัก</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ชื่อสถานที่ <span style="color:var(--red)">*</span></label>
              <input class="ap-input" id="ap_place_name" type="text" placeholder="เช่น Big Dog Cafe สาขาลาดพร้าว">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ประเภท <span style="color:var(--red)">*</span> <span style="font-weight:400;color:var(--muted)">(เลือกได้หลายประเภท)</span></label>
                <input type="hidden" id="ap_category" value="">
                <div id="ap_category_boxes" style="display:flex;flex-wrap:wrap;gap:8px;padding:8px 0">
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ap-cat-chip">
                    <input type="checkbox" value="โรงแรม" class="ap-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">โรงแรม</label>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ap-cat-chip">
                    <input type="checkbox" value="คาเฟ่" class="ap-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">คาเฟ่</label>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ap-cat-chip">
                    <input type="checkbox" value="ร้านอาหาร" class="ap-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">ร้านอาหาร</label>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ap-cat-chip">
                    <input type="checkbox" value="อาบน้ำ ตัดขน" class="ap-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">อาบน้ำ ตัดขน</label>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ap-cat-chip">
                    <input type="checkbox" value="โรงพยาบาลสัตว์" class="ap-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">โรงพยาบาลสัตว์</label>
                </div>
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">จังหวัด <span style="color:var(--red)">*</span></label>
                <select class="ap-input" id="ap_province">
                  <option value="">-- เลือกจังหวัด --</option>
                  <?php
                  $provinces = [
                      'กรุงเทพมหานคร','กระบี่','กาญจนบุรี','กาฬสินธุ์','กำแพงเพชร','ขอนแก่น','จันทบุรี','ฉะเชิงเทรา',
                      'ชลบุรี','ชัยนาท','ชัยภูมิ','ชุมพร','เชียงราย','เชียงใหม่','ตรัง','ตราด','ตาก','นครนายก',
                      'นครปฐม','นครพนม','นครราชสีมา','นครศรีธรรมราช','นครสวรรค์','นนทบุรี','นราธิวาส','น่าน',
                      'บึงกาฬ','บุรีรัมย์','ปทุมธานี','ประจวบคีรีขันธ์','ปราจีนบุรี','ปัตตานี','พระนครศรีอยุธยา',
                      'พะเยา','พังงา','พัทลุง','พิจิตร','พิษณุโลก','เพชรบุรี','เพชรบูรณ์','แพร่','ภูเก็ต',
                      'มหาสารคาม','มุกดาหาร','แม่ฮ่องสอน','ยโสธร','ยะลา','ร้อยเอ็ด','ระนอง','ระยอง','ราชบุรี',
                      'ลพบุรี','ลำปาง','ลำพูน','เลย','ศรีสะเกษ','สกลนคร','สงขลา','สตูล','สมุทรปราการ',
                      'สมุทรสงคราม','สมุทรสาคร','สระแก้ว','สระบุรี','สิงห์บุรี','สุโขทัย','สุพรรณบุรี',
                      'สุราษฎร์ธานี','สุรินทร์','หนองคาย','หนองบัวลำภู','อ่างทอง','อำนาจเจริญ','อุดรธานี',
                      'อุตรดิตถ์','อุทัยธานี','อุบลราชธานี',
                  ];
                  foreach ($provinces as $pv): ?>
                  <option><?= htmlspecialchars($pv) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ที่อยู่</label>
              <textarea class="ap-input" id="ap_address" rows="2" placeholder="เลขที่ ถนน แขวง/ตำบล เขต/อำเภอ รหัสไปรษณีย์"></textarea>
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">คำอธิบาย</label>
              <textarea class="ap-input" id="ap_description" rows="3" placeholder="บรรยายสถานที่ บรรยากาศ บริการ..."></textarea>
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">เบอร์โทรศัพท์</label>
              <input class="ap-input" id="ap_phone" type="text" placeholder="0xx-xxx-xxxx">
            </div>
          </div>
        </div>

        <!-- Location -->
        <div style="background:var(--card);border-radius:16px;padding:22px">
          <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">พิกัด</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">Latitude</label>
              <input class="ap-input" id="ap_latitude" type="number" step="any" placeholder="13.736717">
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">Longitude</label>
              <input class="ap-input" id="ap_longitude" type="number" step="any" placeholder="100.523186">
            </div>
          </div>
          <div style="font-size:12px;color:var(--muted)">
            เปิด <a href="https://maps.google.com" target="_blank" style="color:var(--navy)">Google Maps</a>
            คลิกขวาที่ตำแหน่ง แล้วคัดลอกพิกัดมาวาง
          </div>
        </div>

      </div>

      <!-- RIGHT COL -->
      <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Hours -->
        <div style="background:var(--card);border-radius:16px;padding:22px">
          <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">เวลาเปิด-ปิด</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">เปิด</label>
              <input class="ap-input" id="ap_open_time" type="time" value="09:00">
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ปิด</label>
              <input class="ap-input" id="ap_close_time" type="time" value="21:00">
            </div>
          </div>
        </div>

        <!-- Pet Policy -->
        <div style="background:var(--card);border-radius:16px;padding:22px">
          <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">นโยบายสัตว์เลี้ยง</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ประเภทสัตว์ที่รับ</label>
              <div style="display:flex;gap:8px;flex-wrap:wrap" id="ap_pet_type_wrap">
                <?php foreach (['สุนัข (หมา)','แมว','กระต่าย','นก','สัตว์แปลก'] as $pt): ?>
                <label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;background:#f1f5f9;padding:5px 12px;border-radius:20px;border:1px solid var(--border)">
                  <input type="checkbox" value="<?= $pt ?>" class="ap-pet-type" style="accent-color:var(--navy)"> <?= $pt ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ขนาดที่รับ</label>
              <select class="ap-input" id="ap_pet_size">
                <option value="">ไม่ระบุ</option>
                <option value="ทุกขนาด">ทุกขนาด</option>
                <option value="เล็ก">เล็ก</option>
                <option value="เล็ก-กลาง">เล็ก-กลาง</option>
                <option value="รับเฉพาะขนาดเล็ก-กลาง (≤ 25 กิโลกรัม)">เล็ก-กลาง (ไม่เกิน 25 กก.)</option>
              </select>
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ค่าใช้จ่ายเพิ่มเติม</label>
              <input class="ap-input" id="ap_extra_cost" type="text" placeholder="เช่น ค่าธรรมเนียมสัตว์เลี้ยง 500 บาท/คืน">
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">กฎสัตว์เลี้ยง</label>
              <textarea class="ap-input" id="ap_pet_rules" rows="2" placeholder="เช่น ต้องใส่สายจูงตลอดเวลา"></textarea>
            </div>
          </div>
        </div>

        <!-- Amenities -->
        <div style="background:var(--card);border-radius:16px;padding:22px">
          <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">สิ่งอำนวยความสะดวก</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:6px">ทั่วไป</label>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php foreach (['Wi-Fi ฟรี','ที่จอดรถฟรี','สระว่ายน้ำ','แผนกต้อนรับ 24 ชั่วโมง','ร้านอาหารในบริเวณ','รับฝากสัมภาระ','ห้องพักสัตว์เลี้ยง'] as $am): ?>
                <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;background:#f1f5f9;padding:4px 10px;border-radius:20px;border:1px solid var(--border)">
                  <input type="checkbox" value="<?= $am ?>" class="ap-amenity" style="accent-color:var(--navy)"> <?= $am ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:6px">สำหรับสัตว์เลี้ยง</label>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php foreach (['มีอาหารสำหรับสัตว์เลี้ยง','บริการอาบน้ำสัตว์เลี้ยง','สนามวิ่งเล่นสำหรับสัตว์เลี้ยง','ที่นอนสำหรับสัตว์เลี้ยง','ถาดอาหารสำหรับสัตว์เลี้ยง','ไดร์เป่าขนสำหรับสัตว์เลี้ยง','สระว่ายน้ำสำหรับสัตว์เลี้ยง'] as $pa): ?>
                <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;background:#f1f5f9;padding:4px 10px;border-radius:20px;border:1px solid var(--border)">
                  <input type="checkbox" value="<?= $pa ?>" class="ap-pet-amenity" style="accent-color:var(--navy)"> <?= $pa ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Images -->
        <div style="background:var(--card);border-radius:16px;padding:22px">
          <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">รูปภาพ</div>
          <div id="apDropzone"
               onclick="document.getElementById('apFileInput').click()"
               ondragover="event.preventDefault();this.style.borderColor='#4a7aad'"
               ondragleave="this.style.borderColor=''"
               ondrop="event.preventDefault();this.style.borderColor='';handleApFiles(event.dataTransfer.files)"
               style="border:2px dashed var(--border);border-radius:10px;padding:28px 16px;text-align:center;cursor:pointer;background:#fafcff;transition:all .2s">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:8px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <div style="font-size:13px;font-weight:500;color:var(--navy);margin-bottom:3px">คลิกหรือลากรูปมาวาง</div>
            <div style="font-size:11px;color:var(--muted)">JPG, PNG, WEBP, AVIF — สูงสุด 5MB/รูป</div>
          </div>
          <input type="file" id="apFileInput" multiple accept="image/*" style="display:none" onchange="handleApFiles(this.files)">
          <div id="apPreviewGrid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;margin-top:10px"></div>
        </div>

        <!-- Submit -->
        <div style="display:flex;gap:10px">
          <button onclick="submitAddPlace()"
                  style="flex:1;padding:12px;background:var(--navy);color:#fff;border:none;border-radius:12px;font-family:'Kanit',sans-serif;font-size:15px;font-weight:500;cursor:pointer;transition:background .15s"
                  onmouseover="this.style.background='var(--navy-dark)'" onmouseout="this.style.background='var(--navy)'">
            บันทึกสถานที่
          </button>
          <button onclick="resetAddPlace()"
                  style="padding:12px 22px;background:#f1f5f9;color:var(--text);border:none;border-radius:12px;font-family:'Kanit',sans-serif;font-size:15px;cursor:pointer">
            ล้างข้อมูล
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════
       PAGE: EDIT PLACE
  ══════════════════════════════════ -->
  <div class="page" id="page-editplace">
    <div class="page-header">
      <div class="page-title">แก้ไขสถานที่</div>
      <div class="page-sub">สถานที่ที่ admin เพิ่มเข้าระบบ — คลิกที่แถวเพื่อแก้ไข</div>
    </div>

    <div id="epAlertBox" style="display:none;padding:12px 18px;border-radius:10px;font-size:14px;margin-bottom:16px;align-items:center;gap:10px"></div>

    <!-- Search -->
    <div class="search-row">
      <input class="search-input" type="text" id="epSearch" placeholder="ค้นหาสถานที่..." oninput="filterEpPlaces()">
      <select class="filter-sel" id="epCatFilter" onchange="filterEpPlaces()">
        <option value="">ทุกประเภท</option>
        <?php foreach (['โรงแรม','คาเฟ่','ร้านอาหาร','อาบน้ำ ตัดขน','โรงพยาบาลสัตว์','รีสอร์ท','สวนสาธารณะ','อื่นๆ'] as $cat): ?>
        <option value="<?= $cat ?>"><?= $cat ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-sel" id="epProvFilter" onchange="filterEpPlaces()">
        <option value="">ทุกจังหวัด</option>
        <?php
        $epProvs = array_unique(array_column($pendingPlaces ?? [], 'province'));
        sort($epProvs);
        foreach ($epProvs as $pv): ?>
        <option value="<?= htmlspecialchars($pv) ?>"><?= htmlspecialchars($pv) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="table-card">
      <table id="epTable">
        <thead>
          <tr>
            <th>#</th>
            <th>ชื่อสถานที่</th>
            <th>ประเภท</th>
            <th>จังหวัด</th>
            <th>พิกัด</th>
            <th>รูปภาพ</th>
            <th>อัปเดต</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody id="epTbody">
          <?php
          $adminPlaces = $approvedPlaces ?? [];
          foreach ($adminPlaces as $pl):
            $pid   = (int)$pl['place_id'];
            $pname = htmlspecialchars($pl['place_name'] ?? '-');
            $cat   = htmlspecialchars($pl['category']   ?? '-');
            $prov  = htmlspecialchars($pl['province']   ?? '-');
            $lat   = $pl['latitude']  ?? 0;
            $lng   = $pl['longitude'] ?? 0;
            $hasCoord = ($lat != 0 && $lng != 0);
            $hasImg   = !empty($pl['place_image']);
            $updated  = $pl['updated_at'] ? date('d/m/y', strtotime($pl['updated_at'])) : '-';
          ?>
          <tr class="ep-row"
              data-pid="<?= $pid ?>"
              data-name="<?= htmlspecialchars(strtolower($pl['place_name'] ?? '')) ?>"
              data-cat="<?= htmlspecialchars($pl['category'] ?? '') ?>"
              data-prov="<?= htmlspecialchars($pl['province'] ?? '') ?>">
            <td style="color:var(--muted);font-size:12px"><?= $pid ?></td>
            <td>
              <?php if ($hasImg): ?>
              <div style="display:flex;align-items:center;gap:10px">
                <img src="<?= htmlspecialchars($pl['place_image']) ?>" style="width:36px;height:36px;border-radius:6px;object-fit:cover;flex-shrink:0" onerror="this.style.display='none'">
                <strong style="color:var(--navy)"><?= $pname ?></strong>
              </div>
              <?php else: ?>
              <strong style="color:var(--navy)"><?= $pname ?></strong>
              <?php endif; ?>
            </td>
            <td><span class="type-badge"><?= $cat ?></span></td>
            <td><?= $prov ?></td>
            <td>
              <?php if ($hasCoord): ?>
              <span style="color:var(--green);font-size:12px">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                <?= round($lat,4) ?>, <?= round($lng,4) ?>
              </span>
              <?php else: ?>
              <span style="color:var(--amber);font-size:12px">ยังไม่มีพิกัด</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($hasImg): ?>
              <span style="color:var(--green);font-size:12px">มีรูป</span>
              <?php else: ?>
              <span style="color:var(--amber);font-size:12px">ยังไม่มีรูป</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--muted)"><?= $updated ?></td>
            <td>
              <div style="display:flex;gap:6px;align-items:center">
                <button onclick="openEditPanel(<?= $pid ?>)"
                        style="padding:6px 14px;background:var(--navy);color:#fff;border:none;border-radius:8px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer">
                  แก้ไข
                </button>
                <button onclick="deletePlace(<?= $pid ?>, '<?= addslashes(htmlspecialchars($pl['place_name'] ?? '')) ?>')"
                        style="padding:6px 14px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer">
                  ลบ
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($adminPlaces)): ?>
          <tr><td colspan="8" style="text-align:center;color:#999;padding:30px">ยังไม่มีสถานที่ที่ admin เพิ่ม</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ EDIT PLACE PANEL ══ -->
  <div class="overlay" id="epOverlay" onclick="closeEditPanel()" style="z-index:201"></div>
  <div id="epPanel" style="position:fixed;top:0;right:0;width:600px;max-width:96vw;height:100vh;background:var(--card);box-shadow:-4px 0 24px rgba(0,0,0,.15);z-index:202;overflow-y:auto;transform:translateX(100%);transition:transform .25s ease">

    <!-- Header -->
    <div style="background:var(--navy);padding:18px 24px;color:#fff;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:1">
      <div>
        <div style="font-size:16px;font-weight:600" id="epPanelTitle">แก้ไขสถานที่</div>
        <div style="font-size:12px;opacity:.6;margin-top:2px" id="epPanelSub"></div>
      </div>
      <button onclick="closeEditPanel()" style="width:30px;height:30px;background:rgba(255,255,255,.15);border:none;border-radius:8px;cursor:pointer;color:#fff;font-size:16px;display:flex;align-items:center;justify-content:center">x</button>
    </div>

    <!-- Loading -->
    <div id="epPanelLoading" style="text-align:center;padding:60px;color:var(--muted)">กำลังโหลด...</div>

    <!-- Form Body -->
    <div id="epPanelBody" style="display:none;padding:24px">
      <input type="hidden" id="ep_place_id">

      <!-- Section: ข้อมูลหลัก -->
      <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">ข้อมูลหลัก</div>
      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px">
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ชื่อสถานที่ <span style="color:var(--red)">*</span></label>
          <input class="ap-input" id="ep_place_name" type="text">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div>
            <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ประเภท <span style="color:var(--red)">*</span> <span style="font-weight:400;color:var(--muted)">(เลือกได้หลายประเภท)</span></label>
            <input type="hidden" id="ep_category" value="">
            <div id="ep_category_boxes" style="display:flex;flex-wrap:wrap;gap:8px;padding:8px 0">
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ep-cat-chip">
                <input type="checkbox" value="โรงแรม" class="ep-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">โรงแรม</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ep-cat-chip">
                <input type="checkbox" value="คาเฟ่" class="ep-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">คาเฟ่</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ep-cat-chip">
                <input type="checkbox" value="ร้านอาหาร" class="ep-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">ร้านอาหาร</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ep-cat-chip">
                <input type="checkbox" value="อาบน้ำ ตัดขน" class="ep-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">อาบน้ำ ตัดขน</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--card);transition:all .15s" class="cat-chip ep-cat-chip">
                <input type="checkbox" value="โรงพยาบาลสัตว์" class="ep-cat-cb" style="width:14px;height:14px;accent-color:var(--navy)">โรงพยาบาลสัตว์</label>
            </div>
          </div>
          <div>
            <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">จังหวัด <span style="color:var(--red)">*</span></label>
            <select class="ap-input" id="ep_province">
              <option value="">-- เลือกจังหวัด --</option>
              <?php foreach ([
                  'กรุงเทพมหานคร','กระบี่','กาญจนบุรี','กาฬสินธุ์','กำแพงเพชร','ขอนแก่น','จันทบุรี','ฉะเชิงเทรา',
                  'ชลบุรี','ชัยนาท','ชัยภูมิ','ชุมพร','เชียงราย','เชียงใหม่','ตรัง','ตราด','ตาก','นครนายก',
                  'นครปฐม','นครพนม','นครราชสีมา','นครศรีธรรมราช','นครสวรรค์','นนทบุรี','นราธิวาส','น่าน',
                  'บึงกาฬ','บุรีรัมย์','ปทุมธานี','ประจวบคีรีขันธ์','ปราจีนบุรี','ปัตตานี','พระนครศรีอยุธยา',
                  'พะเยา','พังงา','พัทลุง','พิจิตร','พิษณุโลก','เพชรบุรี','เพชรบูรณ์','แพร่','ภูเก็ต',
                  'มหาสารคาม','มุกดาหาร','แม่ฮ่องสอน','ยโสธร','ยะลา','ร้อยเอ็ด','ระนอง','ระยอง','ราชบุรี',
                  'ลพบุรี','ลำปาง','ลำพูน','เลย','ศรีสะเกษ','สกลนคร','สงขลา','สตูล','สมุทรปราการ',
                  'สมุทรสงคราม','สมุทรสาคร','สระแก้ว','สระบุรี','สิงห์บุรี','สุโขทัย','สุพรรณบุรี',
                  'สุราษฎร์ธานี','สุรินทร์','หนองคาย','หนองบัวลำภู','อ่างทอง','อำนาจเจริญ','อุดรธานี',
                  'อุตรดิตถ์','อุทัยธานี','อุบลราชธานี',
              ] as $pv): ?>
              <option><?= htmlspecialchars($pv) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ที่อยู่</label>
          <textarea class="ap-input" id="ep_address" rows="2"></textarea>
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">คำอธิบาย</label>
          <textarea class="ap-input" id="ep_description" rows="3"></textarea>
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">เบอร์โทรศัพท์</label>
          <input class="ap-input" id="ep_phone" type="text">
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">สถานะ</label>
          <select class="ap-input" id="ep_status">
            <option value="approved">อนุมัติ</option>
            <option value="pending">รอยืนยัน</option>
            <option value="rejected">ปฏิเสธ</option>
          </select>
        </div>
      </div>

      <!-- Section: พิกัด -->
      <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">พิกัด</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:6px">
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">Latitude</label>
          <input class="ap-input" id="ep_latitude" type="number" step="any">
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">Longitude</label>
          <input class="ap-input" id="ep_longitude" type="number" step="any">
        </div>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:20px">
        เปิด <a href="https://maps.google.com" target="_blank" style="color:var(--navy)">Google Maps</a> คลิกขวาที่ตำแหน่ง แล้วคัดลอกพิกัดมาวาง
      </div>

      <!-- Section: เวลา -->
      <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">เวลาเปิด-ปิด</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">เปิด</label>
          <input class="ap-input" id="ep_open_time" type="time">
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ปิด</label>
          <input class="ap-input" id="ep_close_time" type="time">
        </div>
      </div>

      <!-- Section: นโยบายสัตว์เลี้ยง -->
      <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">นโยบายสัตว์เลี้ยง</div>
      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px">
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:6px">ประเภทสัตว์ที่รับ</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach (['สุนัข (หมา)','แมว','กระต่าย','นก','สัตว์แปลก'] as $pt): ?>
            <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;background:#f1f5f9;padding:4px 10px;border-radius:20px;border:1px solid var(--border)">
              <input type="checkbox" value="<?= $pt ?>" class="ep-pet-type" style="accent-color:var(--navy)"> <?= $pt ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ขนาดที่รับ</label>
          <select class="ap-input" id="ep_pet_size">
            <option value="">ไม่ระบุ</option>
            <option value="ทุกขนาด">ทุกขนาด</option>
            <option value="เล็ก">เล็ก</option>
            <option value="เล็ก-กลาง">เล็ก-กลาง</option>
            <option value="รับเฉพาะขนาดเล็ก-กลาง (≤ 25 กิโลกรัม)">เล็ก-กลาง (ไม่เกิน 25 กก.)</option>
          </select>
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ค่าใช้จ่ายเพิ่มเติม</label>
          <input class="ap-input" id="ep_extra_cost" type="text">
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">กฎสัตว์เลี้ยง</label>
          <textarea class="ap-input" id="ep_pet_rules" rows="2"></textarea>
        </div>
      </div>

      <!-- Section: สิ่งอำนวยความสะดวก -->
      <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">สิ่งอำนวยความสะดวก</div>
      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px">
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:6px">ทั่วไป</label>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php foreach (['Wi-Fi ฟรี','ที่จอดรถฟรี','สระว่ายน้ำ','แผนกต้อนรับ 24 ชั่วโมง','ร้านอาหารในบริเวณ','รับฝากสัมภาระ','ห้องพักสัตว์เลี้ยง'] as $am): ?>
            <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;background:#f1f5f9;padding:4px 10px;border-radius:20px;border:1px solid var(--border)">
              <input type="checkbox" value="<?= $am ?>" class="ep-amenity" style="accent-color:var(--navy)"> <?= $am ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:6px">สำหรับสัตว์เลี้ยง</label>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php foreach (['มีอาหารสำหรับสัตว์เลี้ยง','บริการอาบน้ำสัตว์เลี้ยง','สนามวิ่งเล่นสำหรับสัตว์เลี้ยง','ที่นอนสำหรับสัตว์เลี้ยง','ถาดอาหารสำหรับสัตว์เลี้ยง','ไดร์เป่าขนสำหรับสัตว์เลี้ยง','สระว่ายน้ำสำหรับสัตว์เลี้ยง'] as $pa): ?>
            <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;background:#f1f5f9;padding:4px 10px;border-radius:20px;border:1px solid var(--border)">
              <input type="checkbox" value="<?= $pa ?>" class="ep-pet-amenity" style="accent-color:var(--navy)"> <?= $pa ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Section: รูปภาพเพิ่มเติม -->
      <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">รูปภาพเพิ่มเติม</div>
      <div id="epDropzone"
           onclick="document.getElementById('epFileInput').click()"
           ondragover="event.preventDefault();this.style.borderColor='#4a7aad'"
           ondragleave="this.style.borderColor=''"
           ondrop="event.preventDefault();this.style.borderColor='';handleEpFiles(event.dataTransfer.files)"
           style="border:2px dashed var(--border);border-radius:10px;padding:24px 16px;text-align:center;cursor:pointer;background:#fafcff;transition:all .2s;margin-bottom:10px">
        <div style="font-size:13px;font-weight:500;color:var(--navy);margin-bottom:3px">คลิกหรือลากรูปมาวาง</div>
        <div style="font-size:11px;color:var(--muted)">JPG, PNG, WEBP, AVIF — สูงสุด 5MB/รูป</div>
      </div>
      <input type="file" id="epFileInput" multiple accept="image/*" style="display:none" onchange="handleEpFiles(this.files)">
      <div id="epImgPreview" style="display:none;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;margin-bottom:20px"></div>

      <!-- Save button -->
      <div style="display:flex;gap:10px;padding-top:8px;border-top:1px solid var(--border)">
        <button onclick="saveEditPlace()" id="epSaveBtn"
                style="flex:1;padding:12px;background:var(--navy);color:#fff;border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:500;cursor:pointer"
                onmouseover="this.style.background='var(--navy-dark)'" onmouseout="this.style.background='var(--navy)'">
          บันทึกการแก้ไข
        </button>
        <button onclick="closeEditPanel()"
                style="padding:12px 20px;background:#f1f5f9;color:var(--text);border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer">
          ยกเลิก
        </button>
      </div>
    </div>
  </div>

  <!-- NEWS PAGE -->
  <div class="page" id="page-news">
    <div class="page-header">
      <div class="page-title">จัดการข่าว</div>
      <div class="page-sub">เพิ่ม แก้ไข และลบข่าวที่แสดงในหน้า article_news.php</div>
    </div>

    <div id="newsAlertBox" style="display:none;padding:12px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;align-items:center;gap:10px"></div>

    <!-- News List Table -->
    <div id="newsListView">
      <!-- Tabs -->
      <div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:2px solid var(--border);padding-bottom:0">
        <button onclick="switchNewsTab('list')" id="ntab-list" style="background:none;border:none;padding:10px 20px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;font-weight:600;color:var(--navy);border-bottom:2px solid var(--navy);margin-bottom:-2px">รายการข่าว</button>
        <button onclick="switchNewsTab('config')" id="ntab-config" style="background:none;border:none;padding:10px 20px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-2px">ตั้งค่าหน้า</button>
      </div>

      <div id="newsTabList">
      <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <button onclick="openNewsForm(null)" style="background:var(--navy);color:#fff;border:none;padding:10px 22px;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          เพิ่มข่าวใหม่
        </button>
      </div>
      <div class="table-card">
        <table id="newsTable">
          <thead>
            <tr>
              <th style="width:60px">รูป</th>
              <th>หัวข้อ</th>
              <th style="width:120px">Badge</th>
              <th style="width:100px">สถานะ</th>
              <th style="width:80px">Layout</th>
              <th style="width:130px">วันที่</th>
              <th style="width:110px">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($newsList)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:32px">ยังไม่มีข่าว</td></tr>
            <?php else: ?>
            <?php foreach ($newsList as $n): ?>
            <tr id="news-row-<?= $n['id'] ?>">
              <td>
                <?php if ($n['image']): ?>
                  <img src="<?= htmlspecialchars($n['image']) ?>" style="width:48px;height:36px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                  <div style="width:48px;height:36px;background:var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-weight:500;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($n['title']) ?></td>
              <td><span style="background:#e8edf5;color:var(--navy);padding:2px 10px;border-radius:20px;font-size:12px"><?= htmlspecialchars($n['badge']) ?></span></td>
              <td>
                <?php if ($n['status'] === 'published'): ?>
                  <span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:20px;font-size:12px">เผยแพร่</span>
                <?php else: ?>
                  <span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:20px;font-size:12px">Draft</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:var(--muted)"><?= $n['reverse_layout'] ? 'สลับซ้าย' : 'ปกติ' ?></td>
              <td style="font-size:12px;color:var(--muted)"><?= date('d/m/Y', strtotime($n['created_at'])) ?></td>
              <td>
                <div style="display:flex;gap:6px">
                  <button onclick="openNewsForm(<?= $n['id'] ?>)" style="background:var(--navy);color:#fff;border:none;padding:5px 12px;border-radius:7px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer">แก้ไข</button>
                  <button onclick="deleteNews(<?= $n['id'] ?>, this)" style="background:#fee2e2;color:#991b1b;border:none;padding:5px 12px;border-radius:7px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer">ลบ</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      </div><!-- /newsTabList -->

      <!-- Config Tab -->
      <div id="newsTabConfig" style="display:none">
        <div style="background:var(--card);border-radius:16px;padding:24px;max-width:720px">
          <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:20px;padding-bottom:10px;border-bottom:1px solid var(--border)">ตั้งค่าส่วนหัวปัจจุบัน &amp; ไฮไลท์</div>

          <div id="cfgAlertBox" style="display:none;padding:10px 16px;border-radius:8px;font-size:13px;margin-bottom:16px"></div>

          <div style="display:flex;flex-direction:column;gap:14px">
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">Tag ของหัวปัจจุบัน</label>
              <input class="ap-input" id="cfg_header_tag" value="<?= htmlspecialchars($pageCfg['header_tag']) ?>" placeholder="ข่าวและความเคลื่อนไหว">
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">หัวข้อหลัก</label>
              <input class="ap-input" id="cfg_header_title" value="<?= htmlspecialchars($pageCfg['header_title']) ?>" placeholder="ข่าวท่องเที่ยว...">
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">คำอธิบายใต้หัวข้อ</label>
              <textarea class="ap-input" id="cfg_header_desc" rows="3" placeholder="คำอธิบาย..."><?= htmlspecialchars($pageCfg['header_desc']) ?></textarea>
            </div>

            <div style="font-size:13px;font-weight:600;color:var(--navy);margin-top:4px;padding-top:12px;border-top:1px solid var(--border)">Stats (3 ตัวเลข)</div>
            <?php foreach([1,2,3] as $si): ?>
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:10px">
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ตัวเลข <?= $si ?></label>
                <input class="ap-input" id="cfg_stat<?= $si ?>_number" value="<?= htmlspecialchars($pageCfg["stat{$si}_number"]) ?>" placeholder="เช่น 62%">
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">คำอธิบาย <?= $si ?></label>
                <input class="ap-input" id="cfg_stat<?= $si ?>_label" value="<?= htmlspecialchars($pageCfg["stat{$si}_label"]) ?>" placeholder="คำอธิบาย...">
              </div>
            </div>
            <?php endforeach; ?>

            <div style="font-size:13px;font-weight:600;color:var(--navy);margin-top:4px;padding-top:12px;border-top:1px solid var(--border)">ไฮไลท์</div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">หัวข้อ Section</label>
              <input class="ap-input" id="cfg_highlight_section_title" value="<?= htmlspecialchars($pageCfg['highlight_section_title']) ?>">
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">หัวข้อ Box</label>
              <input class="ap-input" id="cfg_highlight_box_title" value="<?= htmlspecialchars($pageCfg['highlight_box_title']) ?>">
            </div>
            <div>
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">รายการ Highlights <span style="font-weight:400;color:var(--muted)">(รูปแบบ: หัวข้อ|อธิบาย, แล้วขึ้นบรรทัดใหม่)</span></label>
              <textarea class="ap-input" id="cfg_highlight_items" rows="6" style="font-size:12px" placeholder="สรุป|อธิบายหั่วทีแ1&#10;หัวข้อ 2|อธิบาย"><?= htmlspecialchars($pageCfg['highlight_items']) ?></textarea>
            </div>
          </div>

          <div style="margin-top:20px;display:flex;justify-content:flex-end">
            <button onclick="saveNewsConfig()" style="background:var(--navy);color:#fff;border:none;padding:10px 28px;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;font-weight:600">บันทึก</button>
          </div>
        </div>
      </div><!-- /newsTabConfig -->
    </div><!-- /newsListView -->

    <!-- News Form (add/edit) -->
    <div id="newsFormView" style="display:none;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <button onclick="closeNewsForm()" style="background:var(--border);color:var(--text);border:none;padding:8px 16px;border-radius:8px;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;">← กลับ</button>
        <div style="font-size:18px;font-weight:600;color:var(--navy)" id="newsFormTitle">เพิ่มข่าวใหม่</div>
      </div>

      <input type="hidden" id="nw_id" value="">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <!-- LEFT -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <div style="background:var(--card);border-radius:16px;padding:22px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">ข้อมูลหลัก</div>
            <div style="display:flex;flex-direction:column;gap:12px">
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">Badge / Tag <span style="color:var(--red)">*</span></label>
                <input class="ap-input" id="nw_badge" type="text" placeholder="เช่น โครงการ ททท., แคมเปญภาคตะวันออก">
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">หัวข้อข่าว <span style="color:var(--red)">*</span></label>
                <input class="ap-input" id="nw_title" type="text" placeholder="หัวข้อข่าว">
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">คำอธิบาย <span style="color:var(--red)">*</span></label>
                <textarea class="ap-input" id="nw_description" rows="4" placeholder="เนื้อหาสรุปข่าว..."></textarea>
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">แหล่งที่มา</label>
                <input class="ap-input" id="nw_source" type="text" placeholder="เช่น การท่องเที่ยวแห่งประเทศไทย (ททท.)">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div>
                  <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">สถานะ</label>
                  <select class="ap-input" id="nw_status">
                    <option value="published">เผยแพร่</option>
                    <option value="draft">Draft (ซ่อน)</option>
                  </select>
                </div>
                <div>
                  <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">Layout รูป</label>
                  <select class="ap-input" id="nw_reverse">
                    <option value="0">ปกติ (รูปซ้าย)</option>
                    <option value="1">สลับ (รูปขวา)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <div style="background:var(--card);border-radius:16px;padding:22px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">รูปภาพ & Highlights</div>
            <div style="display:flex;flex-direction:column;gap:12px">
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">รูปภาพข่าว</label>
                <div id="nw_img_preview" style="width:100%;height:160px;border:2px dashed var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;overflow:hidden;background:var(--bg)">
                  <span style="color:var(--muted);font-size:13px">ยังไม่มีรูป</span>
                </div>
                <input type="file" id="nw_image" accept="image/*" style="font-size:13px;width:100%" onchange="previewNewsImg(this)">
                <div id="nw_img_current" style="font-size:11px;color:var(--muted);margin-top:4px"></div>
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">หัวข้อ Highlights</label>
                <input class="ap-input" id="nw_highlights_title" type="text" placeholder="เช่น พันธมิตรหลัก, ไฮไลท์แคมเปญ">
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">รายการ Highlights <span style="color:var(--muted);font-weight:400">(1 บรรทัด = 1 ข้อ)</span></label>
                <textarea class="ap-input" id="nw_highlights" rows="5" placeholder="รายการที่ 1&#10;รายการที่ 2&#10;รายการที่ 3"></textarea>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div style="margin-top:20px;display:flex;gap:12px;justify-content:flex-end">
        <button onclick="closeNewsForm()" style="background:var(--border);color:var(--text);border:none;padding:10px 24px;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer">ยกเลิก</button>
        <button onclick="saveNews()" style="background:var(--navy);color:#fff;border:none;padding:10px 28px;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;font-weight:600" id="nwSaveBtn">บันทึก</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════
       PAGE: EVENTS
  ══════════════════════════════════════════════════ -->
  <div class="page" id="page-events">
    <div class="page-header">
      <div class="page-title">จัดการอีเวนต์</div>
      <div class="page-sub">เพิ่ม แก้ไข และลบอีเวนต์ที่แสดงในหน้า article_events.php</div>
    </div>

    <div id="evAlertBox" style="display:none;padding:12px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;align-items:center;gap:10px"></div>

    <!-- Events List -->
    <div id="evListView">
      <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <button onclick="openEventForm(null)" style="background:var(--navy);color:#fff;border:none;padding:10px 22px;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          เพิ่มอีเวนต์ใหม่
        </button>
      </div>
      <div class="table-card">
        <table id="evTable">
          <thead>
            <tr>
              <th style="width:60px">รูป</th>
              <th>ชื่องาน</th>
              <th style="width:130px">วันที่จัดงาน</th>
              <th style="width:180px">สถานที่</th>
              <th style="width:100px">สถานะ</th>
              <th style="width:80px">ไฮไลท์</th>
              <th style="width:110px">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($eventsList)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">ยังไม่มีอีเวนต์ — กดปุ่ม "เพิ่มอีเวนต์ใหม่" เพื่อเริ่มต้น</td></tr>
            <?php else: ?>
            <?php foreach ($eventsList as $ev): ?>
            <?php
              $ds = $ev['date_start'] ? date('d/m/Y', strtotime($ev['date_start'])) : '–';
              $de = $ev['date_end']   ? date('d/m/Y', strtotime($ev['date_end']))   : '';
              $dateLabel = $de && $de !== $ds ? $ds.' – '.$de : $ds;
            ?>
            <tr id="ev-row-<?= $ev['id'] ?>">
              <td>
                <?php if ($ev['image']): ?>
                  <img src="<?= htmlspecialchars($ev['image']) ?>" style="width:48px;height:36px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                  <div style="width:48px;height:36px;background:var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-weight:500;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($ev['title']) ?></td>
              <td style="font-size:13px;color:var(--muted)"><?= $dateLabel ?></td>
              <td style="font-size:13px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($ev['location']) ?></td>
              <td>
                <?php if ($ev['status'] === 'published'): ?>
                  <span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:20px;font-size:12px">เผยแพร่</span>
                <?php else: ?>
                  <span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:20px;font-size:12px">Draft</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <?php if ($ev['featured']): ?>
                  <span title="งานไฮไลท์" style="font-size:18px"></span>
                <?php else: ?>
                  <span style="color:var(--muted);font-size:12px">–</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex;gap:6px">
                  <button onclick="openEventForm(<?= $ev['id'] ?>)" style="background:var(--navy);color:#fff;border:none;padding:5px 12px;border-radius:7px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer">แก้ไข</button>
                  <button onclick="deleteEvent(<?= $ev['id'] ?>, this)" style="background:#fee2e2;color:#991b1b;border:none;padding:5px 12px;border-radius:7px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer">ลบ</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div><!-- /evListView -->

    <!-- Events Form (add/edit) -->
    <div id="evFormView" style="display:none;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <button onclick="closeEventForm()" style="background:var(--border);color:var(--text);border:none;padding:8px 16px;border-radius:8px;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;">← กลับ</button>
        <div style="font-size:18px;font-weight:600;color:var(--navy)" id="evFormTitle">เพิ่มอีเวนต์ใหม่</div>
      </div>

      <input type="hidden" id="ev_id" value="">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <!-- LEFT -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <div style="background:var(--card);border-radius:16px;padding:22px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">ข้อมูลหลัก</div>
            <div style="display:flex;flex-direction:column;gap:12px">
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ชื่องาน <span style="color:var(--red)">*</span></label>
                <input class="ap-input" id="ev_title" type="text" placeholder="เช่น Pet Expo Thailand 2026">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div>
                  <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">วันเริ่มต้น</label>
                  <input class="ap-input" id="ev_date_start" type="date">
                </div>
                <div>
                  <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">วันสิ้นสุด</label>
                  <input class="ap-input" id="ev_date_end" type="date">
                </div>
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">สถานที่จัดงาน <span style="color:var(--red)">*</span></label>
                <input class="ap-input" id="ev_location" type="text" placeholder="เช่น ไบเทค บางนา, สยามพารากอน">
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">คำอธิบาย</label>
                <textarea class="ap-input" id="ev_description" rows="4" placeholder="รายละเอียดงาน..."></textarea>
              </div>
              <div>
                <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">แท็ก <span style="color:var(--muted);font-weight:400">(คั่นด้วย comma เช่น สุนัข,แมว,นิทรรศการ)</span></label>
                <input class="ap-input" id="ev_tags" type="text" placeholder="สุนัข,แมว,นิทรรศการ">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div>
                  <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">สถานะ</label>
                  <select class="ap-input" id="ev_status">
                    <option value="published">เผยแพร่</option>
                    <option value="draft">Draft (ซ่อน)</option>
                  </select>
                </div>
                <div>
                  <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">ลิงก์เว็บไซต์งาน</label>
                  <input class="ap-input" id="ev_link_url" type="url" placeholder="https://...">
                </div>
              </div>
              <div style="margin-top:4px">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px 14px;background:#f0f7ff;border-radius:10px;border:1px solid #c8e4fe">
                  <input type="checkbox" id="ev_featured" style="width:17px;height:17px;accent-color:var(--navy);cursor:pointer;flex-shrink:0">
                  <div>
                    <div style="font-size:13px;font-weight:600;color:var(--navy)">⭐ แสดงเป็นงานไฮไลท์</div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px">งานนี้จะแสดงในส่วน "งานไฮไลท์" บนหน้า article_events (มีได้ 1 งาน)</div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <div style="background:var(--card);border-radius:16px;padding:22px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">รูปภาพ</div>
            <div style="display:flex;flex-direction:column;gap:12px">
              <div>
                <div id="ev_img_preview" style="width:100%;height:200px;border:2px dashed var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;overflow:hidden;background:var(--bg)">
                  <span style="color:var(--muted);font-size:13px">ยังไม่มีรูป</span>
                </div>
                <input type="file" id="ev_image" accept="image/*" style="font-size:13px;width:100%" onchange="previewEventImg(this)">
                <div id="ev_img_current" style="font-size:11px;color:var(--muted);margin-top:4px"></div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div style="margin-top:20px;display:flex;gap:12px;justify-content:flex-end">
        <button onclick="closeEventForm()" style="background:var(--border);color:var(--text);border:none;padding:10px 24px;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer">ยกเลิก</button>
        <button onclick="saveEvent()" style="background:var(--navy);color:#fff;border:none;padding:10px 28px;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;font-weight:600" id="evSaveBtn">บันทึก</button>
      </div>
    </div><!-- /evFormView -->

  </div><!-- /page-events -->

<!--  REJECT REVIEW MODAL  -->
<div id="rejectOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:5000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:92%;max-width:500px;position:relative;box-shadow:0 8px 40px rgba(0,0,0,0.18);">
    <button onclick="closeRejectModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;"></button>
    <h3 style="font-family:'Kanit',sans-serif;font-size:16px;font-weight:600;color:#1e293b;margin:0 0 16px;">เหตุผลที่ปฏิเสธรีวิว</h3>
    <input type="hidden" id="rejectReviewId">
    <select id="rejectReason" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:'Kanit',sans-serif;font-size:13px;margin-bottom:16px;outline:none;">
      <option value="">-- เลือกเหตุผล --</option>
      <option>เนื้อหาไม่เกี่ยวข้องกับสถานที่</option>
      <option>รีวิวซ้ำกับที่เคยส่งแล้ว</option>
      <option>ใช้คำหยาบ คำไม่เหมาะสม หรือภาษารุนแรง</option>
      <option>มีการโจมตี ด่าทอ หรือใส่ร้ายบุคคล/ธุรกิจ</option>
      <option>มีเนื้อหาเชิงคุกคาม เหยียด หรือสร้างความเกลียดชัง</option>
      <option>มีสแปมหรือข้อความโฆษณา</option>
      <option>ข้อมูลไม่ตรงกับสถานที่จริง</option>
      <option>รีวิวจากผู้ที่ไม่ได้ใช้บริการจริง</option>
      <option>ให้ข้อมูลเกินจริงหรือทำให้เข้าใจผิด</option>
      <option>ระบุรายละเอียดสำคัญผิด เช่น เวลาเปิด-ปิด ราคา หรือบริการ</option>
      <option>รีวิวไม่ได้เกี่ยวข้องกับการพาสัตว์เลี้ยงเข้าใช้บริการ</option>
      <option>ข้อมูล Pet Friendly ไม่ชัดเจน</option>
      <option>ไม่มีข้อมูลสำคัญ เช่น ขนาดสัตว์ที่เข้าได้ หรือโซนที่อนุญาต</option>
      <option>รีวิวสร้างความเข้าใจผิดว่าสถานที่อนุญาตสัตว์เลี้ยง ทั้งที่จริงไม่อนุญาต</option>
      <option>ไม่มีหลักฐานหรือรูปประกอบเกี่ยวกับสัตว์เลี้ยงในสถานที่</option>
      <option>รีวิวมีข้อมูลส่วนตัว เช่น เบอร์โทร อีเมล หรือข้อมูลผู้อื่น</option>
      <option>มีการโปรโมทธุรกิจตัวเองเกินสมควร</option>
      <option>มีลิงก์ภายนอกที่ไม่ปลอดภัย</option>
      <option>พยายามปั่นคะแนนหรือสร้างรีวิวปลอม</option>
      <option>ฝ่าฝืนนโยบายชุมชนของระบบ</option>
    </select>
    <div id="rejectMsg" style="display:none;padding:8px 12px;border-radius:8px;font-family:'Kanit',sans-serif;font-size:13px;margin-bottom:12px;"></div>
    <div style="display:flex;gap:10px;">
      <button onclick="closeRejectModal()" style="flex:1;padding:11px;background:#f1f5f9;color:#374151;border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;">ยกเลิก</button>
      <button onclick="confirmReject()" style="flex:1;padding:11px;background:#ef4444;color:#fff;border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">ยืนยันปฏิเสธ</button>
    </div>
  </div>
</div>

<!--  PLACE DETAIL PANEL  -->
<div class="overlay" id="placeOverlay" onclick="closePlaceDetail()" style="z-index:201"></div>
<div class="place-detail-panel" id="placeDetailPanel">
  <div class="pdp-header">
    <div class="pdp-header-top">
      <div>
        <div class="pdp-title" id="pdpTitle">—</div>
        <div class="pdp-sub" id="pdpSub">—</div>
      </div>
      <button class="pdp-close" onclick="closePlaceDetail()"></button>
    </div>
  </div>
  <div class="pdp-gallery" id="pdpGallery">
    <div class="pdp-img-main"><div class="pdp-no-img">ไม่มีรูปภาพ</div></div>
  </div>
  <div class="pdp-body" id="pdpBody"></div>
  <div class="pdp-status-row">
    <span class="pdp-status-label">สถานะ :</span>
    <select class="pdp-status-sel" id="pdpStatusSel">
      <option value="pending">รอยืนยัน</option>
      <option value="approved">อนุมัติ</option>
      <option value="rejected">ปฏิเสธ</option>
    </select>
    <button class="pdp-save-btn" onclick="savePlaceStatusFromPanel()">บันทึก</button>
  </div>
</div>

<!--  DETAIL PANEL (operator)  -->
<div class="overlay" id="overlay" onclick="closeDetail()"></div>
<div class="detail-panel" id="detailPanel">
  <div class="dp-header">
    <div>
      <div class="dp-title" id="dpTitle">—</div>
      <div class="dp-sub"  id="dpSub">—</div>
    </div>
    <button class="dp-close" onclick="closeDetail()"></button>
  </div>
  <div class="dp-body" id="dpBody"></div>
</div>

<!--  PHP data for JS  -->
<script>
const OPERATORS = <?= json_encode(array_values($operators), JSON_UNESCAPED_UNICODE) ?>;
const REASONS   = <?= json_encode($REJECTION_REASONS, JSON_UNESCAPED_UNICODE) ?>;
const PLACES_DATA = <?= json_encode(array_values($approvedPlaces ?? []), JSON_UNESCAPED_UNICODE) ?>;

//  Place Detail Panel 
let currentPlaceId = null;

function openPlaceDetail(pid) {
  const pl = PLACES_DATA.find(p => p.place_id == pid);
  if (!pl) return;
  currentPlaceId = pid;

  // Header
  document.getElementById('pdpTitle').textContent = pl.place_name || '—';
  const cat  = pl.category || '';
  const prov = pl.province || '';
  document.getElementById('pdpSub').textContent = [cat, prov].filter(Boolean).join(' • ');

  // Gallery
  const allImgs = pl.all_images ? pl.all_images.split(',').map(s => s.trim()).filter(Boolean) : [];
  const mainImg = pl.place_image || allImgs[0] || '';
  let galleryHtml = '<div class="pdp-img-main">';
  if (mainImg) {
    galleryHtml += `<img src="${esc(mainImg)}" alt="place image" onerror="this.parentElement.innerHTML='<div class=pdp-no-img>ไม่พบรูปภาพ</div>'">`;
  } else {
    galleryHtml += '<div class="pdp-no-img">ไม่มีรูปภาพ</div>';
  }
  galleryHtml += '</div>';
  // Extra thumbs
  const extras = allImgs.slice(1, 3);
  extras.forEach(img => {
    galleryHtml += `<div class="pdp-img-thumb"><img src="${esc(img)}" onerror="this.style.display='none'"></div>`;
  });
  document.getElementById('pdpGallery').innerHTML = galleryHtml;

  // Body
  const lat = pl.latitude  || '—';
  const lng = pl.longitude || '—';
  const mapUrl = (lat !== '—' && parseFloat(lat) !== 0)
    ? `https://www.google.com/maps?q=${lat},${lng}`
    : '#';
  const petAllowed = pl.pet_allowed === 'yes' || pl.pet_allowed === 'ใช่'
    ? '<span style="color:var(--green)"> รับสัตว์เลี้ยง</span>'
    : '<span style="color:var(--red)"> ไม่รับ</span>';

  // Amenities tags
  const amenList = (pl.amenities || '').split(',').map(a=>a.trim()).filter(Boolean);
  const petAmenList = (pl.pet_amenities || '').split(',').map(a=>a.trim()).filter(Boolean);
  const amenHtml = amenList.length ? amenList.map(a=>`<span class="pdp-tag">${esc(a)}</span>`).join('') : '<span style="color:var(--muted);font-size:13px">ไม่ระบุ</span>';
  const petAmenHtml = petAmenList.length ? petAmenList.map(a=>`<span class="pdp-tag">${esc(a)}</span>`).join('') : '<span style="color:var(--muted);font-size:13px">ไม่ระบุ</span>';

  // License
  const licHtml = pl.license_file
    ? `<img class="pdp-lic-img" src="${esc(pl.license_file)}" alt="ใบประกอบการ" onerror="this.style.display='none'" style="cursor:pointer;" onclick="window.open('${esc(pl.license_file)}','_blank')">`
    : '<span style="color:var(--muted);font-size:13px">ไม่มีไฟล์</span>';

  // Category verification documents
  let catDocsHtml = '<span style="color:var(--muted);font-size:13px">ไม่มีเอกสาร</span>';
  if (pl.category_docs) {
    try {
      const docs = JSON.parse(pl.category_docs);
      if (docs && docs.length > 0) {
        catDocsHtml = '<div style="display:flex;flex-wrap:wrap;gap:8px;">' + docs.map(src => {
          const isPdf = src.toLowerCase().endsWith('.pdf');
          return isPdf
            ? `<a href="${esc(src)}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1e40af;font-size:13px;text-decoration:none;"> ดูไฟล์ PDF</a>`
            : `<img src="${esc(src)}" onclick="window.open('${esc(src)}','_blank')" style="width:90px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;" onerror="this.style.display='none'">`;
        }).join('') + '</div>';
      }
    } catch(e) { catDocsHtml = '<span style="color:var(--muted);font-size:13px">ไม่มีเอกสาร</span>'; }
  }

  document.getElementById('pdpBody').innerHTML = `
    <div class="pdp-section">
      <div class="pdp-sec-title">ข้อมูลทั่วไป</div>
      <div class="pdp-row"><span class="pdp-label">เบอร์โทร</span><span class="pdp-val">${esc(pl.phone||'—')}</span></div>
      <div class="pdp-row"><span class="pdp-label">เวลาเปิด–ปิด</span><span class="pdp-val">${esc((pl.open_time||'—') + ' — ' + (pl.close_time||'—'))}</span></div>
      <div class="pdp-row"><span class="pdp-label">คำอธิบาย</span><span class="pdp-val" style="line-height:1.6">${esc(pl.description||'—')}</span></div>
    </div>
    <div class="pdp-section">
      <div class="pdp-sec-title">ที่ตั้ง</div>
      <div class="pdp-row"><span class="pdp-label">ที่อยู่</span><span class="pdp-val">${esc(pl.address||'—')}</span></div>
      <div class="pdp-row"><span class="pdp-label">จังหวัด</span><span class="pdp-val">${esc(pl.province||'—')}</span></div>
      <div class="pdp-row"><span class="pdp-label">พิกัด</span><span class="pdp-val">Lat: ${lat}, Lng: ${lng}</span></div>
      <a class="map-box" href="${mapUrl}" target="_blank" rel="noopener" style="margin-top:10px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        ดูตำแหน่งบน Google Maps
      </a>
    </div>
    <div class="pdp-section">
      <div class="pdp-sec-title">นโยบายสัตว์เลี้ยง</div>
      <div class="pdp-row"><span class="pdp-label">รับสัตว์เลี้ยง</span><span class="pdp-val">${petAllowed}</span></div>
      <div class="pdp-row"><span class="pdp-label">ประเภทสัตว์</span><span class="pdp-val">${esc(pl.pet_type_allowed||'—')}</span></div>
      <div class="pdp-row"><span class="pdp-label">ขนาดที่รับ</span><span class="pdp-val">${esc(pl.pet_size_allowed||'—')}</span></div>
      <div class="pdp-row"><span class="pdp-label">ค่าใช้จ่ายเพิ่ม</span><span class="pdp-val">${esc(pl.extra_cost||'ไม่มี')}</span></div>
      ${pl.pet_rules ? `<div class="pdp-row"><span class="pdp-label">กฎสัตว์เลี้ยง</span><span class="pdp-val">${esc(pl.pet_rules)}</span></div>` : ''}
    </div>
    <div class="pdp-section">
      <div class="pdp-sec-title">สิ่งอำนวยความสะดวก</div>
      <div style="margin-bottom:10px">
        <div style="font-size:12px;color:var(--muted);margin-bottom:6px">ทั่วไป</div>
        <div class="pdp-tags">${amenHtml}</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:6px">สำหรับสัตว์เลี้ยง</div>
        <div class="pdp-tags">${petAmenHtml}</div>
      </div>
    </div>
    <div class="pdp-section">
      <div class="pdp-sec-title">ใบยืนยันการเป็นผู้ประกอบการ</div>
      ${licHtml}
    </div>
    <div class="pdp-section">
      <div class="pdp-sec-title" style="margin-bottom:12px;">เอกสารยืนยันประเภทสถานที่</div>
      ${catDocsHtml}
    </div>
    ${pl.rejection_reason ? `
    <div class="pdp-section">
      <div class="pdp-sec-title">เหตุผลที่ปฏิเสธ</div>
      <div style="background:#fdecea;color:#b02a2a;padding:10px 14px;border-radius:8px;font-size:13px">${esc(pl.rejection_reason)}</div>
    </div>` : ''}
  `;

  // Set status selector
  const pstat = pl.status || 'pending';
  document.getElementById('pdpStatusSel').value = pstat;

  // Open panel
  document.getElementById('placeDetailPanel').classList.add('open');
  document.getElementById('placeOverlay').classList.add('show');
}

function closePlaceDetail() {
  document.getElementById('placeDetailPanel').classList.remove('open');
  document.getElementById('placeOverlay').classList.remove('show');
  currentPlaceId = null;
}

function savePlaceStatusFromPanel() {
  if (!currentPlaceId) return;
  const val = document.getElementById('pdpStatusSel').value;
  let reason = '';
  if (val === 'rejected') {
    reason = prompt('กรุณาระบุเหตุผลที่ปฏิเสธ:') || '';
  }
  _doUpdatePlace(currentPlaceId, val, reason, function(ok) {
    if (ok) {
      // Update badge in table row
      const row = document.querySelector(`tr[data-pid="${currentPlaceId}"]`);
      if (row) {
        const badge = row.querySelector('.badge');
        if (badge) {
          badge.className = 'badge badge-' + val;
          badge.textContent = {pending:'รอยืนยัน', approved:'อนุมัติ', rejected:'ปฏิเสธ'}[val] || val;
        }
        // Update table select
        const sel = row.querySelector('.status-sel');
        if (sel) sel.value = val;
      }
      // Update PLACES_DATA local cache
      const pl = PLACES_DATA.find(p => p.place_id == currentPlaceId);
      if (pl) { pl.status = val; pl.rejection_reason = reason; }
      closePlaceDetail();
    }
  });
}

//  Update place status 
function updatePlaceStatus(id, val) {
  let reason = '';
  if (val === 'rejected') {
    reason = prompt('กรุณาระบุเหตุผลที่ปฏิเสธ:') || '';
  }
  _doUpdatePlace(id, val, reason);
}

function _doUpdatePlace(id, status, reason, callback) {
  fetch('admin_dashboard.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=update_place_status&place_id=${id}&status=${encodeURIComponent(status)}&reason=${encodeURIComponent(reason)}`
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) {
      const row = document.querySelector(`tr[data-pid="${id}"]`);
      if (row) {
        const badge = row.querySelector('.badge');
        if (badge) {
          badge.className = 'badge badge-' + status;
          badge.textContent = {pending:'รอยืนยัน', approved:'อนุมัติ', rejected:'ปฏิเสธ'}[status] || status;
        }
      }
      if (typeof callback === 'function') callback(true);
    } else {
      alert('เกิดข้อผิดพลาด: ' + (d.msg || 'ไม่ทราบสาเหตุ'));
      if (typeof callback === 'function') callback(false);
    }
  })
  .catch(() => {
    alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
    if (typeof callback === 'function') callback(false);
  });
}

//  Filter places table 
function filterPlaces() {
  const q = document.getElementById('plSearch').value.toLowerCase();
  document.querySelectorAll('#plTbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

//  Chart 
const _chartLabels   = <?= $chartLabelsJson ?>;
const _chartData     = <?= $chartDataJson ?>;
const _categoryData  = <?= $categoryDataJson ?? '{}' ?>;
const _filterMode  = <?= $filterModeJson ?>;
const _filterMonth = <?= $filterMonthJson ?>;
const _filterYear  = <?= $filterYearJson ?>;
const _yearStartCE = <?= (int)$yearStartCE ?>;

const _xTitle = _filterMode === 'month' && _filterMonth > 0 && _filterYear > 0
  ? 'วันที่'
  : (_filterYear > 0 ? 'เดือน' : 'เดือน (6 เดือนล่าสุด)');

const _hasData = _chartData.length > 0 && _chartData.some(v => v > 0);

if (!_hasData) {
  const canvas = document.getElementById('userChart');
  const wrap = canvas.parentElement;
  canvas.style.display = 'none';
  wrap.innerHTML += `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:200px;color:#9aa8b7;gap:8px"><svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 16l4-4 4 4 4-6"/></svg><div style="font-size:13px">ไม่มีสถานที่ที่เพิ่มในช่วงเวลานี้</div></div>`;
} else {
  new Chart(document.getElementById('userChart'), {
    type: 'line',
    data: {
      labels: _chartLabels,
      datasets: [{
        label: 'สถานที่เพิ่มใหม่',
        data: _chartData,
        borderColor: '#123451',
        backgroundColor: 'rgba(18,52,81,0.08)',
        borderWidth: 2.5,
        pointBackgroundColor: '#123451',
        pointRadius: 5,
        tension: 0.35,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ' ' + ctx.parsed.y + ' แห่ง'
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#6b8099', font: { size: 12 } },
          title: { display: true, text: _xTitle, color: '#6b8099', font: { size: 11 } }
        },
        y: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: { color: '#6b8099', font: { size: 12 }, precision: 0, stepSize: 1 },
          beginAtZero: true
        }
      }
    }
  });
}

// ── Filter mode toggle ──────────────────────────────────────
function setFilterMode(mode) {
  document.getElementById('filterModeInput').value = mode;
  const monthSel = document.getElementById('filterMonthSel');
  const mBtn = document.getElementById('modeMonthBtn');
  const yBtn = document.getElementById('modeYearBtn');
  if (mode === 'year') {
    monthSel.style.display = 'none';
    mBtn.style.background = 'var(--card)'; mBtn.style.color = 'var(--text)';
    yBtn.style.background = 'var(--navy)'; yBtn.style.color = '#fff';
  } else {
    monthSel.style.display = '';
    mBtn.style.background = 'var(--navy)'; mBtn.style.color = '#fff';
    yBtn.style.background = 'var(--card)'; yBtn.style.color = 'var(--text)';
  }
}

//  Page navigation 
function showPage(p) {
  document.querySelectorAll('.page').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  document.getElementById('page-' + p).classList.add('active');
  document.getElementById('nav-' + p).classList.add('active');
  if (p === 'reviews') filterReviews('pending');
}

// Auto-open page from URL ?page=xxx
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const pg = params.get('page');
  if (pg && document.getElementById('page-' + pg)) {
    showPage(pg);
  }
});

//  Update status via AJAX 
function updateStatus(id, val) {
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=update_status&entre_id=${id}&status=${encodeURIComponent(val)}&reason=`
  }).then(r => r.json()).then(d => {
    if (!d.ok) { alert('เกิดข้อผิดพลาด: ' + d.msg); return; }
    // toggle reason cell
    const cell = document.querySelector(`.reason-cell-${id}`);
    if (cell) {
      if (val === 'rejected') {
        let opts = REASONS.map(r => `<option value="${r}">${r}</option>`).join('');
        cell.innerHTML = `<select class="reason-sel" onchange="updateReason(${id},this.value)"><option value="">เลือกเหตุผล</option>${opts}</select>`;
      } else {
        cell.innerHTML = '<span style="color:var(--muted);font-size:12px">—</span>';
      }
    }
  });
}

function updateReason(id, reason) {
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=update_status&entre_id=${id}&status=rejected&reason=${encodeURIComponent(reason)}`
  });
}

//  Filter tables 
function filterOps() {
  const q = document.getElementById('opSearch').value.toLowerCase();
  const s = document.getElementById('opStatusFilter').value;
  document.querySelectorAll('#opTbody tr').forEach(tr => {
    const nm = tr.cells[0]?.textContent.toLowerCase() || '';
    const ow = tr.cells[1]?.textContent.toLowerCase() || '';
    const st = tr.dataset.status || '';
    tr.style.display = ((!q || nm.includes(q) || ow.includes(q)) && (!s || st === s)) ? '' : 'none';
  });
}

function filterApproved() {
  const q = document.getElementById('apSearch').value.toLowerCase();
  document.querySelectorAll('#apTbody tr').forEach(tr => {
    const nm = tr.cells[0]?.textContent.toLowerCase() || '';
    tr.style.display = (!q || nm.includes(q)) ? '' : 'none';
  });
}

//  Detail panel 
function openDetail(id) {
  const op = OPERATORS.find(o => o.entre_id == id);
  if (!op) return;

  const name  = op.business_name  || '—';
  const type  = op.business_type  || '—';
  const prov  = op.bussiness_province || '—';
  const owner = (op.entre_firstname||'') + ' ' + (op.entre_lastname||'');
  const email = op.entre_email    || '—';
  const addr  = op.business_address || '—';
  const desc  = op.business_details || '—';
  const lat   = op.latitude  || '—';
  const lng   = op.longitude || '—';
  const pets  = op.pet_allowed || '—';
  const size  = op.pet_size_allowed || '—';
  const wt    = op.pet_weight_allowed || '';
  const mapUrl = (lat !== '—' && parseFloat(lat) !== 0)
    ? `https://www.google.com/maps?q=${lat},${lng}`
    : '#';
  const status = op.approval_status || 'pending';
  const stLabel = { pending:'รอยืนยัน', approved:'อนุมัติแล้ว', rejected:'ถูกปฏิเสธ' }[status] || status;
  const stClass = { pending:'badge-pending', approved:'badge-approved', rejected:'badge-rejected' }[status] || 'badge-pending';

  // Registration documents
  let regDocsHtml = '<span style="color:var(--muted);font-size:13px;">ไม่มีเอกสาร</span>';
  if (op.reg_docs) {
    try {
      const docs = JSON.parse(op.reg_docs);
      if (docs && docs.length > 0) {
        regDocsHtml = '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">' + docs.map(src => {
          const isPdf = src.toLowerCase().endsWith('.pdf');
          return isPdf
            ? `<a href="${esc(src)}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1e40af;font-size:13px;text-decoration:none;"> ดูไฟล์ PDF</a>`
            : `<img src="${esc(src)}" onclick="window.open('${esc(src)}','_blank')" style="width:90px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;" onerror="this.style.display='none'">`;
        }).join('') + '</div>';
      }
    } catch(e) {}
  }

  document.getElementById('dpTitle').textContent = name;
  document.getElementById('dpSub').textContent   = type + ' • ' + prov;

  document.getElementById('dpBody').innerHTML = `
    <div class="dp-section">
      <div class="dp-sec-title">ข้อมูลเจ้าของ</div>
      <div class="dp-row"><span class="dp-label">ชื่อเจ้าของ</span><span class="dp-val">${esc(owner)}</span></div>
      <div class="dp-row"><span class="dp-label">อีเมล</span><span class="dp-val" style="color:var(--navy)">${esc(email)}</span></div>
    </div>
    <div class="dp-section">
      <div class="dp-sec-title">ข้อมูลสถานที่</div>
      <div class="dp-row"><span class="dp-label">ที่อยู่</span><span class="dp-val">${esc(addr)}</span></div>
      <div class="dp-row"><span class="dp-label">จังหวัด</span><span class="dp-val">${esc(prov)}</span></div>
      <div class="dp-row"><span class="dp-label">รายละเอียด</span><span class="dp-val">${esc(desc)}</span></div>
      <div class="dp-row"><span class="dp-label">พิกัด</span><span class="dp-val">Lat: ${lat}, Lng: ${lng}</span></div>
      <a class="map-box" href="${mapUrl}" target="_blank" rel="noopener">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        ดูตำแหน่งบน Google Maps
      </a>
    </div>
    <div class="dp-section">
      <div class="dp-sec-title">นโยบายสัตว์เลี้ยง</div>
      <div class="dp-row"><span class="dp-label">รับสัตว์เลี้ยง</span><span class="dp-val">${pets==='yes'||pets==='ใช่'?'ใช่':pets==='no'||pets==='ไม่ใช่'?'ไม่ใช่':esc(pets)}</span></div>
      ${size && size!=='—' ? `<div class="dp-row"><span class="dp-label">ขนาดที่รับ</span><span class="dp-val">${esc(size)}</span></div>` : ''}
      ${wt ? `<div class="dp-row"><span class="dp-label">น้ำหนักสูงสุด</span><span class="dp-val">${wt} กก.</span></div>` : ''}
    </div>
    <div class="dp-section">
      <div class="dp-sec-title">สถานะ</div>
      <span class="badge ${stClass}">${stLabel}</span>
      ${status==='rejected' && op.rejection_reason
        ? `<span style="margin-left:8px;font-size:12px;color:#b02a2a;background:#fdecea;padding:3px 10px;border-radius:20px">${esc(op.rejection_reason)}</span>`
        : ''}
    </div>
    <div class="dp-section">
      <div class="dp-sec-title">เอกสารยืนยันการลงทะเบียน</div>
      ${regDocsHtml}
    </div>
  `;
  document.getElementById('detailPanel').classList.add('open');
  document.getElementById('overlay').classList.add('show');
}

function closeDetail() {
  document.getElementById('detailPanel').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

//  Review Management 
function filterReviews(status) {
  document.querySelectorAll('.review-row').forEach(row => {
    row.style.display = row.dataset.status === status ? '' : 'none';
  });
  ['pending','approved','rejected'].forEach(s => {
    const btn = document.getElementById('rv-tab-' + s);
    if (!btn) return;
    btn.style.fontWeight = s === status ? '700' : '400';
    btn.style.opacity    = s === status ? '1' : '0.7';
  });
}

// ไม่เรียก filterReviews ตอน DOMContentLoaded อีกต่อไป
// จะเรียกตอน showPage('reviews') แทน

function openRejectModal(reviewId) {
  document.getElementById('rejectReviewId').value = reviewId;
  document.getElementById('rejectReason').value = '';
  document.getElementById('rejectMsg').style.display = 'none';
  const overlay = document.getElementById('rejectOverlay');
  overlay.style.display = 'flex';
}

function closeRejectModal() {
  document.getElementById('rejectOverlay').style.display = 'none';
}

async function confirmReject() {
  const reviewId = document.getElementById('rejectReviewId').value;
  const reason   = document.getElementById('rejectReason').value;
  if (!reason) {
    showRejectMsg('กรุณาเลือกเหตุผล', 'error');
    return;
  }
  await reviewAction(reviewId, 'reject', reason);
  closeRejectModal();
}

async function reviewAction(reviewId, action, reason = '') {
  if (action === 'reject_prompt') { openRejectModal(reviewId); return; }
  const fd = new FormData();
  fd.append('review_id', reviewId);
  fd.append('action', action);
  fd.append('reason', reason);
  try {
    const res  = await fetch('review_action.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      window.location.href = window.location.pathname + '?page=reviews';
    } else {
      alert(data.message || 'เกิดข้อผิดพลาด');
    }
  } catch(e) { alert('เกิดข้อผิดพลาด'); }
}

function showRejectMsg(msg, type) {
  const el = document.getElementById('rejectMsg');
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
  el.style.color      = type === 'success' ? '#065f46' : '#991b1b';
}

//  Images Page 
let imgSelectedFiles = [];
let imgCurrentPid    = null;

function selectImgPlace(pid) {
  imgCurrentPid = pid;
  imgSelectedFiles = [];

  // highlight selected
  document.querySelectorAll('.img-place-row').forEach(el => {
    el.style.background = el.dataset.pid == pid ? '#e8f0ff' : '';
  });

  // AJAX fetch place data
  fetch(`admin_dashboard.php?img_ajax=1&pid=${pid}`)
    .then(r => r.json())
    .then(data => renderImgPanel(data))
    .catch(() => showImgAlert('ไม่สามารถโหลดข้อมูลได้', 'error'));
}

function renderImgPanel(pl) {
  const allImgs = pl.all_images
    ? pl.all_images.split(',').map(s => s.trim()).filter(Boolean)
    : (pl.place_image ? [pl.place_image] : []);
  const mainImg = pl.place_image || '';

  let galleryHtml = '';
  if (allImgs.length > 0) {
    galleryHtml = `
      <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:10px">
        รูปภาพที่มีอยู่ (${allImgs.length} รูป)
        <span style="font-size:12px;color:var(--muted);font-weight:400;margin-left:6px">กดปุ่มเพื่อจัดการ</span>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-bottom:20px">
        ${allImgs.map(img => `
          <div style="position:relative;border-radius:10px;overflow:hidden;aspect-ratio:4/3;background:#dde8f5;border:2px solid ${img===mainImg?'var(--navy)':'transparent'}">
            <img src="${img}" style="width:100%;height:100%;object-fit:cover;display:block" onerror="this.parentElement.style.background='#f1f5f9'">
            ${img===mainImg ? '<div style="position:absolute;top:6px;left:6px;background:var(--navy);color:#fff;font-size:10px;padding:2px 8px;border-radius:20px">รูปหลัก</div>' : ''}
            <div class="img-gallery-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0);display:flex;align-items:flex-end;padding:7px;gap:5px;transition:background .15s"
                 onmouseenter="this.style.background='rgba(0,0,0,.45)';this.querySelectorAll('button').forEach(b=>b.style.opacity='1')"
                 onmouseleave="this.style.background='';this.querySelectorAll('button').forEach(b=>b.style.opacity='0')">
              ${img!==mainImg ? `<button onclick="setMainImg(${pl.place_id},'${escHtml(img)}')" style="opacity:0;padding:4px 9px;border-radius:6px;border:none;font-size:11px;font-family:'Kanit',sans-serif;cursor:pointer;background:#fff;color:var(--navy);transition:opacity .15s">ตั้งหลัก</button>` : ''}
              <button onclick="deleteImg(${pl.place_id},'${escHtml(img)}')" style="opacity:0;padding:4px 9px;border-radius:6px;border:none;font-size:11px;font-family:'Kanit',sans-serif;cursor:pointer;background:#ef4444;color:#fff;transition:opacity .15s">ลบ</button>
            </div>
          </div>
        `).join('')}
      </div>
      <hr style="border:none;border-top:1px solid var(--border);margin-bottom:18px">
    `;
  }

  document.getElementById('imgUploadPanel').innerHTML = `
    <div style="background:var(--card);border-radius:16px;padding:24px">
      <div style="font-size:16px;font-weight:600;color:var(--navy);margin-bottom:2px">${escHtml(pl.place_name)}</div>
      <div style="font-size:13px;color:var(--muted);margin-bottom:20px">${escHtml(pl.category)} &bull; ${escHtml(pl.province)}</div>
      ${galleryHtml}
      <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:10px">เพิ่มรูปภาพใหม่</div>
      <div id="imgDropzone"
           onclick="document.getElementById('imgFileInput').click()"
           ondragover="event.preventDefault();this.style.borderColor='#4a7aad';this.style.background='#f0f6ff'"
           ondragleave="this.style.borderColor='';this.style.background=''"
           ondrop="event.preventDefault();this.style.borderColor='';this.style.background='';handleImgFiles(event.dataTransfer.files)"
           style="border:2px dashed var(--border);border-radius:12px;padding:32px 20px;text-align:center;cursor:pointer;background:#fafcff;transition:all .2s">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:10px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <div style="font-size:14px;font-weight:500;color:var(--navy);margin-bottom:4px">คลิกหรือลากรูปมาวางที่นี่</div>
        <div style="font-size:12px;color:var(--muted)">JPG, PNG, WEBP, AVIF &bull; สูงสุด 5MB ต่อรูป</div>
      </div>
      <input type="file" id="imgFileInput" multiple accept="image/*" style="display:none" onchange="handleImgFiles(this.files)">
      <div id="imgPreviewGrid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;margin:14px 0"></div>
      <div id="imgProgressBar" style="display:none;width:100%;height:5px;background:#e2e8f0;border-radius:99px;margin:10px 0;overflow:hidden">
        <div id="imgProgressFill" style="height:100%;background:var(--navy);border-radius:99px;width:0%;transition:width .3s"></div>
      </div>
      <div style="display:flex;gap:10px;margin-top:14px">
        <button id="imgUploadBtn" onclick="uploadImages(${pl.place_id})" disabled
                style="padding:9px 22px;background:var(--navy);color:#fff;border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;opacity:.5;transition:all .15s">
          อัปโหลด (<span id="imgFileCount">0</span> รูป)
        </button>
        <button onclick="clearImgFiles()" style="padding:9px 20px;background:#f1f5f9;color:var(--text);border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer">ล้าง</button>
      </div>
    </div>
  `;
}

function handleImgFiles(files) {
  const allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/avif','image/gif'];
  for (const file of files) {
    if (!allowed.includes(file.type)) { alert(file.name + ' ไม่ใช่รูปภาพที่รองรับ'); continue; }
    if (file.size > 5*1024*1024) { alert(file.name + ' ขนาดเกิน 5MB'); continue; }
    imgSelectedFiles.push(file);
  }
  renderImgPreviews();
}

function renderImgPreviews() {
  const grid = document.getElementById('imgPreviewGrid');
  const btn  = document.getElementById('imgUploadBtn');
  const cnt  = document.getElementById('imgFileCount');
  if (!grid) return;
  if (!imgSelectedFiles.length) {
    grid.style.display = 'none';
    btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed';
    cnt.textContent = '0'; return;
  }
  grid.style.display = 'grid';
  grid.innerHTML = '';
  btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer';
  cnt.textContent = imgSelectedFiles.length;
  imgSelectedFiles.forEach((file, i) => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.style.cssText = 'position:relative;border-radius:8px;overflow:hidden;aspect-ratio:4/3;background:#dde8f5';
      div.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
        <button onclick="removeImgFile(${i})" style="position:absolute;top:4px;right:4px;width:20px;height:20px;background:rgba(0,0,0,.55);border:none;border-radius:50%;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1">x</button>`;
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function removeImgFile(i) { imgSelectedFiles.splice(i,1); renderImgPreviews(); }
function clearImgFiles()  { imgSelectedFiles = []; document.getElementById('imgFileInput').value=''; renderImgPreviews(); }

async function uploadImages(pid) {
  if (!imgSelectedFiles.length) return;
  const bar  = document.getElementById('imgProgressBar');
  const fill = document.getElementById('imgProgressFill');
  bar.style.display = 'block';
  let w = 0; const timer = setInterval(() => { w = Math.min(w+12, 85); fill.style.width = w+'%'; }, 200);

  const fd = new FormData();
  fd.append('img_upload', '1');
  fd.append('place_id', pid);
  imgSelectedFiles.forEach(f => fd.append('images[]', f));

  try {
    const res  = await fetch('admin_dashboard.php', { method:'POST', body:fd });
    const data = await res.json();
    clearInterval(timer); fill.style.width = '100%';
    if (data.ok) {
      showImgAlert(data.msg, 'success');
      imgSelectedFiles = [];
      // refresh panel
      fetch(`admin_dashboard.php?img_ajax=1&pid=${pid}`).then(r=>r.json()).then(renderImgPanel);
      // update thumb in list
      if (data.main_image) {
        const row = document.querySelector(`.img-place-row[data-pid="${pid}"] img`);
        if (row) row.src = data.main_image;
      }
    } else {
      showImgAlert(data.msg || 'เกิดข้อผิดพลาด', 'error');
    }
  } catch(e) {
    clearInterval(timer);
    showImgAlert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
  }
  setTimeout(() => { bar.style.display='none'; fill.style.width='0%'; }, 1000);
}

async function setMainImg(pid, img) {
  const fd = new FormData();
  fd.append('img_set_main','1'); fd.append('place_id',pid); fd.append('set_main',img);
  const res  = await fetch('admin_dashboard.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.ok) {
    showImgAlert('ตั้งรูปหลักแล้ว','success');
    fetch(`admin_dashboard.php?img_ajax=1&pid=${pid}`).then(r=>r.json()).then(renderImgPanel);
  }
}

async function deletePlace(pid, name) {
  if (!confirm('ต้องการลบ "' + name + '" ออกจากระบบ? ไม่สามารถกู้คืนได้')) return;
  const fd = new FormData();
  fd.append('delete_place', '1');
  fd.append('place_id', pid);
  try {
    const res  = await fetch('admin_dashboard.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      const row = document.querySelector(`#epTbody tr[data-pid="${pid}"]`);
      if (row) row.remove();
      if (window.currentPlaceId == pid) {
        document.getElementById('epPanel').style.display = 'none';
        window.currentPlaceId = null;
      }
      alert('ลบสถานที่เรียบร้อยแล้ว');
    } else {
      alert('เกิดข้อผิดพลาด: ' + (data.msg || 'unknown'));
    }
  } catch(e) {
    alert('เกิดข้อผิดพลาด');
  }
}

async function deleteImg(pid, img) {
  if (!confirm('ลบรูปนี้?')) return;
  const fd = new FormData();
  fd.append('img_delete','1'); fd.append('place_id',pid); fd.append('delete_image',img);
  const res  = await fetch('admin_dashboard.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.ok) {
    showImgAlert('ลบรูปแล้ว','success');
    fetch(`admin_dashboard.php?img_ajax=1&pid=${pid}`).then(r=>r.json()).then(renderImgPanel);
  }
}

function showImgAlert(msg, type) {
  const el = document.getElementById('imgAlertBox');
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'flex';
  el.style.background = type==='success' ? '#d1fae5' : '#fee2e2';
  el.style.color      = type==='success' ? '#065f46' : '#991b1b';
  el.style.border     = '1px solid ' + (type==='success' ? '#a7f3d0' : '#fca5a5');
  setTimeout(() => el.style.display='none', 4000);
}

function filterImgPlaces() {
  const q = document.getElementById('imgSearch').value.toLowerCase();
  document.querySelectorAll('.img-place-row').forEach(el => {
    el.style.display = (!q || el.dataset.name.includes(q) || el.dataset.prov.includes(q)) ? '' : 'none';
  });
  document.querySelectorAll('.img-prov-label').forEach(label => {
    let next = label.nextElementSibling;
    let vis  = false;
    while (next && next.classList.contains('img-place-row')) {
      if (next.style.display !== 'none') vis = true;
      next = next.nextElementSibling;
    }
    label.style.display = vis ? '' : 'none';
  });
}

function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }


// ── Add Place Page ─────────────────────────────
let apSelectedFiles = [];

function handleApFiles(files) {
  const allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/avif','image/gif'];
  for (const file of files) {
    if (!allowed.includes(file.type)) continue;
    if (file.size > 5*1024*1024) { alert(file.name+' ขนาดเกิน 5MB'); continue; }
    apSelectedFiles.push(file);
  }
  renderApPreviews();
}

function renderApPreviews() {
  const grid = document.getElementById('apPreviewGrid');
  if (!grid) return;
  if (!apSelectedFiles.length) { grid.style.display='none'; return; }
  grid.style.display = 'grid';
  grid.innerHTML = '';
  apSelectedFiles.forEach((file, i) => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.style.cssText = 'position:relative;border-radius:8px;overflow:hidden;aspect-ratio:4/3;background:#dde8f5';
      div.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
        <button onclick="apSelectedFiles.splice(${i},1);renderApPreviews()" style="position:absolute;top:3px;right:3px;width:19px;height:19px;background:rgba(0,0,0,.6);border:none;border-radius:50%;color:#fff;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1">x</button>
        ${i===0 ? '<div style="position:absolute;bottom:4px;left:4px;background:var(--navy);color:#fff;font-size:9px;padding:1px 6px;border-radius:10px">รูปหลัก</div>' : ''}`;
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

async function submitAddPlace() {
  const name = document.getElementById('ap_place_name').value.trim();
  const catArr = [...document.querySelectorAll('.ap-cat-cb:checked')].map(el=>el.value);
  const cat  = catArr.join(',');
  const prov = document.getElementById('ap_province').value;
  if (!name || !cat || !prov) { showApAlert('กรุณาเลือกประเภทอย่างน้อย 1 ประเภท','error'); return; }

  const petTypes = [...document.querySelectorAll('.ap-pet-type:checked')].map(el=>el.value).join(', ');
  const amenities = [...document.querySelectorAll('.ap-amenity:checked')].map(el=>el.value).join(',');
  const petAmen   = [...document.querySelectorAll('.ap-pet-amenity:checked')].map(el=>el.value).join(',');

  const fd = new FormData();
  fd.append('add_place','1');
  fd.append('place_name',  name);
  fd.append('category',    cat);
  fd.append('province',    prov);
  fd.append('address',     document.getElementById('ap_address').value.trim());
  fd.append('description', document.getElementById('ap_description').value.trim());
  fd.append('phone',       document.getElementById('ap_phone').value.trim());
  fd.append('latitude',    document.getElementById('ap_latitude').value  || '0');
  fd.append('longitude',   document.getElementById('ap_longitude').value || '0');
  fd.append('open_time',   document.getElementById('ap_open_time').value);
  fd.append('close_time',  document.getElementById('ap_close_time').value);
  fd.append('pet_type_allowed', petTypes);
  fd.append('pet_size_allowed', document.getElementById('ap_pet_size').value);
  fd.append('extra_cost',  document.getElementById('ap_extra_cost').value.trim());
  fd.append('pet_rules',   document.getElementById('ap_pet_rules').value.trim());
  fd.append('amenities',   amenities);
  fd.append('pet_amenities', petAmen);
  apSelectedFiles.forEach(f => fd.append('images[]', f));

  try {
    const res  = await fetch('admin_dashboard.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.ok) {
      showApAlert(data.msg + ' (place_id: ' + data.place_id + ')', 'success');
      resetAddPlace();
    } else {
      showApAlert(data.msg || 'เกิดข้อผิดพลาด', 'error');
    }
  } catch(e) { showApAlert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'); }
}

function resetAddPlace() {
  ['ap_place_name','ap_address','ap_description','ap_phone','ap_latitude','ap_longitude','ap_extra_cost','ap_pet_rules']
    .forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
  document.querySelectorAll('.ap-cat-cb').forEach(cb => cb.checked = false);
  const prov = document.getElementById('ap_province'); if(prov) prov.value='';
  const size = document.getElementById('ap_pet_size'); if(size) size.value='';
  const ot = document.getElementById('ap_open_time');  if(ot) ot.value='09:00';
  const ct = document.getElementById('ap_close_time'); if(ct) ct.value='21:00';
  document.querySelectorAll('.ap-pet-type,.ap-amenity,.ap-pet-amenity').forEach(el=>el.checked=false);
  apSelectedFiles = [];
  document.getElementById('apFileInput').value = '';
  renderApPreviews();
  window.scrollTo({top:0, behavior:'smooth'});
}

function showApAlert(msg, type) {
  const el = document.getElementById('apAlertBox');
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'flex';
  el.style.background = type==='success' ? '#d1fae5' : '#fee2e2';
  el.style.color      = type==='success' ? '#065f46' : '#991b1b';
  el.style.border     = '1px solid ' + (type==='success' ? '#a7f3d0' : '#fca5a5');
  if (type==='success') setTimeout(()=>el.style.display='none', 5000);
  else el.scrollIntoView({behavior:'smooth', block:'nearest'});
}


// ── Edit Place Page ────────────────────────────
let epSelectedFiles = [];

function filterEpPlaces() {
  const q    = document.getElementById('epSearch').value.toLowerCase();
  const cat  = document.getElementById('epCatFilter').value;
  const prov = document.getElementById('epProvFilter').value;
  document.querySelectorAll('.ep-row').forEach(row => {
    const nm = row.dataset.name || '';
    const rc = row.dataset.cat  || '';
    const rp = row.dataset.prov || '';
    const ok = (!q || nm.includes(q))
            && (!cat  || rc === cat)
            && (!prov || rp === prov);
    row.style.display = ok ? '' : 'none';
  });
}

async function openEditPanel(pid) {
  document.getElementById('ep_place_id').value = pid;
  document.getElementById('epPanelLoading').style.display = 'block';
  document.getElementById('epPanelBody').style.display    = 'none';
  document.getElementById('epPanelTitle').textContent = 'แก้ไขสถานที่';
  document.getElementById('epPanelSub').textContent   = 'กำลังโหลด...';
  document.getElementById('epPanel').style.transform  = 'translateX(0)';
  document.getElementById('epOverlay').classList.add('show');
  epSelectedFiles = [];
  renderEpPreviews();

  const fd = new FormData();
  fd.append('fetch_place','1'); fd.append('place_id', pid);
  try {
    const res  = await fetch('admin_dashboard.php', {method:'POST', body:fd});
    const pl   = await res.json();
    if (!pl || !pl.place_id) { alert('ไม่พบข้อมูลสถานที่'); closeEditPanel(); return; }
    populateEditForm(pl);
  } catch(e) { alert('โหลดข้อมูลไม่สำเร็จ'); closeEditPanel(); }
}

function populateEditForm(pl) {
  document.getElementById('epPanelTitle').textContent = pl.place_name || '-';
  document.getElementById('epPanelSub').textContent   = (pl.category||'') + ' • ' + (pl.province||'');

  const set = (id, val) => { const el=document.getElementById(id); if(el) el.value = val||''; };
  set('ep_place_id',   pl.place_id);
  set('ep_place_name', pl.place_name);
  set('ep_address',    pl.address);
  set('ep_description',pl.description);
  set('ep_phone',      pl.phone);
  set('ep_latitude',   pl.latitude != 0  ? pl.latitude  : '');
  set('ep_longitude',  pl.longitude != 0 ? pl.longitude : '');
  set('ep_open_time',  pl.open_time);
  set('ep_close_time', pl.close_time);
  set('ep_extra_cost', pl.extra_cost);
  set('ep_pet_rules',  pl.pet_rules);
  set('ep_status',     pl.status || 'approved');

  // Selects
  const epCats = (pl.category || '').split(',').map(s=>s.trim());
  document.querySelectorAll('.ep-cat-cb').forEach(cb => { cb.checked = epCats.includes(cb.value); });
  const provSel = document.getElementById('ep_province');
  if (provSel) { provSel.value = pl.province || ''; if (!provSel.value) { const o=document.createElement('option'); o.value=pl.province; o.textContent=pl.province; provSel.appendChild(o); provSel.value=pl.province; } }
  const sizeSel = document.getElementById('ep_pet_size');
  if (sizeSel) sizeSel.value = pl.pet_size_allowed || '';

  // Checkboxes - pet type
  const petTypes = (pl.pet_type_allowed || '').split(',').map(s=>s.trim());
  document.querySelectorAll('.ep-pet-type').forEach(cb => cb.checked = petTypes.includes(cb.value));

  // Checkboxes - amenities
  const amens = (pl.amenities || '').split(',').map(s=>s.trim());
  document.querySelectorAll('.ep-amenity').forEach(cb => cb.checked = amens.includes(cb.value));

  // Checkboxes - pet amenities
  const petAmens = (pl.pet_amenities || '').split(',').map(s=>s.trim());
  document.querySelectorAll('.ep-pet-amenity').forEach(cb => cb.checked = petAmens.includes(cb.value));

  document.getElementById('epPanelLoading').style.display = 'none';
  document.getElementById('epPanelBody').style.display    = 'block';
}

function closeEditPanel() {
  document.getElementById('epPanel').style.transform = 'translateX(100%)';
  document.getElementById('epOverlay').classList.remove('show');
}

function handleEpFiles(files) {
  const allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/avif','image/gif'];
  for (const file of files) {
    if (!allowed.includes(file.type)) continue;
    if (file.size > 5*1024*1024) { alert(file.name+' ขนาดเกิน 5MB'); continue; }
    epSelectedFiles.push(file);
  }
  renderEpPreviews();
}

function renderEpPreviews() {
  const grid = document.getElementById('epImgPreview');
  if (!grid) return;
  if (!epSelectedFiles.length) { grid.style.display='none'; return; }
  grid.style.display='grid'; grid.innerHTML='';
  epSelectedFiles.forEach((file, i) => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.style.cssText = 'position:relative;border-radius:8px;overflow:hidden;aspect-ratio:4/3;background:#dde8f5';
      div.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
        <button onclick="epSelectedFiles.splice(${i},1);renderEpPreviews()" style="position:absolute;top:3px;right:3px;width:18px;height:18px;background:rgba(0,0,0,.6);border:none;border-radius:50%;color:#fff;font-size:11px;cursor:pointer;line-height:1">x</button>`;
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

async function saveEditPlace() {
  const pid  = document.getElementById('ep_place_id').value;
  const name = document.getElementById('ep_place_name').value.trim();
  const catArr2 = [...document.querySelectorAll('.ep-cat-cb:checked')].map(el=>el.value);
  const cat  = catArr2.join(',');
  const prov = document.getElementById('ep_province').value;
  if (!name || !cat || !prov) { showEpAlert('กรุณากรอกชื่อสถานที่ เลือกประเภทอย่างน้อย 1 ประเภท และเลือกจังหวัด','error'); return; }

  const saveBtn = document.getElementById('epSaveBtn');
  const originalText = saveBtn ? saveBtn.textContent : '';
  if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'กำลังบันทึก...'; saveBtn.style.opacity = '.6'; }

  const petTypes  = [...document.querySelectorAll('.ep-pet-type:checked')].map(el=>el.value).join(', ');
  const amenities = [...document.querySelectorAll('.ep-amenity:checked')].map(el=>el.value).join(',');
  const petAmen   = [...document.querySelectorAll('.ep-pet-amenity:checked')].map(el=>el.value).join(',');

  const fd = new FormData();
  fd.append('update_place','1');
  fd.append('place_id',    pid);
  fd.append('place_name',  name);
  fd.append('category',    cat);
  fd.append('province',    prov);
  fd.append('address',     document.getElementById('ep_address').value.trim());
  fd.append('description', document.getElementById('ep_description').value.trim());
  fd.append('phone',       document.getElementById('ep_phone').value.trim());
  fd.append('latitude',    document.getElementById('ep_latitude').value  || '0');
  fd.append('longitude',   document.getElementById('ep_longitude').value || '0');
  fd.append('open_time',   document.getElementById('ep_open_time').value);
  fd.append('close_time',  document.getElementById('ep_close_time').value);
  fd.append('pet_type_allowed', petTypes);
  fd.append('pet_size_allowed', document.getElementById('ep_pet_size').value);
  fd.append('extra_cost',  document.getElementById('ep_extra_cost').value.trim());
  fd.append('pet_rules',   document.getElementById('ep_pet_rules').value.trim());
  fd.append('amenities',   amenities);
  fd.append('pet_amenities', petAmen);
  fd.append('status',      document.getElementById('ep_status').value);
  epSelectedFiles.forEach(f => fd.append('images[]', f));

  try {
    const res  = await fetch('admin_dashboard.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.ok) {
      showEpAlert(data.msg, 'success');
      closeEditPanel();
      // Update row in table
      const row = document.querySelector(`.ep-row[data-pid="${pid}"]`);
      if (row) {
        row.cells[1].innerHTML = `<strong style="color:var(--navy)">${name}</strong>`;
        row.cells[2].innerHTML = `<span class="type-badge">${cat}</span>`;
        row.cells[3].textContent = prov;
        row.dataset.name = name.toLowerCase();
        row.dataset.cat  = cat;
        row.dataset.prov = prov;
      }
    } else {
      showEpAlert(data.msg || 'เกิดข้อผิดพลาด', 'error');
    }
  } catch(e) { showEpAlert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: ' + e.message,'error'); }
  finally {
    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = originalText; saveBtn.style.opacity = '1'; }
  }
}

function showEpAlert(msg, type) {
  const el = document.getElementById('epAlertBox');
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'flex';
  el.style.background = type==='success' ? '#d1fae5' : '#fee2e2';
  el.style.color      = type==='success' ? '#065f46' : '#991b1b';
  el.style.border     = '1px solid ' + (type==='success' ? '#a7f3d0' : '#fca5a5');
  el.scrollIntoView({behavior:'smooth', block:'start'});
  if (type==='success') setTimeout(()=>el.style.display='none', 4000);
}

// ── NEWS MANAGEMENT ──────────────────────────────────────────────
function showNewsAlert(msg, type) {
  const box = document.getElementById('newsAlertBox');
  box.style.display = 'flex';
  box.style.background = type === 'ok' ? '#d1fae5' : '#fee2e2';
  box.style.color = type === 'ok' ? '#065f46' : '#991b1b';
  box.innerHTML = msg;
  setTimeout(() => { box.style.display = 'none'; }, 4000);
}

function openNewsForm(id) {
  document.getElementById('newsListView').style.display = 'none';
  document.getElementById('newsFormView').style.display = 'block';
  document.getElementById('newsAlertBox').style.display = 'none';
  // reset
  ['nw_id','nw_badge','nw_title','nw_description','nw_source','nw_highlights_title','nw_highlights'].forEach(f => {
    document.getElementById(f).value = '';
  });
  document.getElementById('nw_status').value = 'published';
  document.getElementById('nw_reverse').value = '0';
  document.getElementById('nw_image').value = '';
  document.getElementById('nw_img_current').textContent = '';
  document.getElementById('nw_img_preview').innerHTML = '<span style="color:var(--muted);font-size:13px">ยังไม่มีรูป</span>';

  if (!id) {
    document.getElementById('newsFormTitle').textContent = 'เพิ่มข่าวใหม่';
    return;
  }
  document.getElementById('newsFormTitle').textContent = 'แก้ไขข่าว';
  const fd = new FormData();
  fd.append('action', 'fetch_news');
  fd.append('news_id', id);
  fetch('', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
      if (!d || !d.id) return;
      document.getElementById('nw_id').value               = d.id;
      document.getElementById('nw_badge').value            = d.badge || '';
      document.getElementById('nw_title').value            = d.title || '';
      document.getElementById('nw_description').value      = d.description || '';
      document.getElementById('nw_source').value           = d.source || '';
      document.getElementById('nw_highlights_title').value = d.highlights_title || '';
      document.getElementById('nw_highlights').value       = d.highlights || '';
      document.getElementById('nw_status').value           = d.status || 'published';
      document.getElementById('nw_reverse').value          = d.reverse_layout || '0';
      if (d.image) {
        document.getElementById('nw_img_current').textContent = 'รูปปัจจุบัน: ' + d.image;
        document.getElementById('nw_img_preview').innerHTML =
          '<img src="' + d.image + '" style="width:100%;height:100%;object-fit:cover">';
      }
    });
}

function closeNewsForm() {
  document.getElementById('newsListView').style.display = 'block';
  document.getElementById('newsFormView').style.display = 'none';
}

function previewNewsImg(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('nw_img_preview').innerHTML =
      '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover">';
  };
  reader.readAsDataURL(file);
}

function saveNews() {
  const title = document.getElementById('nw_title').value.trim();
  const badge = document.getElementById('nw_badge').value.trim();
  if (!title || !badge) { showNewsAlert('กรุณากรอก Badge และ หัวข้อข่าว', 'err'); return; }

  const btn = document.getElementById('nwSaveBtn');
  btn.disabled = true; btn.textContent = 'กำลังบันทึก...';

  const id = document.getElementById('nw_id').value;
  const fd = new FormData();
  fd.append('action',            id ? 'update_news' : 'add_news');
  if (id) fd.append('news_id',  id);
  fd.append('badge',             badge);
  fd.append('title',             title);
  fd.append('description',       document.getElementById('nw_description').value.trim());
  fd.append('source',            document.getElementById('nw_source').value.trim());
  fd.append('highlights_title',  document.getElementById('nw_highlights_title').value.trim());
  fd.append('highlights',        document.getElementById('nw_highlights').value.trim());
  fd.append('status',            document.getElementById('nw_status').value);
  fd.append('reverse_layout',    document.getElementById('nw_reverse').value);
  const imgFile = document.getElementById('nw_image').files[0];
  if (imgFile) fd.append('news_image', imgFile);

  fetch('', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
      btn.disabled = false; btn.textContent = 'บันทึก';
      if (d.ok) {
        showNewsAlert('บันทึกข่าวเรียบร้อยแล้ว', 'ok');
        setTimeout(() => location.reload(), 1200);
      } else {
        showNewsAlert('เกิดข้อผิดพลาด: ' + (d.msg || ''), 'err');
      }
    });
}

function deleteNews(id, btn) {
  if (!confirm('ลบข่าวนี้?')) return;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('action', 'delete_news');
  fd.append('news_id', id);
  fetch('', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
      if (d.ok) {
        const row = document.getElementById('news-row-' + id);
        if (row) row.remove();
      } else {
        alert('เกิดข้อผิดพลาด: ' + (d.msg || ''));
        btn.disabled = false;
      }
    });
}


// ── NEWS CONFIG TAB ─────────────────────────────────
function switchNewsTab(tab) {
  const isList = tab === 'list';
  document.getElementById('newsTabList').style.display   = isList ? '' : 'none';
  document.getElementById('newsTabConfig').style.display = isList ? 'none' : '';
  const activeStyle   = 'background:none;border:none;padding:10px 20px;font-family:\'Kanit\',sans-serif;font-size:14px;cursor:pointer;font-weight:600;color:var(--navy);border-bottom:2px solid var(--navy);margin-bottom:-2px';
  const inactiveStyle = 'background:none;border:none;padding:10px 20px;font-family:\'Kanit\',sans-serif;font-size:14px;cursor:pointer;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-2px';
  document.getElementById('ntab-list').setAttribute('style',   isList ? activeStyle : inactiveStyle);
  document.getElementById('ntab-config').setAttribute('style', isList ? inactiveStyle : activeStyle);
}

function saveNewsConfig() {
  const fd = new FormData();
  fd.append('action', 'save_news_config');
  const keys = [
    'header_tag','header_title','header_desc',
    'stat1_number','stat1_label','stat2_number','stat2_label','stat3_number','stat3_label',
    'highlight_section_title','highlight_box_title','highlight_items'
  ];
  keys.forEach(k => fd.append(k, document.getElementById('cfg_' + k).value));
  fetch('', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
      const box = document.getElementById('cfgAlertBox');
      box.style.display = 'block';
      if (d.ok) {
        box.style.background = '#d1fae5'; box.style.color = '#065f46';
        box.textContent = 'บันทึกสําเร็จแล้ว';
      } else {
        box.style.background = '#fee2e2'; box.style.color = '#991b1b';
        box.textContent = 'เกิดข้อผิดพลาด: ' + (d.msg || '');
      }
      setTimeout(() => box.style.display = 'none', 4000);
    });
}


// ── CATEGORY BREAKDOWN CHART ──────────────────────
let _catChartInstance = null;

function renderCatChart() {
  const cat = document.getElementById('catChartSel').value;
  const empty = document.getElementById('catChartEmpty');
  const canvas = document.getElementById('catChart');
  const sub = document.getElementById('catChartSub');

  if (!cat || !_categoryData[cat] || _categoryData[cat].data.length === 0) {
    empty.style.display = 'flex';
    canvas.style.display = 'none';
    sub.textContent = 'เลือกประเภทเพื่อดูรายละเอียด';
    if (!cat) empty.querySelector('div').textContent = 'เลือกประเภทสถานที่เพื่อดูข้อมูล';
    else empty.querySelector('div').textContent = 'ไม่มีข้อมูล ' + cat + ' ในระบบ';
    return;
  }

  const d = _categoryData[cat];
  sub.textContent = cat + ' ทั้งหมด ' + d.total + ' แห่ง ใน ' + d.labels.length + ' จังหวัด';
  empty.style.display = 'none';
  canvas.style.display = 'block';

  if (_catChartInstance) { _catChartInstance.destroy(); _catChartInstance = null; }

  // Color palette
  const palette = [
    'rgba(18,52,81,0.85)','rgba(30,90,140,0.8)','rgba(56,132,187,0.75)',
    'rgba(90,165,210,0.7)','rgba(130,195,230,0.65)','rgba(18,52,81,0.55)',
    'rgba(56,132,187,0.55)','rgba(90,165,210,0.5)','rgba(130,195,230,0.45)',
    'rgba(18,52,81,0.4)'
  ];
  const colors = d.labels.map((_,i) => palette[i % palette.length]);

  _catChartInstance = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: d.labels,
      datasets: [{
        label: cat,
        data: d.data,
        backgroundColor: colors,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ' ' + ctx.parsed.y + ' แห่ง'
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#6b8099', font: { size: 11 }, maxRotation: 35 }
        },
        y: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: { color: '#6b8099', font: { size: 12 }, precision: 0, stepSize: 1 },
          beginAtZero: true,
          title: { display: true, text: 'จำนวนสถานที่', color: '#6b8099', font: { size: 11 } }
        }
      }
    }
  });
}

// ── EVENTS MANAGEMENT ────────────────────────────────────────────────────────

function showEvAlert(msg, type) {
  const box = document.getElementById('evAlertBox');
  box.style.display = 'flex';
  box.style.background = type === 'ok' ? '#d1fae5' : '#fee2e2';
  box.style.color      = type === 'ok' ? '#065f46' : '#991b1b';
  box.innerHTML = msg;
  setTimeout(() => { box.style.display = 'none'; }, 4000);
}

function openEventForm(id) {
  document.getElementById('evListView').style.display = 'none';
  document.getElementById('evFormView').style.display = 'block';
  document.getElementById('evAlertBox').style.display = 'none';
  // reset
  ['ev_id','ev_title','ev_date_start','ev_date_end','ev_location','ev_description','ev_tags','ev_link_url'].forEach(f => {
    document.getElementById(f).value = '';
  });
  document.getElementById('ev_status').value   = 'published';
  document.getElementById('ev_featured').checked = false;
  document.getElementById('ev_image').value    = '';
  document.getElementById('ev_img_current').textContent = '';
  document.getElementById('ev_img_preview').innerHTML = '<span style="color:var(--muted);font-size:13px">ยังไม่มีรูป</span>';

  if (!id) {
    document.getElementById('evFormTitle').textContent = 'เพิ่มอีเวนต์ใหม่';
    return;
  }
  document.getElementById('evFormTitle').textContent = 'แก้ไขอีเวนต์';
  const fd = new FormData();
  fd.append('action', 'fetch_event');
  fd.append('event_id', id);
  fetch('', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
      if (!d || !d.id) return;
      document.getElementById('ev_id').value          = d.id;
      document.getElementById('ev_title').value       = d.title       || '';
      document.getElementById('ev_date_start').value  = d.date_start  || '';
      document.getElementById('ev_date_end').value    = d.date_end    || '';
      document.getElementById('ev_location').value    = d.location    || '';
      document.getElementById('ev_description').value = d.description || '';
      document.getElementById('ev_tags').value        = d.tags        || '';
      document.getElementById('ev_link_url').value    = d.link_url    || '';
      document.getElementById('ev_status').value      = d.status      || 'published';
      document.getElementById('ev_featured').checked  = d.featured == 1;
      if (d.image) {
        document.getElementById('ev_img_current').textContent = 'รูปปัจจุบัน: ' + d.image;
        document.getElementById('ev_img_preview').innerHTML =
          '<img src="' + d.image + '" style="width:100%;height:100%;object-fit:cover">';
      }
    });
}

function closeEventForm() {
  document.getElementById('evListView').style.display = 'block';
  document.getElementById('evFormView').style.display = 'none';
}

function previewEventImg(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('ev_img_preview').innerHTML =
      '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover">';
  };
  reader.readAsDataURL(file);
}

function saveEvent() {
  const title    = document.getElementById('ev_title').value.trim();
  const location = document.getElementById('ev_location').value.trim();
  if (!title || !location) { showEvAlert('กรุณากรอก ชื่องาน และ สถานที่จัดงาน', 'err'); return; }

  const btn = document.getElementById('evSaveBtn');
  btn.disabled = true; btn.textContent = 'กำลังบันทึก...';

  const id = document.getElementById('ev_id').value;
  const fd = new FormData();
  fd.append('action',     id ? 'update_event' : 'add_event');
  if (id) fd.append('event_id', id);
  fd.append('title',      title);
  fd.append('date_start', document.getElementById('ev_date_start').value);
  fd.append('date_end',   document.getElementById('ev_date_end').value);
  fd.append('location',   location);
  fd.append('description',document.getElementById('ev_description').value.trim());
  fd.append('tags',       document.getElementById('ev_tags').value.trim());
  fd.append('link_url',   document.getElementById('ev_link_url').value.trim());
  fd.append('status',     document.getElementById('ev_status').value);
  if (document.getElementById('ev_featured').checked) fd.append('featured', '1');
  const imgFile = document.getElementById('ev_image').files[0];
  if (imgFile) fd.append('event_image', imgFile);

  fetch('', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
      btn.disabled = false; btn.textContent = 'บันทึก';
      if (d.ok) {
        showEvAlert('บันทึกอีเวนต์เรียบร้อยแล้ว', 'ok');
        setTimeout(() => location.reload(), 1200);
      } else {
        showEvAlert('เกิดข้อผิดพลาด: ' + (d.msg || ''), 'err');
      }
    });
}

function deleteEvent(id, btn) {
  if (!confirm('ลบอีเวนต์นี้?')) return;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('action', 'delete_event');
  fd.append('event_id', id);
  fetch('', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
      if (d.ok) {
        const row = document.getElementById('ev-row-' + id);
        if (row) row.remove();
      } else {
        alert('เกิดข้อผิดพลาด: ' + (d.msg || ''));
        btn.disabled = false;
      }
    });
}

</script>
</body>
</html>