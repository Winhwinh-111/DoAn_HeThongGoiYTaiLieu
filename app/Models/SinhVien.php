<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SinhVien extends Model
{
    protected $table = 'SinhVien';
    protected $primaryKey = 'mssv';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['mssv', 'ho_ten', 'ngay_sinh', 'gioi_tinh', 'so_dien_thoai', 'han_the', 'ma_nganh', 'ma_tai_khoan'];

    public function hocPhans()
    {
        return $this->belongsToMany(HocPhan::class, 'SinhVienHocPhan', 'mssv', 'ma_hoc_phan')
                    ->withPivot('trang_thai');
    }

    public function nganh()
    {
        return $this->belongsTo(NganhHoc::class, 'ma_nganh', 'ma_nganh');
    }
}