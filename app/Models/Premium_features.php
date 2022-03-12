<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Premium_features extends Model
{
    protected $table = 'premium_features';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_pack',
        'feature'
    ];
    public $timestamps = false;
    use HasFactory;
}
