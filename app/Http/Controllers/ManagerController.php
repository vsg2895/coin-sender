<?php

namespace App\Http\Controllers;

use App\Models\{User, Ambassador};

use App\Http\Resources\{
    User as UserResource,
    Manager as ManagerResource,
};

use App\Http\Requests\ManagerListRequest;

class ManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission'])->only(['index']);
    }

    /**
     * Get List All Managers
     * @OA\Get (
     *     path="/api/managers",
     *     tags={"Managers"},
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
    public function index(ManagerListRequest $request)
    {
        $search = '%'.$request->get('search').'%';
        $managers = User::whereHas('allRoles', fn ($query) => $query->where('name', 'Catapult Manager'))
            ->with([
                'allRoles' => function ($query) {
                    $teamId = getPermissionsTeamId();
                    if ($teamId !== 0) {
                        $query->where('model_has_roles.team_id', $teamId);
                    }
                },
                'invitation' => fn ($query) => $query->where('project_id', getPermissionsTeamId()),
                'projectMembers',
                'projectMembers.project' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->when($request->has('search'), fn ($query) => $query->where('name', 'LIKE', $search))
            ->withCount(['tasks'])
            ->get();

        return response()->json(ManagerResource::collection($managers));
    }

    /**
     * Get Manager
     * @OA\Get (
     *     path="/api/managers/{manager}",
     *     @OA\Parameter(
     *         in="path",
     *         name="manager",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Managers"},
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="example name",
     *              ),
     *              @OA\Property(
     *                  property="role",
     *                  nullable="true",
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="Catapult Manager"),
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="test@test.com",
     *              ),
     *              @OA\Property(
     *                  property="country",
     *                  type="string",
     *                  nullable="true",
     *                  example="Ukraine",
     *              ),
     *              @OA\Property(
     *                  property="projects",
     *                  type="array",
     *                  @OA\Items(type="string"),
     *              ),
     *              @OA\Property(
     *                  property="languages",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="language",
     *                          @OA\Property(
     *                              property="id",
     *                              type="number",
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              type="string",
     *                          ),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="self_project",
     *                  nullable="true",
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="social_links",
     *                  type="array",
     *                  deprecated=true,
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="content",
     *                          type="string",
     *                      ),
     *                      @OA\Property(
     *                          property="social_link",
     *                          @OA\Property(
     *                              property="id",
     *                              type="number",
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              type="string",
     *                          ),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="social_providers",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                          example="1",
     *                      ),
     *                      @OA\Property(
     *                          property="name",
     *                          type="string",
     *                          example="test#1234",
     *                      ),
     *                      @OA\Property(
     *                          property="provider_id",
     *                          type="number",
     *                          example="1",
     *                      ),
     *                      @OA\Property(
     *                          property="provider_name",
     *                          type="string",
     *                          enum={"twitter", "telegram", "discord"},
     *                          example="discord",
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="checked_tasks",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="created_tasks",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="deadlines_violated",
     *                  type="number",
     *                  example="1",
     *              ),
     *          ),
     *      ),
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
    public function show(User $user)
    {
        $user->load([
            'allRoles',
            'selfProject' => fn ($query) => $query->withoutGlobalScopes(),
            'projectMembers',
            'projectMembers.project' => fn ($query) => $query->withoutGlobalScopes(),
            'country',
            'country.country',
            'languages',
            'languages.language',
            'socialProviders',
            'socialLinks',
            'socialLinks.link',
            'socialLinks.link.media',
        ]);

        $user->loadCount([
            'tasks',
            'checkedTasks',
        ]);

        return response()->json(new UserResource($user));
    }

    /**
     * Autocomplete Managers
     * @OA\Get (
     *     path="/api/managers/autocomplete",
     *     tags={"Managers"},
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="example name",
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="test@test.com",
     *              ),
     *          ),
     *      ),
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
    public function autocomplete()
    {
        $managers = User::whereHas('allRoles', function ($query) {
            $query->where('name', 'Catapult Manager');
        })->select(['id', 'name', 'email']);

        $result = Ambassador::select(['id', 'name', 'email'])->union($managers->getQuery())->get();
        return response()->json($result);
    }
}
