<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử mượn trả tài liệu - Thư viện HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --blue-main: #003366;
            --red-main: #a71d2a;
        }
        /* Cấu hình Flexbox để đẩy Footer luôn nằm ở cuối trang */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper {
            flex: 1 0 auto;
        }
        .navbar-custom {
            background-color: #ffffff;
            border-bottom: 3px solid var(--red-main);
        }
        .section-title {
            color: var(--blue-main);
            font-weight: 700;
            border-left: 4px solid var(--red-main);
            padding-left: 10px;
        }
        footer {
            margin-top: auto !important;
        }
    </style>
</head>
<body>

    <!-- KHỐI NỘI DUNG CHÍNH -->
    <div class="main-wrapper">
        <!-- HEADER TRẮNG THƯ VIỆN HUB -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom sticky-top shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}" title="Trang chủ Thư viện HUB">
                    <img src="{{ asset('images/logo_lib.jpg') }}" alt="Thư viện HUB Logo" height="80" class="me-2" onerror="this.onerror=null; this.src='https://via.placeholder.com/180x48?text=HUB+LIBRARY';">
                </a>

                <div class="ms-auto">
                    <a href="{{ route('home') }}" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại trang chủ
                    </a>
                </div>
            </div>
        </nav>

        <div class="container my-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h4 class="section-title m-0">LỊCH SỬ MƯỢN TRẢ TÀI LIỆU</h4>
                    <span class="text-muted fs-7">Sinh viên: <strong>{{ $sinhVien->ho_ten ?? session('student_name') }}</strong> ({{ session('mssv') }})</span>
                </div>
                
                <div class="card-body p-4 pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 60px;">STT</th>
                                    <th>Tên tài liệu / Sách</th>
                                    <th style="width: 130px;">Ngày mượn</th>
                                    <th style="width: 130px;">Hạn trả</th>
                                    <th style="width: 130px;">Ngày trả thực tế</th>
                                    <th style="width: 140px;">Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lichSuMuon as $item)
                                    <tr>
                                        <!-- Thay mã giao dịch bằng STT -->
                                        <td class="text-center fw-bold text-secondary">{{ $loop->iteration }}</td>
                                        
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $item->taiLieu->nhan_de ?? $item->ma_tai_lieu }}</div>
                                            <small class="text-muted">Mã tài liệu: {{ $item->ma_tai_lieu }}</small>
                                        </td>
                                        
                                        <td class="text-center">
                                            {{ $item->ngay_muon ? date('d/m/Y', strtotime($item->ngay_muon)) : 'N/A' }}
                                        </td>
                                        
                                        <td class="text-center">
                                            @if($item->ngay_tra)
                                                <span class="text-success fw-semibold">{{ date('d/m/Y', strtotime($item->ngay_tra)) }}</span>
                                            @else
                                                <span class="text-muted fs-7"><i>Chưa trả</i></span>
                                            @endif
                                        </td>
                                        
                                        <td class="text-center">
                                            @if($item->trang_thai === 'Đã trả' || $item->trang_thai === 'DaTra')
                                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                    <i class="bi bi-check-circle me-1"></i>Đã trả
                                                </span>
                                            @elseif($item->trang_thai === 'Đang mượn' || $item->trang_thai === 'DangMuon')
                                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                                    <i class="bi bi-journal-arrow-up me-1"></i>Đang mượn
                                                </span>
                                            @elseif($item->trang_thai === 'Quá hạn' || $item->trang_thai === 'QuaHan')
                                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Quá hạn
                                                </span>
                                            @else
                                                <span class="badge bg-secondary px-2 py-1">{{ $item->trang_thai }}</span>
                                            @endif
                                        </td>
                                        
                                        <td class="fs-7 text-muted">
                                            {{ $item->ghi_chu ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Bạn chưa có lịch sử mượn trả tài liệu nào tại thư viện.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER HUB LUÔN DÍNH Ở ĐÁY MÀN HÌNH -->
    <footer class="bg-dark text-white pt-4 pb-3 border-top border-danger border-3">
        <div class="container text-center fs-7">
            <p class="mb-1 fw-bold">THƯ VIỆN TRƯỜNG ĐẠI HỌC NGÂN HÀNG THÀNH PHỐ HỒ CHÍ MINH</p>
            <p class="text-light opacity-75 mb-0">Địa chỉ: 36 Tôn Thất Thuyết, Phường 18, Quận 4, TP. Hồ Chí Minh / 56 Hoàng Diệu 2, Thủ Đức, TP. Hồ Chí Minh</p>
            <small class="text-muted d-block mt-2">© 2026 HUB Library System - Hệ thống Gợi ý Tài liệu Thông minh</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>