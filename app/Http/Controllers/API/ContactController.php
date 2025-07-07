<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contacts;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;
use App\Models\User;

class ContactController extends Controller
{
    public function index() 
    {
        $contacts = Contacts::orderByDesc('created_at')->paginate(10);

        return view('admin.contacts.index', compact('contacts'));
    }

    public function create(Request $request) 
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'user_id' => 'nullable|integer',
        ]);

        $contact = new Contacts();
        if (!empty($validated['user_id']) && User::find($validated['user_id'])) {
            $contact->user_id = $validated['user_id'];
        } else {
            $contact->user_id = null;
        }
        $contact->name = $validated['name'];
        $contact->email = $validated['email'];
        $contact->subject = $validated['subject'];
        $contact->message = $validated['message'];
        $contact->save();

        Mail::to('admin@gmail.com')->send(new ContactFormMail($validated));

        return response()->json([
            'message' => 'Sent message successfully'
        ], 200);
    }


    public function destroy(Request $request, $id) 
    {
        $data = Contacts::find($id);

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
            Contacts::whereIn('id', $ids)->delete();
            return redirect()->back()->with('message', 'Selected files deleted.');
        }

        return redirect()->back()->with('error', 'No files selected.');
    }

}
