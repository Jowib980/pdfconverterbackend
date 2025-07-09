<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ConvertedDocuments;
use Illuminate\Support\Facades\Auth;

class ConversionController extends Controller
{
    //

    public function index(Request $request)
    {
        $query = ConvertedDocuments::with(['user']);

        // If not admin, limit to own files
        if (!Auth::user()->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        }

        // Admin can filter by user name
        if ($request->filled('name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->filled('file_type')) {
            $type = $request->file_type;
            $query->where(function ($q) use ($type) {
                $q->where('file_type', $type)
                  ->orWhere('file_type', rtrim($type, 's'));
            });
        }

        $files = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('admin.converted_files.index', compact('files'));
    }


    public function destroy(Request $request, $id) 
    {
        $data = ConvertedDocuments::find($id);

        if (!$data) {
            return redirect()->back()->with('error', 'Data not found!');
        }

        $data->delete();
            
        return redirect()->back()->with('message', 'Delete data successfully!');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            ConvertedDocuments::whereIn('id', $ids)->delete();
            return redirect()->back()->with('message', 'Selected files deleted.');
        }

        return redirect()->back()->with('error', 'No files selected.');
    }


}
