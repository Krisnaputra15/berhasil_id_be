<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm_investasi extends Model
{
    protected $table = 'umkm_investasi';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_umkm',
        'id_investor',
        'proposer',
        'status',
    ];
    public $timestamps = false;
    use HasFactory;
}
