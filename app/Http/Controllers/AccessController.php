<?php

namespace App\Http\Controllers;

use App\Models\{
    User,
    Project,
    Invitation,
    ProjectMember,
};

use App\Http\Requests\{
    AccessListRequest,
    AccessStoreRequest,
    AccessUpdateRequest,
};

use App\Http\Resources\UserAccess as UserAccessResource;
use App\Notifications\ManagerInvitationNotification;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AccessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission']);
    }

    /**
     * Get List Accesses
     * @OA\Get (
     *     path="/api/accesses",
     *     tags={"Accesses"},
     *     @OA\Parameter(
     *         in="query",
     *         name="search",
     *         required=false,
     *         @OA\Schema(type="string"),
     *     ),
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
     *              @OA\Property(
     *                  property="role",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="status",
     *                  nullable=true,
     *                  type="string",
     *                  enum={"pending", "declined", "accepted"},
     *                  example="pending",
     *              ),
     *              @OA\Property(
     *                  property="project_name",
     *                  nullable=true,
     *                  type="string",
     *                  example="example project name"
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function index(AccessListRequest $request)
    {
        $this->authorize('view-accesses');

        $search = '%'.$request->get('search').'%';
        $accesses = User::whereHas('allRoles', fn ($query) => $query->where('name', '!=', 'Super Admin'))
            ->with([
                'allRoles',
                'invitation',
                'invitation.project',
            ])
            ->when($request->has('search'), fn ($query) => $query->where('name', 'LIKE', $search))
            ->get();

        return response()->json(UserAccessResource::collection($accesses));
    }

    /**
     * Create Access
     * @OA\Post (
     *     path="/api/accesses",
     *     tags={"Accesses"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="role_name",
     *                      type="string",
     *                      enum={
     *                          "Catapult Manager",
     *                          "Project Owner",
     *                          "Project Administrator",
     *                          "Project Manager",
     *                      },
     *                  ),
     *                  @OA\Property(
     *                      property="manager_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="project_id",
     *                      nullable=true,
     *                      type="number",
     *                  ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="no content",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function store(AccessStoreRequest $request)
    {
        $this->authorize('create-access');

        $role = Role::firstWhere('name', $request->get('role_name'));
        $email = $request->get('email');

        $token = Str::uuid();
        $project = Project::find($request->get('project_id'));

        setPermissionsTeamId($project->id ?? 0);

        $user = User::find($request->get('manager_id')) ?: User::firstOrCreate([
            'email' => $email
        ], [
            'name' => $email,
            'email' => $email,
            'password' => Hash::make(Str::random(10)),
        ]);

        $user->invitation()->create([
            'token' => $token,
            'status' => Invitation::STATUS_PENDING,
            'role_name' => $role->name,
            'project_id' => $project->id,
        ]);

        $user->notify(new ManagerInvitationNotification($token, $role->name, $project));
        return response()->noContent();
    }

    /**
     * Update Access
     * @OA\Put (
     *     path="/api/accesses/{manager}",
     *     @OA\Parameter(
     *         in="path",
     *         name="manager",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Accesses"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="role_name",
     *                      type="string",
     *                      enum={
     *                          "Catapult Manager",
     *                          "Project Owner",
     *                          "Project Administrator",
     *                          "Project Manager",
     *                      },
     *                  ),
     *                  @OA\Property(
     *                      property="project_id",
     *                      nullable=true,
     *                      type="number",
     *                  ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="no content",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function update(User $user, AccessUpdateRequest $request)
    {
        $this->authorize('update-access');

        $role = Role::firstWhere('name', $request->get('role_name'));
        $projectId = $request->get('project_id');

        setPermissionsTeamId($projectId ?? 0);
        if ($request->filled('project_id')) {
            $user->projectMembers()->firstOrCreate([
                'project_id' => $projectId,
            ], [
                'status' => ProjectMember::STATUS_ACCEPTED,
                'project_id' => $projectId,
            ]);
        } else {
            $user->projectMembers()->delete();
        }

        $user->allRoles()->detach();
        $user->assignRole($role->name);

        return response()->noContent();
    }

    /**
     * Delete Access
     * @OA\Delete (
     *     path="/api/accesses/{manager}",
     *     @OA\Parameter(
     *         in="path",
     *         name="manager",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Accesses"},
     *     @OA\Response(
     *         response=204,
     *         description="no content",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can't revoke access super admin!"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function destroy(User $user)
    {
        $this->authorize('delete-access');

        if ($user->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'Can\'t revoke access super admin!',
            ], 400);
        }

        $user->allRoles()->detach();
        $user->forgetCachedPermissions();
        $user->projectMembers()->delete();

        return response()->noContent();
    }
}
