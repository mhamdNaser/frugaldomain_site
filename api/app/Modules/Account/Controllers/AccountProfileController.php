<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Repositories\Interfaces\AccountRepositoryInterface;
use App\Modules\Account\Requests\ChangePasswordRequest;
use App\Modules\Account\Requests\DeleteAccountRequest;
use App\Modules\Account\Requests\UpdateProfileRequest;
use App\Modules\Account\Resources\AccountUserResource;
use Illuminate\Http\Request;

class AccountProfileController extends Controller
{
    public function __construct(private AccountRepositoryInterface $accounts) {}

    public function me(Request $request)
    {
        return new AccountUserResource($request->user());
    }

    public function overview(Request $request)
    {
        return response()->json(['data' => $this->accounts->overview($request->user())]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $this->accounts->updateProfile($request->user(), $request->validated());

        return response()->json([
            'message' => 'Profile saved.',
            'user' => new AccountUserResource($user),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $this->accounts->changePassword($request->user(), $request->validated()['password']);

        return response()->json(['message' => 'Password changed.']);
    }

    public function destroy(DeleteAccountRequest $request)
    {
        $user = $request->user();

        // An admin or a store owner deleting themselves would orphan the site
        // or a shop's data. Those accounts are closed by an administrator.
        if ($user->hasAnyRole(['admin', 'partner']) || $user->hasStore()) {
            return response()->json([
                'message' => 'This account manages the site or a store and cannot be deleted here.',
            ], 403);
        }

        $this->accounts->deleteAccount($user);

        return response()->json(['message' => 'Account deleted.']);
    }
}
