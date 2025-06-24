<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\DownloadToken;

class ConvertedDocuments extends Model
{
    //

    protected $fillable = [
        'user_id',
        'file_type',
        'convert_into',
        'original_name',
        'converted_name',
        'original_doc',
        'converted_pdf',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function downloadToken()
    {
        return $this->hasOne(DownloadToken::class, 'converted_document_id');
    }

}
