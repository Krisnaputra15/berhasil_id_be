<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_details extends Model
{
    protected $table = 'umkm_details';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_user',
        'umkm_name',
        'umkm_desc',
        'umkm_address',
        'umkm_field',
        'umkm_monthly_revenue',
        'umkm_years_experience',
        'sold_goods_quantity',
        'last_make_ad',
    ];
    public $timestamps = false;
    use HasFactory;
}
