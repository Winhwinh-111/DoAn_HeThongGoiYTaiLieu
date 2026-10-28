@extends('layouts.admin')

@section('title', 'Tổng quan Thủ thư')
@section('page_title', 'BẢNG ĐIỀU KHIỂN THỦ THƯ THƯ VIỆN HUB')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-primary border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1 fs-7">TỔNG TÀI LIỆU KHO</h6>
                    <h3 class="fw-bold text-primary m-0">{{ $totalBooks }}</h3>
                </div>
                <i class="bi bi-journal-album fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-warning border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1 fs-7">SÁCH ĐANG CHO MƯỢN</h6>
                    <h3 class="fw-bold text-warning m-0">{{ $totalBorrowing }}</h3>
                </div>
                <i class="bi bi-journal-arrow-up fs-1 text-warning opacity-50"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-success border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1 fs-7">ĐÁNH GIÁ PHÙ HỢP (AI)</h6>
                    <h3 class="fw-bold text-success m-0">{{ $totalFeedback }}</h3>
                </div>
                <i class="bi bi-chat-left-heart fs-1 text-success opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom bg-white p-4">
    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-danger"></i>GIAO DỊCH MƯỢN SÁCH MỚI NHẤT</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="table-light">
                <tr>
                    <th>Mã GD</th>
                    <th>Sinh viên (MSSV)</th>
                    <th>Tên sách</th>
                    <th>Ngày mượn</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBorrows as $b)
                    <tr>
                        <td><strong class="text-danger">{{ $b->ma_giao_dich }}</strong></td>
                        <td>{{ $b->sinhVien->ho_ten ?? $b->mssv }} ({{ $b->mssv }})</td>
                        <td>{{ $b->taiLieu->nhan_de ?? $b->ma_tai_lieu }}</td>
                        <td>{{ date('d/m/Y H:i', strtotime($b->ngay_muon)) }}</td>
                        <td>
                            @if($b->trang_thai === 'DaTra')
                                <span class="badge bg-success">Đã trả</span>
                            @else
                                <span class="badge bg-warning text-dark">Đang mượn</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Chưa có giao dịch mượn sách.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection