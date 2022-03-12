<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_sold_goods_proof extends Model
{
    protected $table = 'umkm_sold_goods_proof';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_umkm',
        'file_name',
    ];
    public $timestamps = false;
    use HasFactory; 
}
