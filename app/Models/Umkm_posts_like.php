<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_posts_like extends Model
{
    protected $table = 'umkm_post_likes';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_post',
        'id_user',
    ];
    public $timestamps = false;
    use HasFactory;
}
