<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use App\QueryBuilders\UserQueryBuilder;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $adminUserService) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));
        $users = UserQueryBuilder::buildQuery($request)->paginate($perPage);

        return response()->json(paginate_payload($users, 'users'));
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $user = $this->adminUserService->create($request->validated());

        return response()->json([
            'message' => 'User created successfully.',
            'user' => user_payload($user->fresh()),
        ], 201);
    }

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $user = $this->adminUserService->update($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => user_payload($user->fresh()),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

}
