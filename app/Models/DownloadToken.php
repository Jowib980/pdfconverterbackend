<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ConvertedDocuments;

class DownloadToken extends Model
{
    //
    protected $fillable = [
        'converted_document_id',
        'token',
        'files',
        'expires_at'
    ];


    public function document()
    {
        return $this->belongsTo(ConvertedDocuments::class);
    }
}
