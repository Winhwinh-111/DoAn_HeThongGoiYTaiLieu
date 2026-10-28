<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhanHoi extends Model
{
    protected $table = 'PhanHoi';
    protected $primaryKey = 'ma_phan_hoi';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ma_phan_hoi',
        'mssv',
        'ma_tai_lieu',
        'muc_danh_gia',
        'thoi_diem'
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