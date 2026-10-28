@extends('layouts.admin')

@section('title', 'Tổng quan Quản trị Hệ thống')
@section('page_title', 'BẢNG ĐIỀU KHIỂN QUẢN TRỊ HỆ THỐNG IT')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-danger border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1 fs-7">TỔNG HỒ SƠ SINH VIÊN</h6>
                    <h3 class="fw-bold text-danger m-0">{{ $studentCount }}</h3>
                </div>
                <i class="bi bi-people fs-1 text-danger opacity-50"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-info border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1 fs-7">TỔNG HỌC PHẦN ĐÀO TẠO</h6>
                    <h3 class="fw-bold text-info m-0">{{ $courseCount }}</h3>
                </div>
                <i class="bi bi-book fs-1 text-info opacity-50"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-success border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1 fs-7">MÔ HÌNH AI HIỆN HÀNH</h6>
                    <h6 class="fw-bold text-success m-0">{{ $activeModel->ma_mo_hinh ?? 'MH_001' }}</h6>
                </div>
                <i class="bi bi-cpu fs-1 text-success opacity-50"></i>
            </div>
        </div>
    </div>
</div>
@endsection