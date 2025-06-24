<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DownloadToken;

class DashboardController extends Controller
{

     public function index()
    {
        $user_count = User::count();
        $total_file_conversion_count = DownloadToken::count();

        return view('dashboard', [
            'users' => $user_count,
            'conversion' => $total_file_conversion_count,
        ]);
    }
}
