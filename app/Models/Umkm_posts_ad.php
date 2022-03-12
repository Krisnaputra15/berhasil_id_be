<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_posts_ad extends Model
{
    protected $table = 'umkm_post_ads';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_umkm',
        'id_post',
        'start_date',
        'end_date',
        'premium_status',
    ];
    public $timestamps = false;
    use HasFactory;
}
