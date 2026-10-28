@extends('layouts.admin')

@section('title', 'Quản lý Tài liệu')
@section('page_title', 'DANH MỤC TÀI LIỆU SÁCH')

@section('content')
<div class="card card-custom bg-white p-4 mb-4">
    <!-- THANH TÌM KIẾM VÀ TÁC VỤ -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form action="{{ route('admin.library.documents.index') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="keyword" value="{{ $keyword }}" class="form-control form-control-sm" placeholder="Tìm theo tên, tác giả, mã..." style="width: 280px;">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Tìm</button>
        </form>
        
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#addBookModal"><i class="bi bi-plus-lg me-1"></i>Thêm Sách mới</button>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importBookModal"><i class="bi bi-file-earmark-excel me-1"></i>Nhập CSV (Import)</button>
        </div>
    </div>

    <!-- BẢNG HIỂN THỊ ĐẦY ĐỦ CÁC TRƯỜNG DỮ LIỆU CỦA TAILIEU -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 fs-8" style="min-width: 1400px;">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 100px;">Mã TL</th>
                    <th style="width: 60px;">Ảnh bìa</th>
                    <th style="width: 200px;">Nhan đề</th>
                    <th style="width: 130px;">Tác giả</th>
                    <th style="width: 120px;">Nhà xuất bản</th>
                    <th style="width: 70px;">Năm XB</th>
                    <th style="width: 80px;">Ngôn ngữ</th>
                    <th style="width: 100px;">Mã ISBN</th>
                    <th style="width: 150px;">Thuật ngữ chủ đề</th>
                    <th style="width: 120px;">Nơi lưu giữ</th>
                    <th style="width: 110px;">Trạng thái</th>
                    <th>Mô tả nội dung</th>
                    <th style="width: 100px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td class="fw-bold text-danger text-center">{{ $doc->ma_tai_lieu }}</td>
                        <td class="text-center p-1">
                            <img src="{{ $doc->anh_bia }}" style="height: 40px; width: 30px; object-fit: cover;" alt="Bìa" onerror="this.src='https://via.placeholder.com/30x40?text=No+Img';">
                        </td>
                        <td class="fw-semibold text-dark">{{ $doc->nhan_de }}</td>
                        <td>{{ $doc->tac_gia ?? '-' }}</td>
                        <td>{{ $doc->nha_xuat_ban ?? '-' }}</td>
                        <td class="text-center">{{ $doc->nam_xuat_ban ?? '-' }}</td>
                        <td class="text-center">{{ $doc->ngon_ngu ?? 'Tiếng Việt' }}</td>
                        <td class="text-center">{{ $doc->isbn ?? '-' }}</td>
                        <td>
                            <small class="text-secondary">{{ $doc->thuat_ngu_chu_de ?? '-' }}</small>
                        </td>
                        <td>{{ $doc->noi_luu_giu ?? 'Thư viện HUB' }}</td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success px-1 py-1">{{ $doc->trang_thai_su_dung ?? 'Sẵn sàng' }}</span>
                        </td>
                        <td>
                            <div class="text-truncate-2 text-muted" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $doc->mo_ta }}">
                                {{ $doc->mo_ta ?? '-' }}
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-warning py-0 px-2" data-bs-toggle="modal" data-bs-target="#editModal-{{ $doc->ma_tai_lieu }}" title="Sửa"><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('admin.library.documents.delete', $doc->ma_tai_lieu) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sách này?');" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- MODAL SỬA THÔNG TIN SÁCH -->
                    <div class="modal fade" id="editModal-{{ $doc->ma_tai_lieu }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form action="{{ route('admin.library.documents.update', $doc->ma_tai_lieu) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">CẬP NHẬT TÀI LIỆU</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body fs-7">
                                        <div class="row g-2">
                                            <div class="col-md-6 mb-2"><label class="form-label">Mã tài liệu:</label><input type="text" class="form-control form-control-sm" value="{{ $doc->ma_tai_lieu }}" disabled></div>
                                            <div class="col-md-6 mb-2"><label class="form-label">Nhan đề sách (*):</label><input type="text" name="nhan_de" class="form-control form-control-sm" value="{{ $doc->nhan_de }}" required></div>
                                            <div class="col-md-6 mb-2"><label class="form-label">Tác giả:</label><input type="text" name="tac_gia" class="form-control form-control-sm" value="{{ $doc->tac_gia }}"></div>
                                            <div class="col-md-6 mb-2"><label class="form-label">Nhà xuất bản:</label><input type="text" name="nha_xuat_ban" class="form-control form-control-sm" value="{{ $doc->nha_xuat_ban }}"></div>
                                            <div class="col-md-4 mb-2"><label class="form-label">Năm xuất bản:</label><input type="number" name="nam_xuat_ban" class="form-control form-control-sm" value="{{ $doc->nam_xuat_ban }}"></div>
                                            <div class="col-md-4 mb-2"><label class="form-label">Ngôn ngữ:</label><input type="text" name="ngon_ngu" class="form-control form-control-sm" value="{{ $doc->ngon_ngu }}"></div>
                                            <div class="col-md-4 mb-2"><label class="form-label">Mã ISBN:</label><input type="text" name="isbn" class="form-control form-control-sm" value="{{ $doc->isbn }}"></div>
                                            <div class="col-md-6 mb-2"><label class="form-label">Nơi lưu giữ:</label><input type="text" name="noi_luu_giu" class="form-control form-control-sm" value="{{ $doc->noi_luu_giu }}"></div>
                                            <div class="col-md-6 mb-2"><label class="form-label">Trạng thái:</label><input type="text" name="trang_thai_su_dung" class="form-control form-control-sm" value="{{ $doc->trang_thai_su_dung }}"></div>
                                            <div class="col-md-12 mb-2"><label class="form-label">Thuật ngữ chủ đề:</label><input type="text" name="thuat_ngu_chu_de" class="form-control form-control-sm" value="{{ $doc->thuat_ngu_chu_de }}"></div>
                                            <div class="col-md-12 mb-2"><label class="form-label">URL Ảnh bìa:</label><input type="text" name="anh_bia" class="form-control form-control-sm" value="{{ $doc->anh_bia }}"></div>
                                            <div class="col-md-12 mb-2"><label class="form-label">Mô tả nội dung:</label><textarea name="mo_ta" class="form-control form-control-sm" rows="3">{{ $doc->mo_ta }}</textarea></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Lưu thay đổi</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="13" class="text-center text-muted">Không tìm thấy tài liệu nào trong CSDL.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PHÂN TRANG -->
<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted fs-8">
        Hiển thị <strong>{{ $documents->firstItem() ?? 0 }}</strong> - <strong>{{ $documents->lastItem() ?? 0 }}</strong> trên tổng số <strong>{{ $documents->total() }}</strong> tài liệu
    </div>
    <div>
        {{ $documents->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- MODAL THÊM SÁCH MỚI -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.library.documents.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">THÊM TÀI LIỆU MỚI</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body fs-7">
                    <div class="row g-2">
                        <div class="col-md-6 mb-2"><label class="form-label">Mã tài liệu (*):</label><input type="text" name="ma_tai_lieu" class="form-control form-control-sm" required></div>
                        <div class="col-md-6 mb-2"><label class="form-label">Nhan đề sách (*):</label><input type="text" name="nhan_de" class="form-control form-control-sm" required></div>
                        <div class="col-md-6 mb-2"><label class="form-label">Tác giả:</label><input type="text" name="tac_gia" class="form-control form-control-sm"></div>
                        <div class="col-md-6 mb-2"><label class="form-label">Nhà xuất bản:</label><input type="text" name="nha_xuat_ban" class="form-control form-control-sm"></div>
                        <div class="col-md-4 mb-2"><label class="form-label">Năm xuất bản:</label><input type="number" name="nam_xuat_ban" class="form-control form-control-sm" value="2026"></div>
                        <div class="col-md-4 mb-2"><label class="form-label">Ngôn ngữ:</label><input type="text" name="ngon_ngu" class="form-control form-control-sm" value="Tiếng Việt"></div>
                        <div class="col-md-4 mb-2"><label class="form-label">Mã ISBN:</label><input type="text" name="isbn" class="form-control form-control-sm"></div>
                        <div class="col-md-6 mb-2"><label class="form-label">Nơi lưu giữ:</label><input type="text" name="noi_luu_giu" class="form-control form-control-sm" value="Thư viện HUB"></div>
                        <div class="col-md-6 mb-2"><label class="form-label">Trạng thái:</label><input type="text" name="trang_thai_su_dung" class="form-control form-control-sm" value="Sẵn sàng phục vụ"></div>
                        <div class="col-md-12 mb-2"><label class="form-label">Thuật ngữ chủ đề:</label><input type="text" name="thuat_ngu_chu_de" class="form-control form-control-sm"></div>
                        <div class="col-md-12 mb-2"><label class="form-label">URL Ảnh bìa:</label><input type="text" name="anh_bia" class="form-control form-control-sm"></div>
                        <div class="col-md-12 mb-2"><label class="form-label">Mô tả nội dung:</label><textarea name="mo_ta" class="form-control form-control-sm" rows="3"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Lưu tài liệu</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT TỆP DỮ LIỆU -->
<div class="modal fade" id="importBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.library.documents.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">IMPORT DANH MỤC TÀI LIỆU (CSV / EXCEL)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body fs-7">
                    <label class="form-label fw-semibold">Chọn tệp (.csv, .xlsx, .xls):</label>
                    <input type="file" name="file_import" class="form-control form-control-sm" accept=".csv, .xlsx, .xls" required>
                    <small class="text-muted d-block mt-2">Dòng đầu tiên trong file phải là tiêu đề cột (Header).</small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-arrow-up me-1"></i> Bắt đầu Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection