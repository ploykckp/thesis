<!-- ══════════ FOOTER ══════════ -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-left">
            <div class="footer-logo"><img src="logo_w.png" alt="logo"></div>
        </div>
        <div class="footer-center">
            <h3 class="footer-title">Follow US</h3>
            <div class="social-icons">
                <div class="social-icon"><span class="iconify" data-icon="mdi:instagram"></span></div>
                <div class="social-icon"><span class="iconify" data-icon="mdi:facebook"></span></div>
                <div class="social-icon"><span class="iconify" data-icon="mdi:email"></span></div>
                <div class="social-icon"><span class="iconify" data-icon="mdi:phone"></span></div>
            </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-right">
            <p>เราเป็นแพลตฟอร์ม ค้นหาสถานที่ท่องเที่ยว ที่พัก คาเฟ่</p>
            <p>และมูลนิธิที่รองรับสัตว์เลี้ยงทั่วประเทศไทย</p>
            <p>ถูกออกแบบมาเพื่อให้ผู้เลี้ยงสัตว์สามารถเดินทางได้ง่ายขึ้น</p>
            <p>พร้อมข้อมูลครบถ้วนในที่เดียว — ทั้งประเภทสัตว์ที่เข้าได้ เงื่อนไข</p>
            <p>และรีวิวจากผู้ใช้</p>
        </div>
    </div>
    <div class="footer-copyright">
        <p id="copyright-text" style="cursor:default;user-select:none;" title="">© Copyright 2025 Pawland.co.th</p>
        <a id="admin-secret-link" href="admin_login.php" style="display:none;position:absolute;">
            <span class="iconify" data-icon="mdi:shield-account" style="font-size:18px;color:#aaa;"></span>
        </a>
    </div>
</footer>

<script>
(function() {
    var count = 0;
    var timer;
    document.getElementById('copyright-text').addEventListener('click', function() {
        count++;
        clearTimeout(timer);
        if (count >= 5) {
            document.getElementById('admin-secret-link').style.display = 'inline-block';
            count = 0;
        }
        timer = setTimeout(function() { count = 0; }, 1500);
    });
})();
</script>