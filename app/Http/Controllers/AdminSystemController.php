<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CauHinhGoiY;
use App\Models\MoHinhGoiY;
use App\Models\SinhVien;
use App\Models\HocPhan;
use App\Models\NganhHoc;
use App\Models\MuonTra;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class AdminSystemController extends Controller
{
    public function dashboard()
    {
        // Truy xuất toàn bộ dữ liệu thống kê từ CSDL
        $studentCount = SinhVien::count();
        $courseCount = HocPhan::count();
        $majorCount = NganhHoc::count();
        $borrowCount = MuonTra::count();
        
        $activeModel = MoHinhGoiY::where('la_hien_hanh', 1)->first();
        $totalModels = MoHinhGoiY::count();

        // Trả về view tổng quan (đóng vai trò là trang thống kê)
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
            $file = $request->file('file_import');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                return redirect()->back()->with('error', 'Tệp dữ liệu rỗng!');
            }

            // Lấy dòng tiêu đề đầu tiên (Header)
            $headerRow = array_shift($rows);

            // Hàm chuẩn hóa chuỗi phục vụ so khớp tiêu đề cột
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

            // Tìm vị trí cột dựa trên từ khóa trong tiêu đề
            $findColIdx = function($keywords, $defaultIdx = null) use ($headerRow, $normalize) {
                foreach ($headerRow as $idx => $cellText) {
                    $normText = $normalize($cellText);
                    foreach ($keywords as $kw) {
                        if (!empty($normText) && str_contains($normText, $kw)) {
                            return $idx;
                        }
                    }
                }
                return $defaultIdx;
            };

            // Tự động quét và tìm vị trí chính xác của từng cột
            $maHpIdx     = $findColIdx(['mahocphan', 'mamonhoc', 'mahp', 'mamon']);
            $tenHpIdx    = $findColIdx(['tenhocphan', 'tenmonhoc', 'tenhp', 'tenmon']);
            $stcIdx      = $findColIdx(['sotinchi', 'stc', 'sotc', 'tinchi']);
            $moTaIdx     = $findColIdx(['mota', 'ghichu', 'noidung']);
            
            $maNganhIdx  = $findColIdx(['manganh', 'machuyennganh', 'manch']);
            $tenNganhIdx = $findColIdx(['tennganh', 'tenchuyennganh', 'tench']);

            $mssvIdx     = $findColIdx(['mssv', 'masinhvien', 'masv', 'mathe']);
            $hoTenIdx    = $findColIdx(['hoten', 'tensinhvien', 'tensv', 'hoandten']);
            $ngaySinhIdx = $findColIdx(['ngaysinh', 'ns']);
            $gioiTinhIdx = $findColIdx(['gioitinh', 'phai']);
            $sdtIdx      = $findColIdx(['sodienthoai', 'sdt', 'dienthoai', 'phone']);
            $hanTheIdx   = $findColIdx(['hanthe', 'ngayhethan']);
            $maTkIdx     = $findColIdx(['mataikhoan', 'matk', 'taikhoan']);

            $count = 0;

            DB::beginTransaction();

            foreach ($rows as $data) {
                if (empty($data) || array_filter($data) === []) {
                    continue;
                }

                // 1. NHẬP HỌC PHẦN (HocPhan)
                if ($type === 'hoc_phan' || $type === 'hocphan') {
                    $idxMaHp  = $maHpIdx ?? 0;
                    $idxTenHp = $tenHpIdx ?? 1;
                    $idxStc   = $stcIdx ?? 2;

                    $maHocPhan  = !empty($data[$idxMaHp]) ? trim($data[$idxMaHp]) : null;
                    $tenHocPhan = !empty($data[$idxTenHp]) ? trim($data[$idxTenHp]) : 'Chưa có tên học phần';
                    
                    // Lấy chính xác giá trị từ file, nếu rỗng hoặc không phải số thì gán null
                    $soTinChi = (isset($data[$idxStc]) && trim($data[$idxStc]) !== '' && is_numeric(trim($data[$idxStc])))
                        ? (int)trim($data[$idxStc])
                        : null;

                    // Mô tả: Chỉ lấy giá trị khi tệp thực sự có cột mô tả
                    $moTa = ($moTaIdx !== null && isset($data[$moTaIdx])) ? trim($data[$moTaIdx]) : null;

                    if (empty($maHocPhan) || in_array(strtolower($maHocPhan), ['ma_hoc_phan', 'mahocphan', 'ma_nganh', 'manganh'])) {
                        continue;
                    }

                    HocPhan::updateOrCreate(
                        ['ma_hoc_phan' => $maHocPhan],
                        [
                            'ten_hoc_phan' => $tenHocPhan,
                            'so_tin_chi'   => $soTinChi, 
                            'mo_ta'        => $moTa,
                        ]
                    );
                    $count++;

                    // TỰ ĐỘNG XỬ LÝ NẾU TỆP EXCEL CÓ CẢ CỘT MÃ NGÀNH VÀ TÊN NGÀNH
                    if ($maNganhIdx !== null && !empty($data[$maNganhIdx])) {
                        $maNganhVal  = trim($data[$maNganhIdx]);
                        $tenNganhVal = ($tenNganhIdx !== null && !empty($data[$tenNganhIdx])) ? trim($data[$tenNganhIdx]) : null;

                        if (!in_array(strtolower($maNganhVal), ['ma_nganh', 'manganh'])) {
                            // Tự động lưu/cập nhật Ngành học
                            NganhHoc::updateOrCreate(
                                ['ma_nganh' => $maNganhVal],
                                [
                                    'ten_nganh' => $tenNganhVal ?? 'NGÀNH HỌC TỰ ĐỘNG'
                                ]
                            );

                            // Tự động gán vào Khung chương trình đào tạo
                            DB::table('ChuongTrinhDaoTao')->updateOrInsert([
                                'ma_nganh'    => $maNganhVal,
                                'ma_hoc_phan' => $maHocPhan,
                            ]);
                        }
                    }
                }
                // 2. NHẬP NGÀNH HỌC (NganhHoc)
                elseif ($type === 'nganh_hoc' || $type === 'nganhhoc') {
                    $idxMaNg  = $maNganhIdx ?? 0;
                    $idxTenNg = $tenNganhIdx ?? 1;

                    $maNganh  = !empty($data[$idxMaNg]) ? trim($data[$idxMaNg]) : null;
                    $tenNganh = !empty($data[$idxTenNg]) ? trim($data[$idxTenNg]) : 'Chưa có tên ngành';

                    if (empty($maNganh) || in_array(strtolower($maNganh), ['ma_nganh', 'manganh'])) {
                        continue;
                    }

                    NganhHoc::updateOrCreate(
                        ['ma_nganh' => $maNganh],
                        [
                            'ten_nganh' => $tenNganh,
                        ]
                    );
                    $count++;
                }
                // 3. NHẬP SINH VIÊN (SinhVien)
                elseif ($type === 'sinh_vien' || $type === 'sinhvien') {
                    $idxMssv  = $mssvIdx ?? 0;
                    $idxHoTen = $hoTenIdx ?? 1;
                    $idxNS    = $ngaySinhIdx ?? 2;
                    $idxGT    = $gioiTinhIdx ?? 3;
                    $idxSdt   = $sdtIdx ?? 4;
                    $idxHT    = $hanTheIdx ?? 5;
                    $idxMNg   = $maNganhIdx ?? 6;
                    $idxMTk   = $maTkIdx ?? 7;

                    $mssv = !empty($data[$idxMssv]) ? trim($data[$idxMssv]) : null;

                    if (empty($mssv) || in_array(strtolower($mssv), ['mssv', 'masinhvien'])) {
                        continue;
                    }

                    $ngaySinh = !empty($data[$idxNS]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[$idxNS]))) : null;
                    $hanThe   = !empty($data[$idxHT]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[$idxHT]))) : '2027-06-30';

                    SinhVien::updateOrCreate(
                        ['mssv' => $mssv],
                        [
                            'ho_ten'        => $data[$idxHoTen] ?? 'Chưa cập nhật',
                            'ngay_sinh'     => $ngaySinh,
                            'gioi_tinh'     => $data[$idxGT] ?? 'Nam',
                            'so_dien_thoai' => $data[$idxSdt] ?? null,
                            'han_the'       => $hanThe,
                            'ma_nganh'      => !empty($data[$idxMNg]) ? trim($data[$idxMNg]) : null,
                            'ma_tai_khoan'  => !empty($data[$idxMTk]) ? trim($data[$idxMTk]) : null,
                        ]
                    );
                    $count++;
                }
                // 4. NHẬP KHUNG CHƯƠNG TRÌNH ĐÀO TẠO
                elseif ($type === 'chuong_trinh_dao_tao' || $type === 'chuongtrinhdaotao') {
                    $idxMaNg = $maNganhIdx ?? 0;
                    $idxMaHp = $maHpIdx ?? 1;

                    $maNganh   = !empty($data[$idxMaNg]) ? trim($data[$idxMaNg]) : null;
                    $maHocPhan = !empty($data[$idxMaHp]) ? trim($data[$idxMaHp]) : null;

                    if (!empty($maNganh) && !empty($maHocPhan) && !in_array(strtolower($maNganh), ['ma_nganh', 'manganh'])) {
                        DB::table('ChuongTrinhDaoTao')->updateOrInsert(
                            [
                                'ma_nganh'    => $maNganh,
                                'ma_hoc_phan' => $maHocPhan
                            ]
                        );
                        $count++;
                    }
                }
            }

            DB::commit();

            if ($count === 0) {
                return redirect()->back()->with('error', 'Không tìm thấy dữ liệu hợp lệ trong tệp! Kiểm tra lại định dạng tệp hoặc loại dữ liệu đã chọn.');
            }

            return redirect()->back()->with('success', "Đã trích xuất và cập nhật thành công {$count} bản ghi loại [{$type}] vào CSDL!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi xử lý tệp: ' . $e->getMessage());
        }
    }

    public function configView()
    {
        $config = CauHinhGoiY::first() ?? new CauHinhGoiY(['top_n' => 10, 'nguong_cosine' => 0.15]);
        $activeModel = MoHinhGoiY::where('la_hien_hanh', 1)->first();

        return view('admin.system.config', compact('config', 'activeModel'));
    }

    public function saveConfig(Request $request)
    {
        $request->validate([
            'top_n'         => 'required|integer|min:1',
            'nguong_cosine' => 'required|numeric|between:0,1',
        ]);

        $config = CauHinhGoiY::first();
        if ($config) {
            $config->update($request->all());
        } else {
            CauHinhGoiY::create([
                'ma_cau_hinh'  => 'CH_001',
                'ma_tai_khoan' => session('user_id'),
                'top_n'        => $request->top_n,
                'nguong_cosine'=> $request->nguong_cosine
            ]);
        }

        return redirect()->back()->with('success', 'Lưu cấu hình tham số gợi ý thành công!');
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

    public function reports()
    {
        return view('admin.system.reports');
    }
}