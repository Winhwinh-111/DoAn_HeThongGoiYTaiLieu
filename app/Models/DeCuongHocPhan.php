<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeCuongHocPhan extends Model
{
    protected $table = 'DeCuongHocPhan';
    public $timestamps = false;
    protected $fillable = ['ma_hoc_phan', 'ma_tai_lieu', 'noi_dung_de_cuong', 'loai_tai_lieu'];
}