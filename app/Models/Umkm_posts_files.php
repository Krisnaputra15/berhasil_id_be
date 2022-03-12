<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_posts_files extends Model
{
    protected $table = 'umkm_post_files';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_post',
        'file_name',
    ];
    public $timestamps = false;
    use HasFactory;
}
