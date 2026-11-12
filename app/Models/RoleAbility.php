<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleAbility extends Model
{
    public $timestamps = false;

    protected $fillable = ['ability'];
}
