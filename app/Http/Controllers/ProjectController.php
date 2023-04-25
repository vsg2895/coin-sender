<?php

namespace App\Http\Controllers;

use App\Models\{
    User,
    Project,
    Ambassador,
    Invitation,
    AmbassadorTask,
};

use App\Http\Resources\{
    Project as ProjectResource,
    SocialProvider as SocialProviderResource,
    PendingReviewAmbassadorTask as PendingReviewAmbassadorTaskResource,
};

use App\Notifications\{
    NewProjectCreatedNotification,
    ProjectOwnerInvitationNotification,
    Social\NewProjectCreatedSocialNotification,
};

use App\Http\Requests\{ProjectCreateRequest, ProjectUpdateRequest};

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission'])->except(['show', 'index']);
        $this->authorizeResource(Project::class, 'project');
    }

    /**
     * Get List Projects
     * @OA\Get (
     *     path="/api/projects",
     *     tags={"Projects"},
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
     *                  property="logo",
     *                  type="string",
     *                  example="cdn.com/logo.png"
     *              ),
     *              @OA\Property(
     *                  property="banner",
     *                  type="string",
     *                  example="cdn.com/banner.png"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  example="example description"
     *              ),
     *              @OA\Property(
     *                  property="pool_amount",
     *                  type="number",
     *                  example="100"
     *              ),
     *              @OA\Property(
     *                  property="medium_username",
     *                  type="string",
     *                  example="@username"
     *              ),
     *              @OA\Property(
     *                  property="coin_type",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="blockchain",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="tags",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="content", type="number", example="example.com"),
     *                      @OA\Property(
     *                          property="tag",
     *                          @OA\Property(property="id", type="number", example="1"),
     *                          @OA\Property(property="name", type="string", example="Telegram"),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="social_links",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="content", type="number", example="example.com"),
     *                      @OA\Property(
     *                          property="social_link",
     *                          @OA\Property(property="id", type="number", example="1"),
     *                          @OA\Property(property="name", type="string", example="Telegram"),
     *                          @OA\Property(property="icon", type="string", example="cnd.com/telegram.png", nullable=true),
     *                      ),
     *                  ),
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
    public function index()
    {
        $projects = Project::with([
            'tags',
            'media',
            'tags.tag',
            'coinType',
            'blockchain',
            'socialLinks',
            'socialLinks.link',
            'socialLinks.link.media',
        ])->get();

        return response()->json(ProjectResource::collection($projects));
    }

    /**
     * Get Project
     * @OA\Get (
     *     path="/api/projects/{project}",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects"},
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
     *                  property="logo",
     *                  type="string",
     *                  example="cdn.com/logo.png"
     *              ),
     *              @OA\Property(
     *                  property="banner",
     *                  type="string",
     *                  example="cdn.com/banner.png"
     *              ),
     *              @OA\Property(
     *                  property="public",
     *                  type="boolean",
     *                  example="true",
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  example="example description"
     *              ),
     *              @OA\Property(
     *                  property="pool_amount",
     *                  type="number",
     *                  example="100"
     *              ),
     *              @OA\Property(
     *                  property="medium_username",
     *                  type="string",
     *                  example="@username"
     *              ),
     *              @OA\Property(
     *                  property="coin_type",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="blockchain",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="tags",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="content", type="number", example="example.com"),
     *                      @OA\Property(
     *                          property="tag",
     *                          @OA\Property(property="id", type="number", example="1"),
     *                          @OA\Property(property="name", type="string", example="Telegram"),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="social_links",
     *                  type="array",
     *                  deprecated=true,
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="content", type="string", example="example.com"),
     *                      @OA\Property(
     *                          property="social_link",
     *                          @OA\Property(property="id", type="number", example="1"),
     *                          @OA\Property(property="name", type="string", example="Telegram"),
     *                          @OA\Property(property="icon", type="string", example="cnd.com/telegram.png", nullable=true),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="social_providers",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(
     *                          property="name",
     *                          type="string",
     *                          example="test#1234",
     *                      ),
     *                      @OA\Property(property="provider_id", type="number", example="1"),
     *                      @OA\Property(
     *                          property="provider_name",
     *                          type="string",
     *                          enum={"twitter", "telegram_bot", "discord_bot"},
     *                          example="discord_bot",
     *                      ),
     *                  ),
     *              ),
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
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function show(Project $project)
    {
        $project->load([
            'tags',
            'showcaseTasks',
            'showcaseTasks.ambassadorTasksInWork',
            'showcaseTasks.ambassadorTasksInWork.ambassador',
            'media',
            'tags',
            'tags.tag',
            'coinType',
            'blockchain',
            'socialProviders',
            'socialLinks',
            'socialLinks.link',
            'socialLinks.link.media',
        ]);

        return response()->json(new ProjectResource($project));
    }

    /**
     * Create Project
     * @OA\Post (
     *     path="/api/projects",
     *     tags={"Projects"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="fulfill",
     *                      type="boolean",
     *                  ),
     *                  @OA\Property(
     *                      property="description",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="pool_amount",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="owner_email",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="coin_type_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="blockchain_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="medium_username",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="tags",
     *                      type="array",
     *                      @OA\Items(type="number"),
     *                  ),
     *                  @OA\Property(
     *                      property="social_links",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="number"),
     *                          @OA\Property(property="content", type="string"),
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="logo",
     *                      type="string",
     *                      format="binary",
     *                  ),
     *                  @OA\Property(
     *                      property="banner",
     *                      type="string",
     *                      format="binary",
     *                  ),
     *             ),
     *         ),
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
     *                  property="public",
     *                  type="boolean",
     *                  example="true",
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  example="example description"
     *              ),
     *              @OA\Property(
     *                  property="pool_amount",
     *                  type="number",
     *                  example="100"
     *              ),
     *              @OA\Property(
     *                  property="medium_username",
     *                  type="string",
     *                  example="@username"
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
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function store(ProjectCreateRequest $request)
    {
        $ownerEmail = $request->get('owner_email');

        $user = User::firstOrCreate([
            'email' => $ownerEmail,
        ], [
            'name' => $ownerEmail,
            'email' => $ownerEmail,
            'password' => Hash::make(Str::random(10)),
        ]);

        $project = Project::create(array_merge(['name' => $ownerEmail, 'owner_id' => $user->id], $request->validated()));
        if ($request->has('logo')) {
            $project->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        if ($request->has('banner')) {
            $project->addMediaFromRequest('banner')->toMediaCollection('banner');
        }

        if ($request->has('tags')) {
            $tagRecords = array_map(function ($tagId) {
                return [
                    'tag_id' => $tagId,
                ];
            }, $request->get('tags'));

            $project->tags()->createMany($tagRecords);
        }

        if ($request->filled('social_links')) {
            $socialLinkRecords = array_map(function ($socialLink) {
                return [
                    'content' => $socialLink['content'],
                    'social_link_id' => $socialLink['id'],
                ];
            }, $request->get('social_links'));

            $project->socialLinks()->createMany($socialLinkRecords);
        }

        $token = Str::uuid();
        $user->invitation()->create([
            'token' => $token,
            'status' => Invitation::STATUS_PENDING,
            'role_name' => 'Project Owner',
            'project_id' => $project->id,
        ]);

        dispatch(static function () use ($user, $token, $project) {
            $project->load(['tags', 'tags.tag', 'blockchain']);

            $ambassadors = Ambassador::all();
            $ambassadors->each->notify(new NewProjectCreatedNotification($project));

            $projects = Project::with(['socialProviders'])->get();
            $projects->each->notify(new NewProjectCreatedSocialNotification($project->name));

            $user->notify(new ProjectOwnerInvitationNotification($token, $project->name));
        })->afterResponse();

        return response()->json(new ProjectResource($project));
    }

    /**
     * Update Project
     * @OA\Put (
     *     path="/api/projects/{project}",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="public",
     *                      type="boolean",
     *                  ),
     *                  @OA\Property(
     *                      property="description",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="pool_amount",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="coin_type_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="blockchain_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="medium_username",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="tags",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="number"),
     *                          @OA\Property(property="tag_id", type="number"),
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="social_links",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="number"),
     *                          @OA\Property(property="content", type="string"),
     *                          @OA\Property(property="social_link_id", type="number"),
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="logo",
     *                      type="string",
     *                      format="binary",
     *                  ),
     *                  @OA\Property(
     *                      property="banner",
     *                      type="string",
     *                      format="binary",
     *                  ),
     *             ),
     *         ),
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
     *                  property="public",
     *                  type="boolean",
     *                  example="true",
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  example="example description"
     *              ),
     *              @OA\Property(
     *                  property="pool_amount",
     *                  type="number",
     *                  example="100"
     *              ),
     *              @OA\Property(
     *                  property="medium_username",
     *                  type="string",
     *                  example="@username"
     *              ),
     *              @OA\Property(
     *                  property="coin_type",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="blockchain",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="tags",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="content", type="number", example="example.com"),
     *                      @OA\Property(
     *                          property="tag",
     *                          @OA\Property(property="id", type="number", example="1"),
     *                          @OA\Property(property="name", type="string", example="Telegram"),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="social_links",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="content", type="number", example="example.com"),
     *                      @OA\Property(
     *                          property="social_link",
     *                          @OA\Property(property="id", type="number", example="1"),
     *                          @OA\Property(property="name", type="string", example="Telegram"),
     *                          @OA\Property(property="icon", type="string", example="cnd.com/telegram.png", nullable=true),
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="social_providers",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(
     *                          property="name",
     *                          type="string",
     *                          example="test#1234",
     *                      ),
     *                      @OA\Property(property="provider_id", type="number", example="1"),
     *                      @OA\Property(
     *                          property="provider_name",
     *                          type="string",
     *                          enum={"twitter", "telegram_bot", "discord_bot"},
     *                          example="discord_bot",
     *                      ),
     *                  ),
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
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
    public function update(Project $project, ProjectUpdateRequest $request)
    {
        // FIXME: duplicate code :c

        if ($request->has('logo')) {
            $project->clearMediaCollection('logo');
            $project->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        if ($request->has('banner')) {
            $project->clearMediaCollection('banner');
            $project->addMediaFromRequest('banner')->toMediaCollection('banner');
        }

        $tags = $request->get('tags');
        $tagIds = [];

        foreach ($tags as $tag) {
            if (isset($tag['id'])) {
                $tagIds[] = $tag['id'];
            }
        }

        $projectTags = $project->tags();
        $projectTags->whereNotIn('id', $tagIds)->delete();

        $upsertTags = array_map(function ($tag) use ($project) {
            if (!isset($tag['id'])) {
                $tag['id'] = null;
            }

            $tag['project_id'] = $project->id;
            return $tag;
        }, $tags);

        $projectTags->upsert($upsertTags, ['id']);

        if ($request->filled('social_links')) {
            $socialLinks = $request->get('social_links');
            $socialLinkIds = [];

            foreach ($socialLinks as $socialLink) {
                if (isset($socialLink['id'])) {
                    $socialLinkIds[] = $socialLink['id'];
                }
            }

            $projectSocialLinks = $project->socialLinks();
            $projectSocialLinks->whereNotIn('id', $socialLinkIds)->delete();

            $upsertSocialLinks = array_map(function ($socialLink) use ($project) {
                if (!isset($socialLink['id'])) {
                    $socialLink['id'] = null;
                }

                $socialLink['project_id'] = $project->id;
                return $socialLink;
            }, $socialLinks);

            $projectSocialLinks->upsert($upsertSocialLinks, ['id', 'content']);
        }

        $project->update($request->validated());
        $project->load([
            'showcaseTasks',
            'showcaseTasks.ambassadorTasksInWork',
            'showcaseTasks.ambassadorTasksInWork.ambassador',
            'media',
            'tags',
            'tags.tag',
            'coinType',
            'blockchain',
            'socialLinks',
            'socialLinks.link',
            'socialLinks.link.media',
            'socialProviders',
        ]);

        return response()->json(new ProjectResource($project));
    }

    /**
     * Delete Project
     * @OA\Delete (
     *     path="/api/projects/{project}",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects"},
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
    public function destroy(Project $project)
    {
        $project->delete();
        // FIXME: Remove this in future
        $project->tasks()->delete();
        return response()->noContent();
    }

    /**
     * Get Project Activities
     * @OA\Get (
     *     path="/api/projects/{project}/activities",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects"},
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
    public function activities(Project $project)
    {
        $activities = DB::table('user_tasks')
            ->select(['activities.id', 'activities.name'])
            ->where([
                ['user_tasks.status', '=', AmbassadorTask::STATUS_DONE],
                ['tasks.project_id', '=', $project->id],
                ['tasks.activity_id', '!=', null],
            ])
            ->leftJoin('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->leftJoin('activities', 'tasks.activity_id', '=', 'activities.id')
            ->groupBy('activities.id')
            ->get();

        return response()->json($activities);
    }

    /**
     * Get Project Social Providers
     * @OA\Get (
     *     path="/api/projects/{project}/social-providers",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="number", example="1"),
     *              @OA\Property(property="provider_id", type="number", example="1"),
     *              @OA\Property(
     *                  property="provider_name",
     *                  type="string",
     *                  enum={"twitter", "telegram_bot", "discord_bot"},
     *                  example="discord_bot",
     *              ),
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
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function providers(Project $project)
    {
        $project->load(['socialProviders']);
        return response()->json(SocialProviderResource::collection($project->socialProviders));
    }

    /**
     * Get Project Pending Reviews
     * @OA\Get (
     *     path="/api/projects/{project}/pending-reviews",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="number", example="1"),
     *              @OA\Property(
     *                  property="task",
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *                  @OA\Property(
     *                      property="project",
     *                      nullable=false,
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="name", type="string", example="example name"),
     *                  ),
     *                  @OA\Property(
     *                      property="activity",
     *                      nullable=true,
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="name", type="string", example="example name"),
     *                  ),
     *                  @OA\Property(
     *                      property="priority",
     *                      type="string",
     *                      enum={"low", "medium", "high"},
     *                  ),
     *                  @OA\Property(
     *                      property="started_at",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="ended_at",
     *                      type="string",
     *                  ),
     *              ),
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
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function pendingReviews(Project $project)
    {
        $ambassadorTasks = AmbassadorTask::whereRelation('task', 'project_id', $project->id)
            ->whereIn('status', [AmbassadorTask::STATUS_WAITING_FOR_REVIEW, AmbassadorTask::STATUS_ON_REVISION])
            ->with(['task.activity'])
            ->limit(5)
            ->get();

        return response()->json(PendingReviewAmbassadorTaskResource::collection($ambassadorTasks));
    }

    /**
     * Validate project name
     * @OA\Post (
     *     path="/api/projects/validate-name",
     *     tags={"Projects"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     * )
     */
    public function validateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:43|unique:projects,name',
        ]);

        return response()->noContent();
    }

    /**
     * Validate project owner email
     * @OA\Post (
     *     path="/api/projects/validate-email",
     *     tags={"Projects"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     * )
     */
    public function validateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:managers,email',
        ]);

        return response()->noContent();
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap(): array
    {
        return [
            'index' => 'viewAny',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }
}
