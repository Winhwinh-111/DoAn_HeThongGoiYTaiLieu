<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaiLieu extends Model
{
    protected $table = 'TaiLieu';
    protected $primaryKey = 'ma_tai_lieu';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ma_tai_lieu', 'nhan_de', 'tac_gia', 'nha_xuat_ban', 
        'nam_xuat_ban', 'ngon_ngu', 'isbn', 'thuat_ngu_chu_de', 
        'mo_ta', 'noi_luu_giu', 'trang_thai_su_dung', 'anh_bia'
    ];
}