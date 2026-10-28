<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaiKhoan;
use App\Models\SinhVien;
use App\Models\QuanTriVien;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'ten_dang_nhap' => 'required',
            'mat_khau' => 'required',
        ]);

        $hashedInput = hash('sha256', $request->mat_khau);

        $user = TaiKhoan::where('ten_dang_nhap', $request->ten_dang_nhap)
            ->where('mat_khau', $hashedInput)
            ->first();

        if ($user) {
            Session::put('user_id', $user->ma_tai_khoan);
            Session::put('username', $user->ten_dang_nhap);
            Session::put('role', $user->vai_tro);

            // Phân luồng Session và chuyển hướng theo Vai trò
            if ($user->vai_tro === 'SinhVien') {
                $sv = SinhVien::where('ma_tai_khoan', $user->ma_tai_khoan)->first();
                Session::put('mssv', $sv ? $sv->mssv : $user->ten_dang_nhap);
                Session::put('student_name', $sv ? $sv->ho_ten : 'Sinh viên');
                return redirect()->route('home');
            } 
            elseif ($user->vai_tro === 'QuanTriThuVien') {
                $qtv = QuanTriVien::where('ma_tai_khoan', $user->ma_tai_khoan)->first();
                Session::put('admin_name', $qtv ? $qtv->ho_ten : 'Thủ thư');
                return redirect()->route('admin.library.dashboard');
            } 
            elseif ($user->vai_tro === 'QuanTriHeThong') {
                $qtv = QuanTriVien::where('ma_tai_khoan', $user->ma_tai_khoan)->first();
                Session::put('admin_name', $qtv ? $qtv->ho_ten : 'Quản trị hệ thống');
                return redirect()->route('admin.system.dashboard');
            }
        }

        return redirect()->route('home')
            ->withErrors(['error' => 'Tên đăng nhập hoặc mật khẩu không chính xác!'])
            ->with('show_login_modal', true);
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('home');
    }
}