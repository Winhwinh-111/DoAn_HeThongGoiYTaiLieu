<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaiLieu;
use App\Models\MuonTra;
use App\Models\PhanHoi;
use App\Models\LichSuTimKiem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class AdminLibraryController extends Controller
{
    public function dashboard()
    {
        $totalBooks = TaiLieu::count();
        $totalBorrowing = MuonTra::where('trang_thai', 'DangMuon')->count();
        $totalFeedback = PhanHoi::count();
        $recentBorrows = MuonTra::with(['taiLieu', 'sinhVien'])->orderBy('ngay_muon', 'desc')->limit(5)->get();

        return view('admin.library.dashboard', compact('totalBooks', 'totalBorrowing', 'totalFeedback', 'recentBorrows'));
    }

    public function documentsIndex(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $query = TaiLieu::query();

        if (!empty($keyword)) {
            $query->where('nhan_de', 'LIKE', "%{$keyword}%")
                  ->orWhere('tac_gia', 'LIKE', "%{$keyword}%")
                  ->orWhere('ma_tai_lieu', 'LIKE', "%{$keyword}%");
        }

        $documents = $query->orderBy('ma_tai_lieu', 'desc')->paginate(10);
        return view('admin.library.documents', compact('documents', 'keyword'));
    }

    public function documentsStore(Request $request)
    {
        $request->validate([
            'ma_tai_lieu' => 'required|unique:TaiLieu,ma_tai_lieu',
            'nhan_de'     => 'required',
        ]);

        TaiLieu::create([
            'ma_tai_lieu'        => $request->ma_tai_lieu,
            'nhan_de'            => $request->nhan_de,
            'tac_gia'            => $request->tac_gia,
            'nha_xuat_ban'       => $request->nha_xuat_ban,
            'nam_xuat_ban'       => $request->nam_xuat_ban ?? date('Y'),
            'ngon_ngu'           => $request->ngon_ngu ?? 'Tiếng Việt',
            'isbn'               => $request->isbn,
            'thuat_ngu_chu_de'   => $request->thuat_ngu_chu_de,
            'mo_ta'              => $request->mo_ta,
            'noi_luu_giu'        => $request->noi_luu_giu ?? 'Kho tầng 1',
            'trang_thai_su_dung' => $request->trang_thai_su_dung ?? 'Co_San',
            'anh_bia'            => $request->anh_bia,
        ]);

        return redirect()->back()->with('success', 'Thêm mới tài liệu thành công!');
    }

    public function documentsUpdate(Request $request, $id)
    {
        $doc = TaiLieu::findOrFail($id);
        $doc->update($request->all());
        return redirect()->back()->with('success', 'Cập nhật thông tin tài liệu thành công!');
    }

    public function documentsDelete($id)
    {
        $doc = TaiLieu::findOrFail($id);
        $doc->delete();
        return redirect()->back()->with('success', 'Xóa tài liệu thành công!');
    }

    // Nhập tệp TaiLieu 
    public function documentsImport(Request $request)
{
    $request->validate([
        'file_import' => 'required|file|mimes:csv,txt,xlsx,xls|max:5000',
    ], [
        'file_import.required' => 'Vui lòng chọn tệp dữ liệu!',
        'file_import.mimes'    => 'Hệ thống chỉ hỗ trợ định dạng tệp .csv, .xlsx, .xls!',
    ]);

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

        // Chuẩn hóa chuỗi phục vụ tìm tiêu đề cột
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

        // Quét tìm chỉ số vị trí của từng cột
        $maDocIdx    = $findColIdx(['matailieu', 'matl', 'masach'], 0);
        $nhanDeIdx   = $findColIdx(['nhande', 'tensach', 'tentailieu'], 1);
        $tacGiaIdx   = $findColIdx(['tacgia'], 2);
        $nxbIdx      = $findColIdx(['nhaxuatban', 'nxb'], 3);
        $namXbIdx    = $findColIdx(['namxuatban', 'namxb', 'nam'], 4);
        $ngonNguIdx  = $findColIdx(['ngonngu', 'lang'], 5);
        $isbnIdx     = $findColIdx(['isbn'], 6);
        $chuDeIdx    = $findColIdx(['thuatnguchude', 'chude', 'thuatngu'], 7);
        $moTaIdx     = $findColIdx(['mota', 'tomtat'], 8);
        $noiLuuIdx   = $findColIdx(['noiluugiu', 'vitri', 'kho'], 9);
        $ttSudungIdx = $findColIdx(['trangthaisudung', 'trangthai'], 10);
        $anhBiaIdx   = $findColIdx(['anhbia', 'hinhbia', 'url', 'cover'], 11);

        $getValue = function($row, $idx) {
            if ($idx === null || !isset($row[$idx])) return null;
            $val = $row[$idx];
            if (is_array($val)) $val = implode(', ', $val);
            $val = preg_replace('/[\[\]\']/', '', (string)$val);
            return trim($val);
        };

        $safeTruncate = function($str, $limit = 250) {
            if (empty($str)) return null;
            return (mb_strlen($str, 'UTF-8') > $limit) ? mb_substr($str, 0, $limit - 3, 'UTF-8') . '...' : $str;
        };

        $countSuccess = 0;
        $countSkipped = 0;

        DB::beginTransaction();

        foreach ($rows as $data) {
            // 1. Bỏ qua các dòng trống hoàn toàn
            if (empty($data) || array_filter($data) === []) {
                continue;
            }

            // Xử lý an toàn cho từng dòng dữ liệu lẻ
            try {
                $maTaiLieu = $getValue($data, $maDocIdx);

                // Bỏ qua dòng tiêu đề trùng lặp trong file hoặc mã trống
                if (empty($maTaiLieu) || in_array(strtolower($maTaiLieu), ['ma_tai_lieu', 'matailieu'])) {
                    $countSkipped++;
                    continue;
                }

                $safeMaTL = $safeTruncate($maTaiLieu, 50);

                // 2. BỎ QUA DÒNG TRÙNG LẶP: Nếu mã tài liệu đã có trong CSDL thì bỏ qua
                $isExist = TaiLieu::where('ma_tai_lieu', $safeMaTL)->exists();
                if ($isExist) {
                    $countSkipped++;
                    continue;
                }

                $nhanDe          = $getValue($data, $nhanDeIdx) ?? 'Chưa có nhan đề';
                $tacGia          = $safeTruncate($getValue($data, $tacGiaIdx), 250);
                $nhaXuatBan      = $safeTruncate($getValue($data, $nxbIdx), 250);
                $namXbVal        = $getValue($data, $namXbIdx);
                $namXuatBan      = (is_numeric($namXbVal)) ? (int)$namXbVal : (int)date('Y');
                $ngonNgu         = $safeTruncate($getValue($data, $ngonNguIdx) ?? 'Tiếng Việt', 50);
                $isbn            = $safeTruncate($getValue($data, $isbnIdx), 50);
                $thuatNguChuDe   = $getValue($data, $chuDeIdx);
                $moTa            = $getValue($data, $moTaIdx);
                
                // Trích xuất vị trí kho
                $rawNoiLuu = $getValue($data, $noiLuuIdx);
                if (!empty($rawNoiLuu) && str_contains($rawNoiLuu, '|')) {
                    $parts = explode('|', $rawNoiLuu);
                    $noiLuuGiu = trim(end($parts));
                } else {
                    $noiLuuGiu = $rawNoiLuu;
                }
                $noiLuuGiu = $safeTruncate($noiLuuGiu ?? 'Kho tầng 1', 250);

                $trangThaiSuDung = $getValue($data, $ttSudungIdx) ?? 'Co_San';
                $anhBia          = $getValue($data, $anhBiaIdx);

                // Đảo vị trí nếu trạng thái chứa URL
                if (!empty($trangThaiSuDung) && (str_starts_with($trangThaiSuDung, 'http://') || str_starts_with($trangThaiSuDung, 'https://'))) {
                    $tempUrl = $trangThaiSuDung;
                    $trangThaiSuDung = (!empty($anhBia) && !str_starts_with($anhBia, 'http')) ? $anhBia : 'Co_San';
                    $anhBia = $tempUrl;
                }

                $trangThaiSuDung = $safeTruncate($trangThaiSuDung, 30) ?? 'Co_San';

                // Thêm mới bản ghi hợp lệ
                TaiLieu::create([
                    'ma_tai_lieu'        => $safeMaTL,
                    'nhan_de'            => $nhanDe,
                    'tac_gia'            => $tacGia,
                    'nha_xuat_ban'       => $nhaXuatBan,
                    'nam_xuat_ban'       => $namXuatBan,
                    'ngon_ngu'           => $ngonNgu,
                    'isbn'               => $isbn,
                    'thuat_ngu_chu_de'   => $thuatNguChuDe,
                    'mo_ta'              => $moTa,
                    'noi_luu_giu'        => $noiLuuGiu,
                    'trang_thai_su_dung' => $trangThaiSuDung,
                    'anh_bia'            => $anhBia,
                ]);

                $countSuccess++;

            } catch (\Exception $rowError) {
                // 3. BỎ QUA DÒNG LỖI: Bỏ qua dòng bị lỗi và tiếp tục dòng kế tiếp
                $countSkipped++;
                continue;
            }
        }

        DB::commit();

        return redirect()->back()->with('success', "Xử lý tệp hoàn tất: Đã nhập thành công {$countSuccess} bản ghi mới, bỏ qua {$countSkipped} dòng (bị trùng hoặc lỗi dữ liệu)!");

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Lỗi xử lý tệp: ' . $e->getMessage());
    }
}

    public function borrowIndex()
    {
        $borrowList = MuonTra::with(['taiLieu', 'sinhVien'])->orderBy('ngay_muon', 'desc')->paginate(10);
        return view('admin.library.borrow', compact('borrowList'));
    }

    public function borrowStore(Request $request)
    {
        $request->validate([
            'mssv'        => 'required',
            'ma_tai_lieu' => 'required',
        ]);

        MuonTra::create([
            'ma_giao_dich' => 'GD_' . time(),
            'mssv'         => $request->mssv,
            'ma_tai_lieu'   => $request->ma_tai_lieu,
            'ngay_muon'    => now(),
            'trang_thai'   => 'DangMuon',
            'ghi_chu'      => $request->ghi_chu
        ]);

        return redirect()->back()->with('success', 'Tạo phiếu mượn tài liệu thành công!');
    }

    public function borrowReturnStatus($id)
    {
        $borrow = MuonTra::findOrFail($id);
        $borrow->update([
            'ngay_tra'   => now(),
            'trang_thai' => 'DaTra'
        ]);
        return redirect()->back()->with('success', 'Đã cập nhật trạng thái trả sách thành công!');
    }

    // Nhập tệp MuonTra (ma_giao_dich, mssv, ma_tai_lieu, ngay_muon, ngay_tra, trang_thai, ghi_chu)
    public function borrowImport(Request $request)
    {
        $request->validate([
            'file_import' => 'required|file|mimes:csv,txt,xlsx,xls|max:5000',
        ]);

        try {
            $file = $request->file('file_import');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            array_shift($rows);

            $count = 0;
            foreach ($rows as $data) {
                if (!empty($data[0]) && !empty($data[1])) {
                    MuonTra::updateOrCreate(
                        ['ma_giao_dich' => trim($data[0])],
                        [
                            'mssv'        => trim($data[1]),
                            'ma_tai_lieu' => trim($data[2]),
                            'ngay_muon'   => $data[3] ?? now(),
                            'ngay_tra'    => !empty($data[4]) ? $data[4] : null,
                            'trang_thai'  => $data[5] ?? 'DangMuon',
                            'ghi_chu'     => $data[6] ?? null,
                        ]
                    );
                    $count++;
                }
            }
            return redirect()->back()->with('success', "Đã trích xuất và cập nhật thành công {$count} bản ghi mượn trả vào CSDL!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi xử lý tệp: ' . $e->getMessage());
        }
    }

    public function reports()
    {
        $totalFeedback = PhanHoi::count();
        $goodFeedback = PhanHoi::where('muc_danh_gia', 'PhuHop')->count();
        $badFeedback = PhanHoi::where('muc_danh_gia', 'KhongPhuHop')->count();

        $topKeywords = LichSuTimKiem::select('tu_khoa', DB::raw('count(*) as count'))
            ->groupBy('tu_khoa')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.library.reports', compact('totalFeedback', 'goodFeedback', 'badFeedback', 'topKeywords'));
    }
}