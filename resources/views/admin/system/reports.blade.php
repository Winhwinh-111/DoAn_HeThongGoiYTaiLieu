@extends('layouts.admin')

@section('title', 'Thống kê Hệ thống')
@section('page_title', 'THỐNG KÊ HỆ THỐNG ĐÀO TẠO & MÔ HÌNH GỢI Ý')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card card-custom bg-white p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-diagram-3 me-2 text-danger"></i>THỐNG KÊ DỮ LIỆU ĐÀO TẠO</h6>
            <div class="mt-3 fs-7">
                <p class="mb-2">Tổng số ngành đào tạo: <strong>{{ \App\Models\NganhHoc::count() }}</strong></p>
                <p class="mb-2">Tổng số học phần: <strong>{{ \App\Models\HocPhan::count() }}</strong></p>
                <p class="mb-2">Tổng số hồ sơ sinh viên: <strong>{{ \App\Models\SinhVien::count() }}</strong></p>
                <p class="mb-0">Tổng lượt mượn trả: <strong>{{ \App\Models\MuonTra::count() }}</strong></p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-custom bg-white p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-cpu me-2 text-primary"></i>LỊCH SỬ MÔ HÌNH AI</h6>
            <div class="mt-3 fs-7">
                <p class="mb-2">Tổng số phiên bản mô hình: <strong>{{ \App\Models\MoHinhGoiY::count() }}</strong></p>
                <p class="mb-2">Mô hình đang hoạt động: <strong>{{ \App\Models\MoHinhGoiY::where('la_hien_hanh', 1)->value('ma_mo_hinh') ?? 'N/A' }}</strong></p>
                <p class="mb-0">Thời điểm tạo mô hình hiện tại: <strong>{{ \App\Models\MoHinhGoiY::where('la_hien_hanh', 1)->value('thoi_diem_tao') ?? 'N/A' }}</strong></p>
            </div>
        </div>
    </div>
</div>
@endsection