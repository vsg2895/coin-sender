<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\{
    Project,
    Invitation,
    Ambassador,
    ProjectMember
};

use App\Notifications\AmbassadorInvitationNotification;

use App\Http\Requests\{
    AmbassadorListRequest,
    AmbassadorInviteRequest,
    AmbassadorAutocompleteRequest,
};

use App\Http\Resources\{
    Ambassador as AmbassadorResource,
    AmbassadorCollection,
};

use Carbon\Carbon;

class AmbassadorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission']);
        $this->authorizeResource(Ambassador::class, 'ambassador');
    }

    /**
     * Get List Ambassadors
     * @OA\Get (
     *     path="/api/ambassadors",
     *     tags={"Ambassadors"},
     *     @OA\Parameter(
     *         in="query",
     *         name="page",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="per_page",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
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
     *                  example="example name",
     *              ),
     *              @OA\Property(
     *                  property="coins",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="level",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="points",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="invitation_status",
     *                  nullable=true,
     *                  type="string",
     *                  enum={"pending", "declined", "accepted"},
     *                  example="pending",
     *              ),
     *              @OA\Property(
     *                  property="has_failed_deadline",
     *                  type="boolean",
     *              ),
     *              @OA\Property(
     *                  property="next_level",
     *                  type="boolean",
     *              ),
     *              @OA\Property(
     *                  property="need_points",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="test@test.com",
     *              ),
     *              @OA\Property(
     *                  property="avatar",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="total_tasks",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="total_rewards",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="registered_at",
     *                  type="string",
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
     *                  property="activities",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="status",
     *                          type="string",
     *                          enum={"created", "approved", "declined"},
     *                          example="created",
     *                      ),
     *                      @OA\Property(
     *                          property="activity",
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
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
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
    public function index(AmbassadorListRequest $request)
    {
        $search = $request->get('search');
        $register_date = Carbon::createFromTimestamp($request->get('register_date'));

        $ambassadors = Ambassador::with([
            'skills',
            'skills.skill',
            'invitation' => fn ($query) => $query->where('project_id', getPermissionsTeamId()),
            'languages',
            'languages.language',
            'activities',
            'activities.activity',
        ])
            ->withCount('tasksInWork as tasks_count')
            ->selectRaw("EXISTS(SELECT 1 FROM `user_tasks` WHERE `user_tasks`.`status` = 'overdue' AND `users`.`id` = `user_tasks`.`user_id`) as has_failed_deadline")
            ->when($request->has('search'), function ($query) use ($search) {
                $query->where('name', 'LIKE', '%'.$search.'%');
            })
            ->when($request->has('register_date'), function ($query) use ($register_date) {
                $query->whereDate('created_at', $register_date);
            })
            ->orderByDesc('id')
            ->paginate($request->get('per_page') ?: 10);

        return response()->json(new AmbassadorCollection($ambassadors));
    }

    /**
     * Autocomplete Search Ambassadors
     * @OA\Get (
     *     path="/api/ambassadors/autocomplete",
     *     tags={"Ambassadors"},
     *     @OA\Parameter(
     *         in="query",
     *         name="search",
     *         required=false,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="min_level",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="max_level",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="activity_id",
     *         required=false,
     *         @OA\Schema(type="number"),
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
     *                  example="example name",
     *              ),
     *              @OA\Property(
     *                  property="coins",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="level",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="points",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="invitation_status",
     *                  nullable=true,
     *                  type="string",
     *                  enum={"pending", "declined", "accepted"},
     *                  example="pending",
     *              ),
     *              @OA\Property(
     *                  property="has_failed_deadline",
     *                  type="boolean",
     *              ),
     *              @OA\Property(
     *                  property="next_level",
     *                  type="boolean",
     *              ),
     *              @OA\Property(
     *                  property="need_points",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="test@test.com",
     *              ),
     *              @OA\Property(
     *                  property="avatar",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="total_tasks",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="total_rewards",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="registered_at",
     *                  type="string",
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
     *                  property="activities",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="status",
     *                          type="string",
     *                          enum={"created", "approved", "declined"},
     *                          example="created",
     *                      ),
     *                      @OA\Property(
     *                          property="activity",
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
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
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
    public function autocomplete(AmbassadorAutocompleteRequest $request)
    {
        $user = auth()->user();
        $user->load(['roles']);

        $search = $request->get('search');
        $activity_id = $request->get('activity_id');

        $ambassadors = Ambassador::with([
            'skills',
            'skills.skill',
            'invitation' => fn ($query) => $query->where('project_id', getPermissionsTeamId()),
            'languages',
            'languages.language',
            'activities',
            'activities.activity',
        ])
            ->withCount('tasksInWork as tasks_count')
            ->selectRaw("EXISTS(SELECT 1 FROM `user_tasks` WHERE `user_tasks`.`status` = 'overdue' AND `users`.`id` = `user_tasks`.`user_id`) as has_failed_deadline")
            ->when($request->filled('search'), function ($query) use ($search) {
                $query->where('name', 'LIKE', '%'.$search.'%');
            });

        if ($request->filled('min_level')) {
            $min_level = $request->get('min_level');
            $max_level = $request->get('max_level');

            if ($min_level === $max_level) {
                $ambassadors = $ambassadors->where('level', '=', $min_level);
            } else {
                $ambassadors = $ambassadors->when($request->has('min_level'), function ($query) use ($min_level) {
                    $query->where('level', '>=', $min_level);
                })->when($request->has('max_level'), function ($query) use ($max_level) {
                    $query->where('level', '<=', $max_level);
                });
            }
        }

        if ($request->has('activity_id')) {
            $ambassadors->whereHas('activities', function ($query) use ($activity_id) {
                $query->where('activity_id', $activity_id)
                    ->active();
            });
        }

        $ambassadors = $ambassadors->orderByDesc('id')->get();
        if (!$user->hasAnyRole(['Super Admin', 'Catapult Manager'])) {
            $ambassadors = $ambassadors->makeHidden([
                'email',
                'wallet',
                'has_failed_deadline',
            ]);
        }

        return response()->json(AmbassadorResource::collection($ambassadors));
    }

    /**
     * Get ambassador
     * @OA\Get (
     *     path="/api/ambassadors/{ambassador}",
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassador",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Ambassadors"},
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
     *                  property="coins",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="level",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="points",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="has_failed_deadline",
     *                  type="boolean",
     *              ),
     *              @OA\Property(
     *                  property="next_level",
     *                  type="boolean",
     *              ),
     *              @OA\Property(
     *                  property="need_points",
     *                  type="number",
     *                  example="100",
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="test@test.com",
     *              ),
     *              @OA\Property(
     *                  property="avatar",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="total_tasks",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="total_rewards",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="registered_at",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="tasks",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(
     *                          property="task",
     *                          @OA\Property(property="name", type="string", example="example name"),
     *                          @OA\Property(property="text", type="number", example="example text"),
     *                          @OA\Property(
     *                              property="project",
     *                              nullable=false,
     *                              @OA\Property(property="id", type="number", example="1"),
     *                              @OA\Property(property="name", type="string", example="example name"),
     *                          ),
     *                          @OA\Property(property="started_at", type="string"),
     *                          @OA\Property(property="ended_at", type="string"),
     *                          @OA\Property(
     *                              property="status_by_dates",
     *                              type="string",
     *                              enum={"available", "upcoming", "finished"},
     *                              example="available",
     *                          ),
     *                      ),
     *                      @OA\Property(property="report", type="string", example="example report"),
     *                      @OA\Property(
     *                          property="status",
     *                          type="string",
     *                          enum={"returned", "in_progress", "waiting_for_review"},
     *                          example="in_progress",
     *                      ),
     *                      @OA\Property(property="user_id", type="number", example="1"),
     *                      @OA\Property(property="task_id", type="number", example="1"),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="skills",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="skill",
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
     *                  property="links",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="link",
     *                          @OA\Property(
     *                              property="id",
     *                              type="number",
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="icon",
     *                              type="string",
     *                              nullable="true",
     *                          ),
     *                      ),
     *                      @OA\Property(
     *                          property="content",
     *                          type="string",
     *                      ),
     *                  ),
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
     *                  property="activities",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="status",
     *                          type="string",
     *                          enum={"created", "approved", "declined"},
     *                          example="created",
     *                      ),
     *                      @OA\Property(
     *                          property="activity",
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
     *                  property="activity_links",
     *                  type="array",
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
     *                          property="activity_link",
     *                          @OA\Property(
     *                              property="id",
     *                              type="number",
     *                          ),
     *                          @OA\Property(
     *                              property="link_id",
     *                              type="number",
     *                          ),
     *                          @OA\Property(
     *                              property="activity_id",
     *                              type="number",
     *                          ),
     *                          @OA\Property(
     *                              property="link",
     *                              @OA\Property(
     *                                  property="name",
     *                                  type="string",
     *                              ),
     *                              @OA\Property(
     *                                  property="icon",
     *                                  type="string",
     *                                  nullable="true",
     *                              ),
     *                          ),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="social_links",
     *                  type="array",
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
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found",
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Unauthorized"),
     *          ),
     *      ),
     *      security={{ "apiAuth": {} }},
     * )
     */
    public function show(Ambassador $ambassador)
    {
        $user = auth()->user();
        $user->load(['roles']);

        $ambassador->load([
            'media',
            'tasks' => function ($query) {
                return $query->withoutGlobalScopes();
            },
            'tasks.task',
            'tasks.task.project' => function ($query) {
                return $query->withoutGlobalScopes();
            },
            'projectMembers',
            'projectMembers.project' => function ($query) {
                return $query->withoutGlobalScopes();
            },
            'skills',
            'skills.skill',
            'links',
            'links.link',
            'country',
            'country.country',
            'languages',
            'languages.language',
            'activities',
            'activities.activity',
            'activityLinks',
            'activityLinks.link',
            'activityLinks.link.link',
            'activityLinks.link.link.media',
            'socialLinks',
            'socialLinks.link',
            'socialLinks.link.media',
            'socialProviders',
        ]);

        if (!$user->hasAnyRole(['Super Admin', 'Catapult Manager'])) {
            $ambassador = $ambassador->makeHidden([
                'email',
                'wallet',
            ])
                ->unsetRelation('country')
                ->unsetRelation('country.country')
                ->unsetRelation('languages')
                ->unsetRelation('languages.language')
                ->unsetRelation('socialProviders')
                ->unsetRelation('socialLinks')
                ->unsetRelation('socialLinks.link')
                ->unsetRelation('socialLinks.link.media');
        }

        $ambassador->position = getUserPositionByLevel($ambassador->id, $ambassador->level);
        return response()->json(new AmbassadorResource($ambassador));
    }

    /**
     * Delete ambassador
     * @OA\Delete (
     *     path="/api/ambassadors/{ambassador}",
     *     tags={"Ambassadors"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassador",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="no content",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
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
    public function destroy(Ambassador $ambassador)
    {
        $ambassador->delete();
        return response()->noContent();
    }

    /**
     * Ambassador level up
     * @OA\Post (
     *     path="/api/ambassadors/level-up/{ambassador}",
     *     tags={"Ambassadors"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassador",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="no content",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can't level up!"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized."),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function levelUp(Ambassador $ambassador)
    {
        $position = getUserPositionByLevel($ambassador->id, $ambassador->level);
        $need_points = config('levels.need_points')[$ambassador->level] ?? null;

        $ambassador->loadCount(['activities' => function ($query) {
            $query->active();
        }]);

        if (is_null($need_points)
            || ($ambassador->points < $need_points && (is_null($position) || $position > config('app.minimum_leaderboard_place_level_up')))
            || $ambassador->activities_count === 0) {
            return response()->json([
                'message' => 'Can\'t level up!',
            ], 400);
        }

        $ambassador->increment('level', 1, ['points' => 0]);
        return response()->noContent();
    }

    /**
     * Invite ambassador to project
     * @OA\Post (
     *     path="/api/ambassadors/invite/{ambassador}",
     *     tags={"Ambassadors"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassador",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\RequestBody(
     *        @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(
     *                 @OA\Property(
     *                     property="project_id",
     *                     type="number",
     *                     nullable=true,
     *                 ),
     *            ),
     *        ),
     *     ),
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
     *             @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  @OA\Examples(value="User without email cannot be invited!"),
     *                  @OA\Examples(value="User is already invited to your project!"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized."),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function invite(Ambassador $ambassador, AmbassadorInviteRequest $request)
    {
        $this->authorize('assign-project-member');

        if (!$ambassador->email) {
            return response()->json([
                'message' => 'User without email cannot be invited!',
            ], 400);
        }

        $project = Project::findOrFail($request->get('project_id') ?? getPermissionsTeamId());
        if ($ambassador->invitation()->where('project_id', $project->id)->exists()
        || $ambassador->projectMembers()->where('project_id', $project->id)->exists()) {
            return response()->json([
                'message' => 'User is already invited to your project!',
            ], 400);
        }

        $token = Str::uuid();
        $ambassador->invitation()->create([
            'token' => $token,
            'status' => Invitation::STATUS_PENDING,
            'role_name' => null,
            'project_id' => $project->id,
        ]);

        $ambassador->projectMembers()->firstOrCreate([
            'project_id' => $project->id,
        ], [
            'status' => ProjectMember::STATUS_INVITED,
            'project_id' => $project->id,
        ]);

        $ambassador->notify(new AmbassadorInvitationNotification($token, $project));
        return response()->noContent();
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return array_merge(parent::resourceAbilityMap(), [
            'levelUp' => 'levelUp',
        ]);
    }
}
