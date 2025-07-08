<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    
    public function index() {
        $users = User::orderByDesc('created_at')->paginate(10);

        return view('admin.user.index', compact('users'));
    }

    public function create() {
        return view('admin.user.store');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required',
        ]);

        $email = $request->email;

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->name = $request->name;
            $user->password = $request->password;
            $user->save();

            return redirect()->route('users')->with('message', 'User updated successfully!');
        } else {
            
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = $request->password;
            $user->save();

            return redirect()->route('users')->with('message', 'User added successfully!');

        }
    }

    public function edit(Request $request, $id) {

        $data = User::find($id);

        return view('admin.user.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|max:255',
                'password' => 'nullable|string|min:6',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        try {
            $user = User::findOrFail($id);

            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];

            if (!empty($validatedData['password'])) {
                $user->password = bcrypt($validatedData['password']);
            }

            $user->save();

            return redirect()->route('users')->with('success', 'User updated successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'User not found.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the user.');
        }
    }



    public function view(Request $request, $id) {
        $user = User::find($id);

        return view('admin.user.view', compact('user'));
    }

    public function destroy(Request $request, $id)
    {
        $data = User::find($id);

        if (!$data) {
            return redirect()->back()->with('error', 'User not found!');
        }

        $data->delete();
        return redirect()->back()->with('message', 'Delete user successfully!');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            User::whereIn('id', $ids)->delete();
            return redirect()->back()->with('message', 'Selected users deleted.');
        }

        return redirect()->back()->with('error', 'No user selected.');
    }

}