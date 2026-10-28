<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TaiKhoan extends Authenticatable
{
    protected $table = 'TaiKhoan';
    protected $primaryKey = 'ma_tai_khoan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['ma_tai_khoan', 'ten_dang_nhap', 'mat_khau', 'vai_tro', 'ngay_tao'];
    protected $hidden = ['mat_khau'];

    public function sinhVien()
    {
        return $this->hasOne(SinhVien::class, 'ma_tai_khoan', 'ma_tai_khoan');
    }
}