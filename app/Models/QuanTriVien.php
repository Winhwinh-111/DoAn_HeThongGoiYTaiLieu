<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuanTriVien extends Model
{
    protected $table = 'QuanTriVien';
    protected $primaryKey = 'ma_quan_tri';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['ma_quan_tri', 'ho_ten', 'email', 'so_dien_thoai', 'chuc_vu', 'ma_tai_khoan'];

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'ma_tai_khoan', 'ma_tai_khoan');
    }
}