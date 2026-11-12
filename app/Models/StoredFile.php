<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoredFile extends Model
{
    public const DISK = 'documents';

    protected $fillable = ['disk', 'path', 'mime_type', 'size'];

    protected $hidden = ['disk', 'path'];
}
