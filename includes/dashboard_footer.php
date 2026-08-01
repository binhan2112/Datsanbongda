    </main> <!-- End Content Area -->
</div> <!-- End Main Wrapper -->

<script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Highlight active menu item based on current URL
    document.addEventListener('DOMContentLoaded', function() {
        const currentUrl = window.location.href;
        const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');
        
        menuItems.forEach(item => {
            if (currentUrl.includes(item.getAttribute('href'))) {
                // Remove active class from all
                menuItems.forEach(i => i.classList.remove('active'));
                // Add active to current
                item.classList.add('active');
            }
        });
    });
</script>
</body>
</html>
