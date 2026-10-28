@extends('layouts.admin')

@section('title', 'Nhập dữ liệu đào tạo')
@section('page_title', 'NHẬP DỮ LIỆU ĐÀO TẠO TỪ TỆP')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card card-custom bg-white p-4 shadow-sm">
            <h6 class="fw-bold text-dark border-bottom pb-3 mb-4">
                <i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>TẢI TỆP DỮ LIỆU ĐÀO TẠO (CSV / EXCEL)
            </h6>

            <form action="{{ route('admin.system.import.process') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">1. Chọn loại dữ liệu cần nhập (*):</label>
                    <select name="data_type" class="form-select form-select-sm" required>
                        <option value="">-- Chọn loại dữ liệu --</option>
                        <option value="sinh_vien">Danh sách Sinh viên</option>
                        <option value="hoc_phan">Danh mục Học phần</option>
                        <option value="nganh_hoc">Danh mục Ngành học</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">2. Chọn tệp dữ liệu (.csv, .xlsx, .xls) (*):</label>
                    <input type="file" name="file_import" class="form-control form-control-sm" accept=".csv, .xlsx, .xls" required>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-cloud-upload me-1"></i> Bắt đầu Nhập dữ liệu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection