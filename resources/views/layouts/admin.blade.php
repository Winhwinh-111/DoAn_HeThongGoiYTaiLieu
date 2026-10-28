<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cổng Quản trị Thư viện HUB')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --blue-main: #003366;
            --red-main: #a71d2a;
        }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background-color: var(--blue-main); min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #cfd8dc; padding: 12px 20px; font-weight: 500; font-size: 0.95rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: rgba(255, 255, 255, 0.12); border-left: 4px solid var(--red-main); }
        .top-navbar { background-color: #ffffff; border-bottom: 2px solid var(--red-main); }
        .card-custom { border: none; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0 sticky-top" style="height: 100vh; overflow-y: auto;">
                <div class="p-3 text-center border-bottom border-secondary">
                    <img src="{{ asset('images/logo_hub.png') }}" height="42" alt="HUB Logo" onerror="this.onerror=null; this.src='https://via.placeholder.com/150x42?text=HUB+ADMIN';">
                    <div class="mt-2 text-uppercase text-light fw-bold fs-8">HỆ THỐNG QUẢN TRỊ HUB</div>
                </div>
                
                <ul class="nav flex-column mt-3">
                    @if(session('role') === 'QuanTriThuVien')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.library.dashboard') ? 'active' : '' }}" href="{{ route('admin.library.dashboard') }}">
                                <i class="bi bi-speedometer2 me-2"></i>Tổng quan Thủ thư
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.library.documents*') ? 'active' : '' }}" href="{{ route('admin.library.documents.index') }}">
                                <i class="bi bi-journal-album me-2"></i>Quản lý Tài liệu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.library.borrow*') ? 'active' : '' }}" href="{{ route('admin.library.borrow.index') }}">
                                <i class="bi bi-arrow-left-right me-2"></i>Quản lý Mượn trả
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.library.reports') ? 'active' : '' }}" href="{{ route('admin.library.reports') }}">
                                <i class="bi bi-bar-chart-line me-2"></i>Thống kê Phản hồi & Tìm kiếm
                            </a>
                        </li>
                    @elseif(session('role') === 'QuanTriHeThong')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.system.dashboard') ? 'active' : '' }}" href="{{ route('admin.system.dashboard') }}">
                                <i class="bi bi-cpu me-2"></i>Tổng quan Hệ thống
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.system.import*') ? 'active' : '' }}" href="{{ route('admin.system.import.view') }}">
                                <i class="bi bi-file-earmark-arrow-up me-2"></i>Nhập dữ liệu Đào tạo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.system.config*') ? 'active' : '' }}" href="{{ route('admin.system.config.view') }}">
                                <i class="bi bi-sliders me-2"></i>Cấu hình & Mô Hình
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.system.reports') ? 'active' : '' }}" href="{{ route('admin.system.reports') }}">
                                <i class="bi bi-graph-up-arrow me-2"></i>Thống kê Hệ thống
                            </a>
                        </li>
                    @endif
                    
                    <li class="nav-item mt-4 border-top border-secondary pt-2">
                        <a class="nav-link text-warning" href="{{ route('home') }}">
                            <i class="bi bi-globe me-2"></i>Xem Trang người dùng
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-10 ms-sm-auto p-0">
                <div class="top-navbar p-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark">@yield('page_title', 'Bảng điều khiển')</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger me-3 px-3 py-2 fs-7">
                            <i class="bi bi-person-badge me-1"></i>{{ session('admin_name', 'Cán bộ') }} ({{ session('role') }})
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>

                <div class="px-4 pt-3">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>

                <div class="p-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>