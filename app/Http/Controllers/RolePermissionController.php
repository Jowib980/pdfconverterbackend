<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    // Create roles and permissions
    public function setupRolesAndPermissions()
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create permissions
        $editPermission = Permission::firstOrCreate(['name' => 'edit articles']);
        $deletePermission = Permission::firstOrCreate(['name' => 'delete articles']);

        // Assign permissions to roles
        $adminRole->givePermissionTo($editPermission);
        $adminRole->givePermissionTo($deletePermission);

        // Assign role to a specific user (e.g., user with ID 1)
        $user = User::find(1);
        if ($user) {
            $user->assignRole($adminRole);
            return response()->json(['message' => 'Roles and permissions setup successfully.']);
        }

        return response()->json(['message' => 'User not found.'], 404);
    }
}
