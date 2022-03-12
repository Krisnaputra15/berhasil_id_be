<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_posts extends Model
{
    protected $table = 'umkm_posts';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_umkm',
        'title',
        'post_desc',
        'like_count',
        'created_at',
    ];
    public $timestamps = false;
    use HasFactory;
}
