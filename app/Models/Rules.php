<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rules extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'upload_id',
        'attributes',
        'authority',
        'granted_to',
        'verified'
    ];
}
