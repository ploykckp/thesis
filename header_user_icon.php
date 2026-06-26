<?php
/**
 * header_user_icon.php
 * Include inside every page's <header> .header-right div.
 * Requires session_start() to already be called on the page.
 */
$_icon_href  = isset($_SESSION['user_id']) ? 'profile.php' : 'form-login.php';
$_icon_color = isset($_SESSION['user_id']) ? '#64b5f6'     : '#94a3b8';
$_icon_name  = isset($_SESSION['user_id']) ? 'mdi:account-circle' : 'mdi:account';
$_icon_title = isset($_SESSION['user_id'])
    ? ('โปรไฟล์ของ ' . htmlspecialchars($_SESSION['firstname'] ?? ''))
    : 'เข้าสู่ระบบ';
$_logged_in  = isset($_SESSION['user_id']);

// Count favorites for badge
$_fav_count = 0;
if ($_logged_in && isset($pdo) && $pdo) {
    try {
        $__s = $pdo->prepare("SELECT COUNT(*) FROM favorite WHERE user_id = ?");
        $__s->execute([$_SESSION['user_id']]);
        $_fav_count = (int)$__s->fetchColumn();
    } catch (Exception $e) { $_fav_count = 0; }
}
?>

<!-- ── User icon ── -->
<button class="icon-btn" title="<?= $_icon_title ?>" style="color:<?= $_icon_color ?>">
    <a href="<?= $_icon_href ?>" style="color:<?= $_icon_color ?>; display:flex; align-items:center;">
        <span class="iconify" data-icon="<?= $_icon_name ?>" data-width="28" data-height="28"></span>
    </a>
</button>

<!-- ── Favorites heart icon → ไปหน้า favorites.php ── -->
<div class="fav-header-wrap">
    <a href="<?= $_logged_in ? 'favorites.php' : 'form-login.php' ?>"
       class="icon-btn fav-header-btn" title="รายการโปรด" style="position:relative; display:flex; align-items:center;">
        <span class="iconify"
              data-icon="<?= $_fav_count > 0 ? 'mdi:heart' : 'mdi:heart-outline' ?>"
              data-width="28" data-height="28"
              style="<?= $_fav_count > 0 ? 'color:#e53e3e' : '' ?>"></span>
        <?php if ($_fav_count > 0): ?>
        <span class="fav-badge"><?= $_fav_count > 99 ? '99+' : $_fav_count ?></span>
        <?php endif; ?>
    </a>
</div>

<style>
.fav-header-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.fav-header-btn {
    position: relative;
    text-decoration: none;
    color: #1e293b;
}
.fav-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e53e3e;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    pointer-events: none;
    line-height: 1;
}
</style>