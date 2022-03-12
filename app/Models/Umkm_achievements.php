<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_achievements extends Model
{
    protected $table = 'umkm_achievements';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_umkm',
        'file_name',
    ];
    public $timestamps = false;
    use HasFactory;
}
