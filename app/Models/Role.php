<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const STUDENT = 'student';

    public const TEACHER = 'teacher';

    public const ADMINISTRATOR = 'administrator';

    protected $fillable = ['name', 'description'];

    public function abilities(): HasMany
    {
        return $this->hasMany(RoleAbility::class);
    }
}
