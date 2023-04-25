<?php

namespace App\Http\Controllers;

use App\Models\{
    User,
    Project,
    Invitation,
    ProjectMember,
};

use App\Http\Requests\{
    MyTeamListRequest,
    MyTeamStoreRequest,
    MyTeamUpdateRequest,
};

use App\Notifications\ManagerInvitationNotification;
use App\Http\Resources\ProjectMember as ProjectMemberResource;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class MyTeamController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission']);
    }

    /**
     * Get List My Team
     * @OA\Get (
     *     path="/api/my-team",
     *     tags={"My Team"},
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
     *                  nullable="true",
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="Project Member"),
     *              ),
     *              @OA\Property(
     *                  property="status",
     *                  nullable=true,
     *                  type="string",
     *                  enum={"pending", "declined", "accepted"},
     *                  example="pending",
     *              ),
     *              @OA\Property(
     *                  property="created_at",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="tasks_count",
     *                  type="number",
     *                  example="1",
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
    public function index(MyTeamListRequest $request)
    {
        $this->authorize('view-team');
        $search = '%'.$request->get('search').'%';
        $projectId = getPermissionsTeamId();

        $members = User::where('id', '!=', auth()->id())
            ->whereHas('invitation', fn ($query) => $query->where('project_id', $projectId))
            ->when($request->has('search'), fn ($query) => $query->where('name', 'LIKE', $search))
            ->withCount(['tasks' => fn ($query) => $query->where('project_id', $projectId)])
            ->with(['roles', 'invitation'])
            ->withoutGlobalScopes()
            ->get();

        return response()->json(ProjectMemberResource::collection($members));
    }

    /**
     * Create Access For My Team Member
     * @OA\Post (
     *     path="/api/my-team",
     *     tags={"My Team"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                      nullable=true,
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
    public function store(MyTeamStoreRequest $request)
    {
        $role = Role::firstWhere('name', $request->get('role_name'));
        $email = $request->get('email');

        $this->authorize(
            $request->filled('email')
                ? 'assign-custom-project-member'
                : 'assign-project-member'
        );

        $user = User::find($request->get('manager_id')) ?: User::firstOrCreate([
            'email' => $email,
        ], [
            'name' => $email,
            'email' => $email,
            'password' => Hash::make(Str::random(10)),
        ]);

        $token = Str::uuid();
        $project = Project::find(getPermissionsTeamId());

        $user->invitation()->create([
            'token' => $token,
            'status' => Invitation::STATUS_PENDING,
            'role_name' => $role->name,
            'project_id' => $project->id,
        ]);

        $user->projectMembers()->firstOrCreate([
            'project_id' => $project->id
        ], [
            'status' => ProjectMember::STATUS_INVITED,
            'project_id' => $project->id,
        ]);

        $user->notify(new ManagerInvitationNotification($token, $role->name, $project));
        return response()->noContent();
    }

    /**
     * Update Access For My Team Member
     * @OA\Put (
     *     path="/api/my-team",
     *     tags={"My Team"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="role_name",
     *                      type="string",
     *                      enum={
     *                          "Catapult Manager",
     *                          "Project Administrator",
     *                          "Project Manager",
     *                      },
     *                  ),
     *                  @OA\Property(
     *                      property="manager_id",
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
    public function update(MyTeamUpdateRequest $request)
    {
        $this->authorize('assign-project-member');

        $role = Role::firstWhere('name', $request->get('role_name'));
        $user = User::find($request->get('manager_id'));

        $user->roles()->detach();
        $user->assignRole($role);
        $user->forgetCachedPermissions();

        return response()->noContent();
    }

    /**
     * Delete user from my team
     * @OA\Delete (
     *     path="/api/my-team/{projectMember}",
     *     tags={"My Team"},
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
     *             @OA\Property(property="message", type="string", example="Can't revoke access from this user!"),
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
        $this->authorize('delete-project-member');
        $user->load(['invitation']);

        $projectId = getPermissionsTeamId();
        $invitation = $user->invitation->first();

        if (!$invitation || $invitation->project_id !== $projectId) {
            return response()->json([
                'message' => 'Can\'t revoke access from this user!',
            ], 400);
        }

        $invitation->update([
            'status' => Invitation::STATUS_REVOKED,
        ]);

        $user->roles()->detach();
        $user->forgetCachedPermissions();
        $user->projectMembers()->where('project_id', $projectId)->delete();

        return response()->noContent();
    }
}
