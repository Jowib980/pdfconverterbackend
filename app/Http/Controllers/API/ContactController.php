<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactController extends Controller
{
    public function index() 
    {
        // You can use this to list contacts if needed
        return Contact::latest()->get();
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

        $contact = new Contact();
        $contact->user_id = $request->input('user_id');
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
}
