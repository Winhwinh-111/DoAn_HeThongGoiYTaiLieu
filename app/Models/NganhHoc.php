<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NganhHoc extends Model
{
    protected $table = 'NganhHoc';
    protected $primaryKey = 'ma_nganh';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];

    public function hocPhans()
    {
        return $this->belongsToMany(HocPhan::class, 'ChuongTrinhDaoTao', 'ma_nganh', 'ma_hoc_phan');
    }
}