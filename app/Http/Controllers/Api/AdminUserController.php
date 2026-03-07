<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\InteractsWithMemberProfiles;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    use InteractsWithMemberProfiles;

    public function indexMembers(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = User::query()->where('role', 'member');

        if ($status === 'pending') {
            $query->where('is_approved', false);
        } elseif ($status === 'active') {
            $query->where('is_approved', true)->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $members = $query
            ->orderByDesc('id')
            ->get(['id', 'name', 'email', 'role', 'is_approved', 'is_active', 'approved_at', 'approved_by']);

        return response()->json(['members' => $members]);
    }

    public function approveMember(Request $request, User $user): JsonResponse
    {
        if ($response = $this->ensureMemberUser($user)) {
            return $response;
        }

        $user->forceFill([
            'is_approved' => true,
            'is_active' => true,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ])->save();

        return response()->json([
            'message' => 'Member approved successfully.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_approved', 'is_active', 'approved_at', 'approved_by']),
        ]);
    }

    public function deactivateUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        if ($response = $this->ensureMemberUser($user)) {
            return $response;
        }

        $user->forceFill(['is_active' => false])->save();

        return response()->json([
            'message' => 'User deactivated successfully.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_approved', 'is_active']),
        ]);
    }

    public function activateUser(User $user): JsonResponse
    {
        if ($response = $this->ensureMemberUser($user)) {
            return $response;
        }

        $user->forceFill(['is_active' => true])->save();

        return response()->json([
            'message' => 'User activated successfully.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_approved', 'is_active']),
        ]);
    }

    public function setRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,member'],
        ]);

        $updates = ['role' => $validated['role']];

        if ($validated['role'] === 'admin') {
            $updates['is_approved'] = true;
            $updates['is_active'] = true;
            $updates['approved_at'] = now();
            $updates['approved_by'] = $request->user()->id;
        }

        $user->forceFill($updates)->save();

        return response()->json([
            'message' => 'Role updated successfully.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_approved', 'is_active', 'approved_at', 'approved_by']),
        ]);
    }

    public function showMemberProfile(User $user): JsonResponse
    {
        if ($response = $this->ensureMemberUser($user)) {
            return $response;
        }

        return response()->json([
            'profile' => $this->memberProfileData($user),
        ]);
    }

    public function updateMemberProfile(Request $request, User $user): JsonResponse
    {
        if ($response = $this->ensureMemberUser($user)) {
            return $response;
        }

        return response()->json([
            'message' => 'Member profile saved successfully.',
            'profile' => $this->saveMemberProfile($request, $user),
        ]);
    }

    private function ensureMemberUser(User $user): ?JsonResponse
    {
        if ($user->role !== 'member') {
            return response()->json(['message' => 'Only member accounts can be managed here.'], 422);
        }

        return null;
    }
}
