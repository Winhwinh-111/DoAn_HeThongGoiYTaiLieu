<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CauHinhGoiY;
use App\Models\MoHinhGoiY;
use App\Models\SinhVien;
use App\Models\HocPhan;
use App\Models\NganhHoc;
use App\Models\MuonTra;
use App\Models\TaiLieu;
use App\Services\NlpTokenizerService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AdminSystemController extends Controller
{
    public function dashboard()
    {
        $studentCount = SinhVien::count();
        $courseCount = HocPhan::count();
        $majorCount = NganhHoc::count();
        $borrowCount = MuonTra::count();
        
        $activeModel = MoHinhGoiY::where('la_hien_hanh', 1)->first();
        $totalModels = MoHinhGoiY::count();

        return view('admin.system.dashboard', compact(
            'studentCount', 'courseCount', 'majorCount', 'borrowCount', 
            'activeModel', 'totalModels'
        ));
    }

        public function importDataView()
        {
            return view('admin.system.import');
        }

        public function processImportData(Request $request)
        {
            // 1. Kiểm tra file và loại dữ liệu đầu vào
            $request->validate([
                'data_type'   => 'required',
                'file_import' => 'required|file|mimes:csv,txt,xlsx,xls|max:5000',
            ], [
                'data_type.required'   => 'Vui lòng chọn loại dữ liệu đào tạo cần nhập!',
                'file_import.required' => 'Vui lòng chọn tệp dữ liệu!',
                'file_import.mimes'    => 'Hệ thống chỉ hỗ trợ định dạng tệp .csv, .xlsx, .xls!',
            ]);

            $type = strtolower(trim($request->input('data_type')));

            try {
                // 2. Tải tệp Excel / CSV và lấy danh sách các dòng
                $file = $request->file('file_import');
                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                if (empty($rows)) {
                    return redirect()->back()->with('error', 'Tệp dữ liệu rỗng!');
                }

                // Tách dòng tiêu đề (Header Row)
                $headerRow = array_shift($rows);

                // Hàm chuẩn hóa chuỗi phục vụ so khớp tiêu đề cột (bỏ dấu, viết thường, bỏ ký tự đặc biệt)
                $normalize = function($str) {
                    if (empty($str)) return '';
                    if (is_array($str)) $str = implode(' ', $str);
                    $str = mb_strtolower(trim($str));
                    $str = preg_replace('/(á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ)/', 'a', $str);
                    $str = preg_replace('/(é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ)/', 'e', $str);
                    $str = preg_replace('/(í|ì|ỉ|ĩ|ị)/', 'i', $str);
                    $str = preg_replace('/(ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ)/', 'o', $str);
                    $str = preg_replace('/(ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự)/', 'u', $str);
                    $str = preg_replace('/(ý|ỳ|ỷ|ỹ|ỵ)/', 'y', $str);
                    $str = preg_replace('/(đ)/', 'd', $str);
                    return preg_replace('/[^a-z0-9]/', '', $str);
                };

                // Hàm tìm chỉ số cột theo từ khóa (Ánh xạ tiêu đề động - Dynamic Header Mapping)
                $findColIdx = function($keywords) use ($headerRow, $normalize) {
                    foreach ($headerRow as $idx => $cellText) {
                        $normText = $normalize($cellText);
                        foreach ($keywords as $kw) {
                            if (!empty($normText) && str_contains($normText, $kw)) {
                                return $idx;
                            }
                        }
                    }
                    return null;
                };

                // 3. Quét tìm vị trí các cột tiêu đề từ file
                $maHpIdx     = $findColIdx(['mahocphan', 'mamonhoc', 'mahp', 'mamon']);
                $tenHpIdx    = $findColIdx(['tenhocphan', 'tenmonhoc', 'tenhp', 'tenmon']);
                $stcIdx      = $findColIdx(['sotinchi', 'stc', 'sotc', 'tinchi']);
                $moTaIdx     = $findColIdx(['mota', 'ghichu', 'noidung', 'tomtat']);
                
                $maNganhIdx  = $findColIdx(['manganh', 'machuyennganh', 'manch']);
                $tenNganhIdx = $findColIdx(['tennganh', 'tenchuyennganh', 'tench']);

                $mssvIdx     = $findColIdx(['mssv', 'masinhvien', 'masv', 'mathe']);
                $hoTenIdx    = $findColIdx(['hoten', 'tensinhvien', 'tensv', 'hoandten']);
                $ngaySinhIdx = $findColIdx(['ngaysinh', 'ns']);
                $gioiTinhIdx = $findColIdx(['gioitinh', 'phai']);
                $sdtIdx      = $findColIdx(['sodienthoai', 'sdt', 'dienthoai', 'phone']);
                $hanTheIdx   = $findColIdx(['hanthe', 'ngayhethan']);
                $maTkIdx     = $findColIdx(['mataikhoan', 'matk', 'taikhoan']);

                $loaiTlIdx   = $findColIdx(['loaitailieu', 'loaitl']);
                $ndDeCuongIdx= $findColIdx(['noidungdecuong', 'decuong']);
                $trangThaiIdx= $findColIdx(['trangthai', 'trangthaihoc']);

                // 4. Kiểm tra tính hợp lệ của cấu trúc tiêu đề file theo từng loại dữ liệu
                if (($type === 'nganh_hoc' || $type === 'nganhhoc') && ($maNganhIdx === null || $tenNganhIdx === null)) {
                    return redirect()->back()->with('error', 'Tệp không đúng cấu trúc mẫu Danh mục Ngành học! Thiếu cột "Mã ngành" hoặc "Tên ngành".');
                }

                if (($type === 'hoc_phan' || $type === 'hocphan') && ($maHpIdx === null || $tenHpIdx === null)) {
                    return redirect()->back()->with('error', 'Tệp không đúng cấu trúc mẫu Danh mục Học phần! Thiếu cột "Mã học phần" hoặc "Tên học phần".');
                }

                if (($type === 'de_cuong_hoc_phan' || $type === 'decuonghocphan') && ($maHpIdx === null || ($ndDeCuongIdx === null && $moTaIdx === null))) {
                    return redirect()->back()->with('error', 'Tệp không đúng cấu trúc mẫu Đề cương Học phần! Thiếu cột "Mã học phần" hoặc "Nội dung đề cương".');
                }

                if (($type === 'chuong_trinh_dao_tao' || $type === 'chuongtrinhdaotao') && ($maNganhIdx === null || $maHpIdx === null)) {
                    return redirect()->back()->with('error', 'Tệp không đúng cấu trúc mẫu Chương trình Đào tạo! Thiếu cột "Mã ngành" hoặc "Mã học phần".');
                }

                if (($type === 'sinh_vien' || $type === 'sinhvien') && ($mssvIdx === null || $hoTenIdx === null)) {
                    return redirect()->back()->with('error', 'Tệp không đúng cấu trúc mẫu Hồ sơ Sinh viên! Thiếu cột "MSSV" hoặc "Họ tên".');
                }

                if (($type === 'sinh_vien_hoc_phan' || $type === 'sinhvienhocphan') && ($mssvIdx === null || $maHpIdx === null)) {
                    return redirect()->back()->with('error', 'Tệp không đúng cấu trúc mẫu Sinh viên - Học phần! Thiếu cột "MSSV" hoặc "Mã học phần".');
                }

                // 5. Khởi tạo biến theo dõi kết quả và mở Transaction
                $countSuccess = 0;
                $countSkipped = 0;
                $errors = [];

                DB::beginTransaction();

                // 6. Duyệt qua từng dòng dữ liệu trong tệp
                foreach ($rows as $rowIndex => $data) {
                    if (empty($data) || array_filter($data) === []) {
                        continue;
                    }

                    $lineNum = $rowIndex + 2; // Số dòng thực tế trong file Excel

                    // Nhập danh mục ngành học
                    if ($type === 'nganh_hoc' || $type === 'nganhhoc') {
                        $maNganh  = !empty($data[$maNganhIdx]) ? trim($data[$maNganhIdx]) : null;
                        $tenNganh = !empty($data[$tenNganhIdx]) ? trim($data[$tenNganhIdx]) : null;

                        if (empty($maNganh) || in_array(strtolower($maNganh), ['ma_nganh', 'manganh'])) continue;

                        if (empty($tenNganh)) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Tên ngành học bị trống [Mã: {$maNganh}].";
                            continue;
                        }

                        NganhHoc::updateOrCreate(['ma_nganh' => $maNganh], ['ten_nganh' => $tenNganh]);
                        $countSuccess++;
                    }

                    // Nhập danh mục học phần
                    elseif ($type === 'hoc_phan' || $type === 'hocphan') {
                        $maHocPhan  = !empty($data[$maHpIdx]) ? trim($data[$maHpIdx]) : null;
                        $tenHocPhan = !empty($data[$tenHpIdx]) ? trim($data[$tenHpIdx]) : null;

                        if (empty($maHocPhan) || in_array(strtolower($maHocPhan), ['ma_hoc_phan', 'mahocphan'])) continue;

                        if (empty($tenHocPhan)) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Tên học phần bị trống [Mã: {$maHocPhan}].";
                            continue;
                        }

                        $soTinChi = ($stcIdx !== null && isset($data[$stcIdx]) && is_numeric(trim($data[$stcIdx]))) ? (int)trim($data[$stcIdx]) : null;
                        $moTa     = ($moTaIdx !== null && isset($data[$moTaIdx])) ? trim($data[$moTaIdx]) : null;

                        HocPhan::updateOrCreate(
                            ['ma_hoc_phan' => $maHocPhan],
                            ['ten_hoc_phan' => $tenHocPhan, 'so_tin_chi' => $soTinChi, 'mo_ta' => $moTa]
                        );
                        $countSuccess++;
                    }

                    //  Nhập đề cương học phần
                    elseif ($type === 'de_cuong_hoc_phan' || $type === 'decuonghocphan') {
                        $idxNd = $ndDeCuongIdx ?? $moTaIdx;
                        $maHocPhan      = !empty($data[$maHpIdx]) ? trim($data[$maHpIdx]) : null;
                        $noiDungDeCuong = !empty($data[$idxNd]) ? trim($data[$idxNd]) : null;
                        $loaiTaiLieu    = ($loaiTlIdx !== null && !empty($data[$loaiTlIdx])) ? trim($data[$loaiTlIdx]) : 'Giáo trình chính';

                        if (empty($maHocPhan) || in_array(strtolower($maHocPhan), ['ma_hoc_phan', 'mahocphan'])) continue;

                        // Kiểm tra khóa tham chiếu (UC16)
                        if (!HocPhan::where('ma_hoc_phan', $maHocPhan)->exists()) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Mã học phần [{$maHocPhan}] không tồn tại trong CSDL.";
                            continue;
                        }

                        if (empty($noiDungDeCuong)) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Nội dung đề cương bị trống [Mã HP: {$maHocPhan}].";
                            continue;
                        }

                        DB::table('DeCuongHocPhan')->updateOrInsert(
                            ['ma_hoc_phan' => $maHocPhan],
                            ['noi_dung_de_cuong' => $noiDungDeCuong, 'loai_tai_lieu' => $loaiTaiLieu]
                        );
                        $countSuccess++;
                    }

                    // Nhập chương trình đào tạo
                    elseif ($type === 'chuong_trinh_dao_tao' || $type === 'chuongtrinhdaotao') {
                        $maNganh   = !empty($data[$maNganhIdx]) ? trim($data[$maNganhIdx]) : null;
                        $maHocPhan = !empty($data[$maHpIdx]) ? trim($data[$maHpIdx]) : null;

                        if (empty($maNganh) || in_array(strtolower($maNganh), ['ma_nganh', 'manganh'])) continue;

                        // Kiểm tra khóa tham chiếu (UC16)
                        if (!NganhHoc::where('ma_nganh', $maNganh)->exists()) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Mã ngành [{$maNganh}] chưa tồn tại trong CSDL.";
                            continue;
                        }

                        if (!HocPhan::where('ma_hoc_phan', $maHocPhan)->exists()) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Mã học phần [{$maHocPhan}] chưa tồn tại trong CSDL.";
                            continue;
                        }

                        DB::table('ChuongTrinhDaoTao')->updateOrInsert([
                            'ma_nganh'    => $maNganh,
                            'ma_hoc_phan' => $maHocPhan
                        ]);
                        $countSuccess++;
                    }

                    // Nhập hồ sơ sinh viên
                    elseif ($type === 'sinh_vien' || $type === 'sinhvien') {
                        $mssv    = !empty($data[$mssvIdx]) ? trim($data[$mssvIdx]) : null;
                        $maNganh = ($maNganhIdx !== null && !empty($data[$maNganhIdx])) ? trim($data[$maNganhIdx]) : null;

                        if (empty($mssv) || in_array(strtolower($mssv), ['mssv', 'masinhvien'])) continue;

                        // Kiểm tra khóa tham chiếu Ngành học (UC16)
                        if (!empty($maNganh) && !NganhHoc::where('ma_nganh', $maNganh)->exists()) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Sinh viên [{$mssv}] có Mã ngành [{$maNganh}] không tồn tại.";
                            continue;
                        }

                        $hoTen    = ($hoTenIdx !== null && !empty($data[$hoTenIdx])) ? trim($data[$hoTenIdx]) : 'Chưa cập nhật';
                        $ngaySinh = ($ngaySinhIdx !== null && !empty($data[$ngaySinhIdx])) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[$ngaySinhIdx]))) : null;
                        $gioiTinh = ($gioiTinhIdx !== null && !empty($data[$gioiTinhIdx])) ? trim($data[$gioiTinhIdx]) : 'Nam';
                        $sdt      = ($sdtIdx !== null && !empty($data[$sdtIdx])) ? trim($data[$sdtIdx]) : null;
                        $hanThe   = ($hanTheIdx !== null && !empty($data[$hanTheIdx])) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[$hanTheIdx]))) : '2027-06-30';
                        $maTk     = ($maTkIdx !== null && !empty($data[$maTkIdx])) ? trim($data[$maTkIdx]) : null;

                        SinhVien::updateOrCreate(
                            ['mssv' => $mssv],
                            [
                                'ho_ten'        => $hoTen,
                                'ngay_sinh'     => $ngaySinh,
                                'gioi_tinh'     => $gioiTinh,
                                'so_dien_thoai' => $sdt,
                                'han_the'       => $hanThe,
                                'ma_nganh'      => $maNganh,
                                'ma_tai_khoan'  => $maTk,
                            ]
                        );
                        $countSuccess++;
                    }

                    // Nhập thông tin học phần sinh viên
                    elseif ($type === 'sinh_vien_hoc_phan' || $type === 'sinhvienhocphan') {
                        $mssv      = !empty($data[$mssvIdx]) ? trim($data[$mssvIdx]) : null;
                        $maHocPhan = !empty($data[$maHpIdx]) ? trim($data[$maHpIdx]) : null;

                        if (empty($mssv) || in_array(strtolower($mssv), ['mssv', 'masinhvien'])) continue;

                        // Kiểm tra khóa tham chiếu MSSV và Mã học phần 
                        if (!SinhVien::where('mssv', $mssv)->exists()) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: MSSV [{$mssv}] chưa tồn tại trong CSDL.";
                            continue;
                        }

                        if (!HocPhan::where('ma_hoc_phan', $maHocPhan)->exists()) {
                            $countSkipped++;
                            $errors[] = "Dòng {$lineNum}: Mã học phần [{$maHocPhan}] chưa tồn tại trong CSDL.";
                            continue;
                        }

                        $trangThai = ($trangThaiIdx !== null && !empty($data[$trangThaiIdx])) ? trim($data[$trangThaiIdx]) : 'Đang học';

                        DB::table('SinhVienHocPhan')->updateOrInsert(
                            ['mssv' => $mssv, 'ma_hoc_phan' => $maHocPhan],
                            ['trang_thai' => $trangThai]
                        );
                        $countSuccess++;
                    }
                }

                DB::commit();

                // 7. Xử lý phản hồi thông báo kết quả chính xác cho View
                if ($countSuccess === 0 && $countSkipped === 0) {
                    return redirect()->back()->with('error', 'Không tìm thấy dữ liệu hợp lệ trong tệp!');
                }

                if ($countSuccess === 0 && $countSkipped > 0) {
                    $msg = "Nhập dữ liệu thất bại! Bỏ qua toàn bộ {$countSkipped} dòng lỗi. Chi tiết: " . implode(' | ', array_slice($errors, 0, 5));
                    return redirect()->back()->with('error', $msg);
                }

                $msg = "Nhập dữ liệu hoàn tất! Thành công: {$countSuccess} bản ghi.";
                if ($countSkipped > 0) {
                    $msg .= " Bỏ qua: {$countSkipped} dòng lỗi. Chi tiết: " . implode(' | ', array_slice($errors, 0, 5));
                }

                return redirect()->back()->with('success', $msg);

            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Lỗi xử lý tệp: ' . $e->getMessage());
            }
        }

    public function configView()
    {
        $config = CauHinhGoiY::orderBy('created_at', 'desc')->first() 
            ?? CauHinhGoiY::orderBy('ma_cau_hinh', 'desc')->first() 
            ?? new CauHinhGoiY(['top_n' => 10, 'nguong_cosine' => 0.15]);

        $activeModel = MoHinhGoiY::where('la_hien_hanh', 1)->first();

        return view('admin.system.config', compact('config', 'activeModel'));
    }

    public function saveConfig(Request $request)
    {
        $request->validate([
            'top_n'         => 'required|integer|min:1',
            'nguong_cosine' => 'required|numeric|between:0,1',
        ]);

        DB::beginTransaction();
        try {
            // Luôn sinh MÃ CẤU HÌNH MỚI và TẠO BẢN GHI MỚI để lưu lịch sử
            $maCauHinhMoi = 'CH_' . time();

            CauHinhGoiY::create([
                'ma_cau_hinh'  => $maCauHinhMoi,
                'ma_tai_khoan' => session('user_id'),
                'top_n'        => $request->top_n,
                'nguong_cosine'=> $request->nguong_cosine,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Thao tác xóa Cache bọc an toàn
            try {
                Cache::forget('app_latest_config');
            } catch (\Throwable $e) {
                // Bỏ qua nếu bảng Cache chưa sẵn sàng
            }

            DB::commit();

            return redirect()->back()->with('success', "Đã tạo cấu hình tham số mới [{$maCauHinhMoi}] thành công!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lưu cấu hình thất bại: ' . $e->getMessage());
        }
    }
    public function retrainModel()
    {
        MoHinhGoiY::where('la_hien_hanh', 1)->update(['la_hien_hanh' => 0]);

        MoHinhGoiY::create([
            'ma_mo_hinh'    => 'MH_' . time(),
            'ma_tai_khoan'  => session('user_id'),
            'ma_cau_hinh'   => 'CH_001',
            'thoi_diem_tao' => now(),
            'duong_dan_tep'  => 'models/tfidf_vectorizer_v' . time() . '.pkl',
            'la_hien_hanh'  => 1,
        ]);

        return redirect()->back()->with('success', 'Mô hình gợi ý đã được cập nhật thành công!');
    }

}