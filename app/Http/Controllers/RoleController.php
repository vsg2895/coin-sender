<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Http\Resources\Role as RoleResource;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission']);
    }

    /**
     * Get List Roles
     * @OA\Get (
     *     path="/api/roles",
     *     tags={"Roles"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="example name"
     *              ),
     *         ),
     *     ),
     * )
     */
    public function index()
    {
        $user = auth()->user();
        $roles = Role::whereNotIn('name', $user->hasRole('Project Owner') ? ['Super Admin', 'Catapult Manager'] : ['Super Admin'])->get();

        return response()->json(RoleResource::collection($roles));
    }
}
