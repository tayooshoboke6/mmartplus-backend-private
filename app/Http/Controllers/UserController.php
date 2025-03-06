<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     *
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Get all users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="roles",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="customer")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $users = User::with('roles');
        
        // Filter by role if provided
        if ($request->has('role')) {
            $users->role($request->role);
        }
        
        // Search functionality
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $users->where(function($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }
        
        $users = $users->paginate($request->per_page ?? 15);
        
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }
    
    /**
     * Store a newly created user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="New User"),
     *             @OA\Property(property="email", type="string", format="email", example="newuser@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 @OA\Items(type="string", example="customer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign roles if provided, otherwise assign customer role
        if ($request->has('roles') && !empty($request->roles)) {
            $user->assignRole($request->roles);
        } else {
            $user->assignRole('customer');
        }

        // Load the roles relation
        $user->load('roles:id,name');

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }
    
    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/admin/users/{id}",
     *     summary="Get a user by ID",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $user = User::with('roles:id,name')->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }
    
    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     summary="Update a user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Name"),
     *             @OA\Property(property="email", type="string", format="email", example="updated@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123"),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 @OA\Items(type="string", example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'string|max:255',
            'email' => [
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        // Update user details
        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        // Update roles if provided
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        // Load the roles relation
        $user->load('roles:id,name');

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }
    
    /**
     * Remove the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     summary="Delete a user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($request->user()->id == $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own account'
            ], 400);
        }
        
        // Only super-admin can delete admin
        if ($user->hasRole('admin') && !$request->user()->hasRole('super-admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete an admin'
            ], 403);
        }
        
        // Check if user has orders
        if ($user->orders()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete user with orders. Consider deactivating the account instead.'
            ], 400);
        }
        
        $user->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
    
    /**
     * Get available roles.
     *
     * @return \Illuminate\Http\Response
     */
    public function getRoles()
    {
        $roles = Role::all(['id', 'name']);
        
        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }
}
