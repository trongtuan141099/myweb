            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="accordion accordion-flush" id="sidebarAccordion">
                    <!-- Tổng quan -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOverview">
                                <i class="bi bi-speedometer2 me-2"></i> Tổng quan
                            </button>
                        </h2>
                        <div id="collapseOverview" class="accordion-collapse collapse show" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="index.php?mainpage=dashboard&subpage=overview" class="list-group-item list-group-item-action">
                                        <i class="bi bi-house-door me-2"></i> Dashboard
                                    </a>
                                    <a href="index.php?mainpage=dashboard&subpage=statistics" class="list-group-item list-group-item-action">
                                        <i class="bi bi-bar-chart me-2"></i> Thống kê
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Master Data -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaster">
                                <i class="bi bi-diagram-3 me-2"></i> Master Data
                            </button>
                        </h2>
                        <div id="collapseMaster" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="index.php?mainpage=masterdata&subpage=departments" class="list-group-item list-group-item-action">
                                        <i class="bi bi-building me-2"></i> Phòng ban
                                    </a>
                                    <a href="index.php?mainpage=masterdata&subpage=positions" class="list-group-item list-group-item-action">
                                        <i class="bi bi-briefcase me-2"></i> Chức vụ
                                    </a>
                                    <a href="index.php?mainpage=masterdata&subpage=settings" class="list-group-item list-group-item-action">
                                        <i class="bi bi-gear me-2"></i> Cài đặt
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nhân viên -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEmployee">
                                <i class="bi bi-people me-2"></i> Nhân viên
                            </button>
                        </h2>
                        <div id="collapseEmployee" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="index.php?mainpage=employees&subpage=list" class="list-group-item list-group-item-action">
                                        <i class="bi bi-list-ul me-2"></i> Danh sách
                                    </a>
                                    <a href="index.php?mainpage=employees&subpage=add" class="list-group-item list-group-item-action">
                                        <i class="bi bi-plus-circle me-2"></i> Thêm mới
                                    </a>
                                    <a href="index.php?mainpage=employees&subpage=import" class="list-group-item list-group-item-action">
                                        <i class="bi bi-upload me-2"></i> Nhập dữ liệu
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chuyên cần -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAttendance">
                                <i class="bi bi-calendar-check me-2"></i> Chuyên cần
                            </button>
                        </h2>
                        <div id="collapseAttendance" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="index.php?mainpage=attendance&subpage=list" class="list-group-item list-group-item-action">
                                        <i class="bi bi-list-ul me-2"></i> Danh sách
                                    </a>
                                    <a href="index.php?mainpage=attendance&subpage=checkin" class="list-group-item list-group-item-action">
                                        <i class="bi bi-clock me-2"></i> Điểm danh
                                    </a>
                                    <a href="index.php?mainpage=attendance&subpage=report" class="list-group-item list-group-item-action">
                                        <i class="bi bi-file-earmark me-2"></i> Báo cáo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lương/Bảng lương -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePayroll">
                                <i class="bi bi-credit-card me-2"></i> Lương
                            </button>
                        </h2>
                        <div id="collapsePayroll" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="index.php?mainpage=payroll&subpage=list" class="list-group-item list-group-item-action">
                                        <i class="bi bi-list-ul me-2"></i> Danh sách
                                    </a>
                                    <a href="index.php?mainpage=payroll&subpage=create" class="list-group-item list-group-item-action">
                                        <i class="bi bi-plus-circle me-2"></i> Tạo bảng lương
                                    </a>
                                    <a href="index.php?mainpage=payroll&subpage=report" class="list-group-item list-group-item-action">
                                        <i class="bi bi-file-earmark me-2"></i> Báo cáo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nghỉ phép -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeave">
                                <i class="bi bi-calendar-event me-2"></i> Nghỉ phép
                            </button>
                        </h2>
                        <div id="collapseLeave" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="index.php?mainpage=leave&subpage=list" class="list-group-item list-group-item-action">
                                        <i class="bi bi-list-ul me-2"></i> Danh sách
                                    </a>
                                    <a href="index.php?mainpage=leave&subpage=request" class="list-group-item list-group-item-action">
                                        <i class="bi bi-plus-circle me-2"></i> Đăng ký nghỉ
                                    </a>
                                    <a href="index.php?mainpage=leave&subpage=approval" class="list-group-item list-group-item-action">
                                        <i class="bi bi-check-circle me-2"></i> Phê duyệt
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Báo cáo -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReports">
                                <i class="bi bi-file-earmark-text me-2"></i> Báo cáo
                            </button>
                        </h2>
                        <div id="collapseReports" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="index.php?mainpage=reports&subpage=employee" class="list-group-item list-group-item-action">
                                        <i class="bi bi-people me-2"></i> Nhân viên
                                    </a>
                                    <a href="index.php?mainpage=reports&subpage=attendance" class="list-group-item list-group-item-action">
                                        <i class="bi bi-calendar me-2"></i> Chuyên cần
                                    </a>
                                    <a href="index.php?mainpage=reports&subpage=payroll" class="list-group-item list-group-item-action">
                                        <i class="bi bi-bar-chart me-2"></i> Lương
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Content Area -->
            <div class="content-area">
