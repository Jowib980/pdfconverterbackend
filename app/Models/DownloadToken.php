<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadToken extends Model
{
    //
    protected $fillable = [
        'token',
        'files',
        'expires_at'
    ];
}
