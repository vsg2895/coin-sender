<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Permission;
use App\Http\Resources\Permission as PermissionResource;

class PermissionController extends Controller
{
    /**
     * Get List Permissions
     * @OA\Get (
     *     path="/api/permissions",
     *     tags={"Permissions"},
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
        $permissions = Permission::all();
        return response()->json(PermissionResource::collection($permissions));
    }
}
