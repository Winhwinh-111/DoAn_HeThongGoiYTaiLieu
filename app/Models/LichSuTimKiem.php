<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuTimKiem extends Model
{
    protected $table = 'LichSuTimKiem';
    protected $primaryKey = 'ma_tim_kiem';
    public $incrementing = false; // Do ma_tim_kiem là chuỗi varchar(30)
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ma_tim_kiem',
        'mssv',
        'tu_khoa',
        'thoi_diem_tim_kiem'
    ];
}