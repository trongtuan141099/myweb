            </div>  <!-- Close .content-area -->
        </div>  <!-- Close .main-content -->

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2026 HRMS - Hệ thống Quản lý Nhân Sự. All rights reserved.</p>
        </footer>
    </div>  <!-- Close .container-wrapper -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            // Add active class to current page link
            const urlParams = new URLSearchParams(window.location.search);
            const mainPage = urlParams.get('mainpage');
            const subPage = urlParams.get('subpage');

            if (mainPage) {
                $(`a[href*="mainpage=${mainPage}&subpage=${subPage}"]`).addClass('active');
            }

            // Search functionality
            $('form:has(input[placeholder*="Tìm"])').on('submit', function(e){
                e.preventDefault();
                const searchTerm = $(this).find('input').val();
                console.log('Searching for: ' + searchTerm);
            });
        });
    </script>
</body>
</html>
