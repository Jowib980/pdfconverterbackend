<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DownloadToken;
use App\Models\ConvertedDocuments;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

     public function index()
    {
        $user_count = User::count();
        $total_file_conversion_count = ConvertedDocuments::count();
        $user_file_conversion_count = ConvertedDocuments::where('user_id', Auth::id())->count();

        return view('dashboard', [
            'users' => $user_count,
            'conversion' => $total_file_conversion_count,
            'user_conversion' => $user_file_conversion_count,
        ]);
    }
}
