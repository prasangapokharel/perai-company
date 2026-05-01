            </div>
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 z-30 hidden transition-opacity duration-300"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function toggleSidebar() {
                const isOpen = sidebar.classList.contains('mobile-open');
                if (isOpen) {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                } else {
                    sidebar.classList.add('mobile-open');
                    sidebarOverlay.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }

            // Close sidebar on window resize if larger than mobile
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768 && sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>
