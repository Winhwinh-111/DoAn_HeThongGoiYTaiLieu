<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MuonTra extends Model
{
    protected $table = 'MuonTra';
    protected $primaryKey = 'ma_giao_dich';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ma_giao_dich', 
        'mssv', 
        'ma_tai_lieu', 
        'ngay_muon', 
        'ngay_tra', 
        'trang_thai', 
        'ghi_chu'
    ];

    // Quan hệ lấy thông tin tài liệu
    public function taiLieu()
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }

    // Quan hệ lấy thông tin sinh viên
    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'mssv', 'mssv');
    }
}