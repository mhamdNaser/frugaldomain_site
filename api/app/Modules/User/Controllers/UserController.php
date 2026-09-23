<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use App\Modules\User\Requests\User\StoreUserRequest;
use App\Modules\User\Requests\User\UpdateUserRequest;
use App\Modules\User\Requests\User\AdminResetPasswordRequest;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{
    protected $repos;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->repos = $users;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->repos->getAllUsers($search, $rowsPerPage, $page);

        return response()->json([
            'data' => UserResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }


    public function all()
    {
        $data =  $this->repos->all();
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $user = $this->repos->create($data);
        return response()
            ->json([
                'message' => 'User created successfully',
                'data' => new UserResource($user)
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = $this->repos->find($id);

        if ($user) {
            return response()->json(new UserResource($user));
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    /**
     * Set a new password for a user who has lost theirs.
     *
     * The administrator chooses the password and passes it on; the user can
     * change it from their account afterwards. The user's sessions are ended
     * unless the administrator says otherwise, so whoever might have been
     * signed in with the old password is signed out.
     */
    public function resetPassword(AdminResetPasswordRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();

        // The model casts password as "hashed".
        $user->password = $data['password'];
        $user->setRememberToken(Str::random(60));
        $user->save();

        if ($data['revoke_sessions'] ?? true) {
            $user->tokens()->delete();
        }

        // Password reset codes still pending for the old password are void.
        DB::table('password_reset_tokens')->where('email', strtolower($user->email))->delete();

        Log::info('Admin reset a user password', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function changStatus($id)
    {
        $user = $this->repos->toggleStatus($id);

        return response()->json([
            'message' => 'User Status Updated',
            'data' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $data = $request->validated();
        $user = $this->repos->update($id, $data);

        if ($user) {
            return response()
                ->json([
                    'message' => 'User created successfully',
                    'data' => new UserResource($user)
                ], 201);
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deleted = $this->repos->delete($id);

        if ($deleted) {
            return response()->json(['message' => 'User deleted successfully'], 200);
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    public function destroyArray(Request $request)
    {
        $ids = $request->input('ids', []);
        $deleted = $this->repos->deleteArray($ids);

        if ($deleted) {
            return response()->json(['message' => 'Users deleted successfully'], 200);
        }

        return response()->json(['message' => 'No users found to delete'], 404);
    }
}
