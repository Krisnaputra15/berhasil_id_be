<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_posts_comments extends Model
{
    protected $table = 'umkm_post_comments';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_post',
        'id_user',
        'comment',
        'replied_to',
        'created_at',
    ];
    public $timestamps = false;
    use HasFactory;
}
