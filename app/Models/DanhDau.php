<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhDau extends Model
{
    protected $table = 'DanhDau';
    protected $primaryKey = 'ma_danh_dau';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ma_danh_dau',
        'mssv',
        'ma_tai_lieu',
        'thoi_diem_danh_dau'
    ];

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'mssv', 'mssv');
    }

    public function taiLieu()
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }
}