<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CauHinhGoiY extends Model
{
    protected $table = 'CauHinhGoiY';
    protected $primaryKey = 'ma_cau_hinh';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'ma_cau_hinh',
        'ma_tai_khoan',
        'top_n',
        'nguong_cosine',
        'created_at',
        'updated_at'
    ];

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'ma_tai_khoan', 'ma_tai_khoan');
    }

    public function moHinhGoiYs()
    {
        return $this->hasMany(MoHinhGoiY::class, 'ma_cau_hinh', 'ma_cau_hinh');
    }
}