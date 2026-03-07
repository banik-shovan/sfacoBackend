<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\InteractsWithMemberProfiles;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberProfileController extends Controller
{
    use InteractsWithMemberProfiles;

    public function schema(): JsonResponse
    {
        return response()->json([
            'fields' => $this->memberProfileFields(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'profile' => $this->memberProfileData($request->user()),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Member profile saved successfully.',
            'profile' => $this->saveMemberProfile($request, $request->user()),
        ]);
    }
}
