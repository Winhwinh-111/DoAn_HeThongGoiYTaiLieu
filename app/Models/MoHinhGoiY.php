<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoHinhGoiY extends Model
{
    protected $table = 'MoHinhGoiY';
    protected $primaryKey = 'ma_mo_hinh';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ma_mo_hinh',
        'ma_tai_khoan',
        'ma_cau_hinh',
        'thoi_diem_tao',
        'duong_dan_tep',
        'la_hien_hanh'
    ];

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'ma_tai_khoan', 'ma_tai_khoan');
    }

    public function cauHinhGoiY()
    {
        return $this->belongsTo(CauHinhGoiY::class, 'ma_cau_hinh', 'ma_cau_hinh');
    }
}