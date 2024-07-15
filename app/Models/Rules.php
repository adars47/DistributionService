<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rules extends Model
{
    use HasFactory;
    public $timestamps = false;

    public $id;
    public $uploadId;
    public $attribute;
    public $authority;
    public $satisfied;
}
