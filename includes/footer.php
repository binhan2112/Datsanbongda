<!-- ══ FOOTER ════════════════════════════════════════════════════ -->
<footer>
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div class="footer-brand">
                <a href="<?php echo $base_url; ?>index.php" class="logo" style="margin-bottom:16px;">
                    <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="CanThoSport Logo" style="height: 36px; width: auto; border-radius: 4px; object-fit: contain; margin-right: 8px;"> CanTho<span>Sport</span>
                </a>
                <p><?php echo __trans('footer_desc'); ?></p>
            </div>

            <!-- Links -->
            <div class="footer-col">
                <h4><?php echo __trans('explore'); ?></h4>
                <ul>
                    <li><a href="<?php echo $base_url; ?>index.php"><?php echo __trans('home'); ?></a></li>
                    <li><a href="<?php echo $base_url; ?>index.php#fields-list"><?php echo __trans('fields_list'); ?></a></li>
                    <li><a href="<?php echo $base_url; ?>pages/events.php"><?php echo __trans('events'); ?></a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4><?php echo __trans('account'); ?></h4>
                <ul>
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?php echo $base_url; ?>pages/profile.php"><?php echo __trans('profile'); ?></a></li>
                        <li><a href="<?php echo $base_url; ?>pages/my_bookings.php"><?php echo __trans('my_bookings'); ?></a></li>
                        <li><a href="<?php echo $base_url; ?>pages/favorites.php"><?php echo __trans('favorites'); ?></a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>auth/login.php"><?php echo __trans('login'); ?></a></li>
                        <li><a href="<?php echo $base_url; ?>auth/register.php"><?php echo __trans('free_register'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4><?php echo __trans('support'); ?></h4>
                <ul>
                    <li><a href="<?php echo $base_url; ?>pages/about.php"><?php echo __trans('help_center'); ?></a></li>
                    <li><a href="<?php echo $base_url; ?>pages/about.php#contact"><?php echo __trans('contact_us'); ?></a></li>
                    <li><a href="<?php echo $base_url; ?>pages/terms.php"><?php echo __trans('terms'); ?></a></li>
                    <li><a href="<?php echo $base_url; ?>pages/privacy.php"><?php echo __trans('privacy'); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 CanThoSport. <?php echo __trans('all_rights_reserved'); ?></p>
            <div class="powered">
                <i data-lucide="zap" style="width:14px;height:14px;color:var(--primary);"></i>
                <?php echo __trans('developed_by'); ?> <strong style="color:var(--text-secondary);margin-left:4px;">Antigravity</strong>
            </div>
        </div>
    </div>
</footer>

<!-- AI Chatbot Widget -->
<?php include 'ai_chatbot.php'; ?>

<script>
    lucide.createIcons();

    // Toggle user dropdown menu
    function toggleUserMenu() {
        const menu = document.getElementById('userDropdownMenu');
        if (menu) menu.classList.toggle('show');
    }

    // Toggle mobile menu
    function toggleMobileMenu() {
        const navLinks = document.querySelector('.nav-links');
        if (navLinks) navLinks.classList.toggle('mobile-show');
    }

    // Đóng dropdown khi click ra ngoài
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('userDropdown');
        const menu = document.getElementById('userDropdownMenu');
        if (dropdown && menu && !dropdown.contains(e.target)) {
            menu.classList.remove('show');
        }
    });
</script>
</body>
</html>
