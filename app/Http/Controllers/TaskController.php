<?php

namespace App\Http\Controllers;

use App\Models\{
    Task,
    Project,
    Ambassador,
};

use App\Http\Requests\{
    TaskListRequest,
    TaskCreateRequest,
    TaskUpdateRequest,
    TaskCalculateEstimatedAmountRequest,
};

use App\Notifications\{
    TaskCreatedNotification,
    Social\TaskCreatedSocialNotification,
};

use App\Http\Resources\{
    Task as TaskResource,
    TaskProjectCollection,
};

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission'])->except(['show', 'index']);
        $this->authorizeResource(Task::class, 'task');
    }

    /**
     * Get List Tasks
     * @OA\Get (
     *     path="/api/tasks",
     *     tags={"Tasks"},
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
     *     @OA\Parameter(
     *         in="query",
     *         name="status",
     *         required=false,
     *         description="Status of tasks",
     *         @OA\Schema(type="string", enum={"available", "upcoming", "finished"}),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *                  @OA\Property(
     *                      property="id",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      example="example name"
     *                  ),
     *                  @OA\Property(
     *                      property="tasks",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="id",
     *                              type="number",
     *                              example="1",
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              type="string",
     *                              example="example name"
     *                          ),
     *                          @OA\Property(
     *                              property="text",
     *                              type="string",
     *                              example="example text",
     *                          ),
     *                          @OA\Property(
     *                              property="min_level",
     *                              type="number",
     *                              example="1",
     *                              nullable=true,
     *                          ),
     *                          @OA\Property(
     *                              property="max_level",
     *                              type="number",
     *                              example="1",
     *                              nullable=true,
     *                          ),
     *                          @OA\Property(
     *                              property="activity",
     *                              nullable=true,
     *                              @OA\Property(property="id", type="number", example="1"),
     *                              @OA\Property(property="name", type="string", example="example name"),
     *                          ),
     *                          @OA\Property(
     *                              property="priority",
     *                              type="string",
     *                              enum={"low", "medium", "high"},
     *                          ),
     *                          @OA\Property(
     *                              property="coin_type",
     *                              nullable=true,
     *                              @OA\Property(property="id", type="number", example="1"),
     *                              @OA\Property(property="name", type="string", example="example name"),
     *                          ),
     *                          @OA\Property(
     *                              property="autovalidate",
     *                              type="boolean",
     *                              default="false",
     *                          ),
     *                          @OA\Property(
     *                              property="verifier_driver",
     *                              type="string",
     *                              enum={"twitter", "telegram", "discord"},
     *                              nullable=true,
     *                          ),
     *                          @OA\Property(
     *                              property="started_at",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="ended_at",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="status_by_dates",
     *                              type="string",
     *                              enum={"available", "upcoming", "finished"},
     *                              example="available",
     *                          ),
     *                          @OA\Property(property="editing_not_available", type="boolean", default="false"),
     *                      ),
     *                  ),
     *         ),
     *     ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
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
    public function index(TaskListRequest $request)
    {
        $status = $request->get('status', 'all');
        $search = '%'.$request->get('search').'%';
        $hasSearch = $request->has('search');

        $searchFunction = function ($query) use ($status, $search, $hasSearch) {
            if ($hasSearch) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', $search)
                        ->orWhereRelation('project', 'name', 'LIKE', $search);
                });
            }

            $now = now();
            if ($status === 'upcoming') {
                $query->whereDate('started_at', '>', $now);
            } else if ($status === 'available') {
                $query->where(function ($query) use ($now) {
                    $query->whereDate('started_at', '<=', $now)
                        ->whereDate('ended_at', '>', $now->subDays(1));
                });
            } else if ($status === 'finished') {
                $query->whereDate('ended_at', '<=', $now->subDays(1));
            }

            $query->withCount('ambassadorTasks');
        };

        $tasks = Project::when($hasSearch, function ($query) use ($search) {
            $query->where('name', 'LIKE', $search);
        })->with([
            'tasks' => $searchFunction,
            'tasks.ambassadorTasksInWork.ambassador',
        ])->orWhereHas('tasks', $searchFunction);

        $tasks = $tasks->get();
        return response()->json(new TaskProjectCollection($tasks));
    }

    /**
     * Create task
     * @OA\Post (
     *     path="/api/tasks",
     *     tags={"Tasks"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="text",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="rewards",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="type",
     *                              type="string",
     *                              enum={"coins", "discord_role"},
     *                          ),
     *                          @OA\Property(
     *                              property="value",
     *                              type="string",
     *                          ),
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="conditions",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="type",
     *                              type="string",
     *                              enum={"discord_role"},
     *                          ),
     *                          @OA\Property(
     *                              property="value",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="operator",
     *                              enum={"="},
     *                          ),
     *                      ),
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="project_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="activity_id",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="coin_type_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="min_level",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="max_level",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="verifier_types",
     *                      type="array",
     *                      @OA\Items(type="string", enum={
     *                          "discord_invite",
     *                          "telegram_invite",
     *                          "twitter_like",
     *                          "twitter_tweet",
     *                          "twitter_reply",
     *                          "twitter_space",
     *                          "twitter_follow",
     *                          "twitter_retweet",
     *                      }),
     *                  ),
     *                  @OA\Property(
     *                      property="tweet_words",
     *                      type="array",
     *                      @OA\Items(type="string"),
     *                  ),
     *                  @OA\Property(
     *                      property="twitter_space",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="twitter_tweet",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="twitter_follow",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="discord_invite",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="telegram_invite",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="default_tweet",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="default_reply",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="verifier_driver",
     *                      type="string",
     *                      enum={"twitter", "telegram", "discord"},
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="started_at",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="ended_at",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="number_of_winners",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="number_of_invites",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="number_of_participants",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="images[]",
     *                      type="array",
     *                      @OA\Items(type="string", format="binary"),
     *                  ),
     *                  @OA\Property(
     *                      property="assign_user_ids[]",
     *                      type="array",
     *                      @OA\Items(type="number"),
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="invalid",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Project without discord social provider!",
     *              ),
     *          ),
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
     * @throws Exception
     */
    public function store(TaskCreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $task = Task::create($request->validated() + ['manager_id' => auth()->id()]);
            if ($request->has('images')) {
                $task->addMultipleMediaFromRequest(['images'])->each->toMediaCollection();
            }

            // FIXME: move to service?
            $verifierDriver = $request->get('verifier_driver');
            if ($verifierDriver) {
                $providerName = $verifierDriver === 'twitter'
                    ? $verifierDriver
                    : $verifierDriver.'_bot';

                if (!$task->project->socialProviders()->where('provider_name', $providerName)->exists()) {
                    throw new HttpException(400, "Project without $verifierDriver social provider!");
                }

                $discordInvite = $request->get('discord_invite');
                $discordGuildId = null;
                $discordGuildName = null;

                if ($verifierDriver === 'discord') {
                    $code = getDiscordInviteCode($request->get('discord_invite'));
                    $invite = getDiscordInvite($code);

                    if (isset($invite['guild'])) {
                        $discordGuildId = $invite['guild']['id'];
                        $discordGuildName = $invite['guild']['name'];
                    }
                }

                $task->verifier()->create([
                    'types' => $request->get('verifier_types'),
                    'invite_link' => $discordInvite ?: $request->get('telegram_invite'),
                    'tweet_words' => $request->get('tweet_words'),
                    'twitter_tweet' => $request->get('twitter_tweet'),
                    'twitter_space' => $request->get('twitter_space'),
                    'twitter_follow' => $request->get('twitter_follow'),
                    'default_tweet' => $request->get('default_tweet'),
                    'default_reply' => $request->get('default_reply'),
                    'discord_guild_id' => $discordGuildId,
                    'discord_guild_name' => $discordGuildName,
                ]);
            }

            $task->rewards()->createMany($request->get('rewards'));

            $conditions = $request->get('conditions');
            if (!empty($conditions)) {
                $task->conditions()->createMany($conditions);
            }

            $assignUserIds = $request->get('assign_user_ids');
            $availableAmbassadors = Ambassador::query()->whereNotNull('email');

            if (!empty($assignUserIds)) {
                $task->ambassadorAssignments()->sync($assignUserIds);
                $availableAmbassadors = $availableAmbassadors->whereIn('id', $assignUserIds);
            }

            if ($task->min_level) {
                $availableAmbassadors = $availableAmbassadors->where(function ($query) use ($task) {
                    $query->where('level', '>=', $task->min_level)
                        ->where('level', '<=', $task->max_level);
                });
            }

            if ($task->activity_id) {
                $availableAmbassadors = $availableAmbassadors->whereHas('activities', function ($query) use ($task) {
                    $query->where('activity_id', $task->activity_id)
                        ->active();
                });
            }

            if ($task->project_id) {
                $availableAmbassadors = $availableAmbassadors->whereHas('projectMembers', function ($query) use ($task) {
                    $query->where('project_id', $task->project_id);
                });
            }

            dispatch(static function () use ($task, $availableAmbassadors) {
                $task->load(['project']);
                $task->project->notify(new TaskCreatedSocialNotification($task));

                $availableAmbassadors = $availableAmbassadors->get();
                $availableAmbassadors->each->notify(new TaskCreatedNotification($task));
            })->afterResponse();

            DB::commit();
            return response()->noContent();
        } catch (Exception $error) {
            DB::rollBack();
            throw $error;
        }
    }

    /**
     * Update task
     * @OA\Put (
     *     path="/api/tasks/{task}",
     *     @OA\Parameter(
     *         in="path",
     *         name="task",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Tasks"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="text",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="rewards",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="type",
     *                              type="string",
     *                              enum={"coins", "discord_role"},
     *                          ),
     *                          @OA\Property(
     *                              property="value",
     *                              type="string",
     *                          ),
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="conditions",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="type",
     *                              type="string",
     *                              enum={"discord_role"},
     *                          ),
     *                          @OA\Property(
     *                              property="value",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="operator",
     *                              enum={"="},
     *                          ),
     *                      ),
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="project_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="activity_id",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="coin_type_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="min_level",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="max_level",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="verifier_types",
     *                      type="array",
     *                      @OA\Items(type="string", enum={
     *                          "discord_invite",
     *                          "telegram_invite",
     *                          "twitter_like",
     *                          "twitter_tweet",
     *                          "twitter_reply",
     *                          "twitter_space",
     *                          "twitter_follow",
     *                          "twitter_retweet",
     *                      }),
     *                  ),
     *                  @OA\Property(
     *                      property="tweet_words",
     *                      type="array",
     *                      @OA\Items(type="string"),
     *                  ),
     *                  @OA\Property(
     *                      property="twitter_space",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="twitter_tweet",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="twitter_follow",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="discord_invite",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="telegram_invite",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="default_tweet",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="default_reply",
     *                      type="string",
     *                      nullable="true",
     *                  ),
     *                  @OA\Property(
     *                      property="verifier_driver",
     *                      type="string",
     *                      enum={"twitter", "telegram", "discord"},
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="started_at",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="ended_at",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="number_of_winners",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="number_of_invites",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="number_of_participants",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="new_images[]",
     *                      type="array",
     *                      @OA\Items(type="string", format="binary"),
     *                  ),
     *                  @OA\Property(
     *                      property="assign_user_ids[]",
     *                      type="array",
     *                      @OA\Items(type="number"),
     *                  ),
     *                  @OA\Property(
     *                      property="delete_image_ids[]",
     *                      type="array",
     *                      @OA\Items(type="number"),
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="invalid",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Can't update this task!"),
     *          ),
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
    public function update(Task $task, TaskUpdateRequest $request)
    {
        $task->loadCount('ambassadorTasks');
        if ($task->editing_not_available) {
            return response()->json([
                'message' => 'Can\'t update this task!',
            ], 400);
        }

        // FIXME: move to service?
        $verifierDriver = $request->get('verifier_driver');
        if ($verifierDriver) {
            $providerName = $verifierDriver === 'twitter'
                ? $verifierDriver
                : $verifierDriver.'_bot';

            if (!$task->project->socialProviders()->where('provider_name', $providerName)->exists()) {
                throw new HttpException(400, "Project without $verifierDriver social provider!");
            }

            $discordInvite = $request->get('discord_invite');
            $discordGuildId = null;
            $discordGuildName = null;

            if ($verifierDriver === 'discord') {
                $code = getDiscordInviteCode($request->get('discord_invite'));
                $invite = getDiscordInvite($code);

                if (isset($invite['guild'])) {
                    $discordGuildId = $invite['guild']['id'];
                    $discordGuildName = $invite['guild']['name'];
                }
            }

            $verifier = $task->verifier;
            if ($verifier) {
                $verifier->update([
                    'types' => $request->get('verifier_types'),
                    'invite_link' => $discordInvite ?: $request->get('telegram_invite'),
                    'tweet_words' => $request->get('tweet_words'),
                    'twitter_tweet' => $request->get('twitter_tweet'),
                    'twitter_space' => $request->get('twitter_space'),
                    'twitter_follow' => $request->get('twitter_follow'),
                    'default_tweet' => $request->get('default_tweet'),
                    'default_reply' => $request->get('default_reply'),
                    'discord_guild_id' => $discordGuildId,
                    'discord_guild_name' => $discordGuildName,
                ]);
            } else {
                $task->verifier()->create([
                    'types' => $request->get('verifier_types'),
                    'invite_link' => $discordInvite ?: $request->get('telegram_invite'),
                    'tweet_words' => $request->get('tweet_words'),
                    'twitter_tweet' => $request->get('twitter_tweet'),
                    'twitter_space' => $request->get('twitter_space'),
                    'twitter_follow' => $request->get('twitter_follow'),
                    'default_tweet' => $request->get('default_tweet'),
                    'default_reply' => $request->get('default_reply'),
                    'discord_guild_id' => $discordGuildId,
                    'discord_guild_name' => $discordGuildName,
                ]);
            }
        } else {
            $task->verifier()->delete();
        }

        $rewards = $request->get('rewards');
        if (!empty($rewards)) {
            foreach ($rewards as $reward) {
                $task->rewards()->updateOrCreate([
                    'type' => $reward['type'],
                ], [
                    'value' => $reward['value'],
                ]);
            }
        }

        $conditions = $request->get('conditions');
        if (!empty($conditions)) {
            foreach ($conditions as $condition) {
                $task->conditions()->updateOrCreate([
                    'type' => $condition['type'],
                ], [
                   'value' => $condition['value'],
                   'operator' => $condition['operator'],
                ]);
            }
        } else {
            $task->conditions()->delete();
        }

        $deleteImageIds = $request->get('delete_image_ids');
        if (!empty($deleteImageIds)) {
            $task->media->reject(function (Media $currentMediaItem) use ($deleteImageIds) {
                return !in_array($currentMediaItem->getKey(), $deleteImageIds);
            })->each->delete();
        }

        if ($request->has('new_images')) {
            $task->addMultipleMediaFromRequest(['new_images'])->each->toMediaCollection();
        }

        $assignUserIds = $request->get('assign_user_ids');
        $availableAmbassadors = Ambassador::query()->whereNotNull('email');

        if (!empty($assignUserIds)) {
            $task->ambassadorAssignments()->sync($assignUserIds);
            $availableAmbassadors = $availableAmbassadors->whereIn('id', $assignUserIds);
        }

        if ($task->min_level) {
            $availableAmbassadors = $availableAmbassadors->where(function ($query) use ($task) {
                $query->where('level', '>=', $task->min_level)
                    ->where('level', '<=', $task->max_level);
            });
        }

        if ($task->activity_id) {
            $availableAmbassadors = $availableAmbassadors->whereHas('activities', function ($query) use ($task) {
                $query->where('activity_id', $task->activity_id)
                    ->active();
            });
        }

        if ($task->project_id) {
            $availableAmbassadors = $availableAmbassadors->whereHas('projectMembers', function ($query) use ($task) {
                $query->where('project_id', $task->project_id);
            });
        }

        dispatch(static function () use ($task, $availableAmbassadors) {
            $task->load(['project']);
            $task->project->notify(new TaskCreatedSocialNotification($task));

            $availableAmbassadors = $availableAmbassadors->whereDoesntHave('notifications', static function ($query) use ($task) {
                $query->where('type', 'new_task')
                    ->whereJsonContains('data->task_id', $task->id);
            })->get();

            $availableAmbassadors->each->notify(new TaskCreatedNotification($task));
        })->afterResponse();

        $task->update($request->validated());
        return response()->noContent();
    }

    /**
     * Get task
     * @OA\Get (
     *     path="/api/tasks/{task}",
     *     @OA\Parameter(
     *         in="path",
     *         name="task",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Tasks"},
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="example name"),
     *              @OA\Property(property="text", type="number", example="example text"),
     *              @OA\Property(
     *                  property="rewards",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="type",
     *                          type="string",
     *                          enum={"coins", "discord_role"},
     *                      ),
     *                      @OA\Property(
     *                          property="value",
     *                          type="string",
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="conditions",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="type",
     *                          type="string",
     *                          enum={"discord_role"},
     *                      ),
     *                      @OA\Property(
     *                          property="value",
     *                          type="string",
     *                      ),
     *                      @OA\Property(
     *                          property="operator",
     *                          type="string",
     *                          enum={"="},
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(property="min_level", type="number", example="1", nullable=true),
     *              @OA\Property(property="max_level", type="number", example="1", nullable=true),
     *              @OA\Property(
     *                  property="project",
     *                  nullable=false,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="activity",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="priority",
     *                  type="string",
     *                  enum={"low", "medium", "high"},
     *              ),
     *              @OA\Property(
     *                  property="coin_type",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="autovalidate",
     *                  type="boolean",
     *                  default="false",
     *              ),
     *              @OA\Property(
     *                  property="verifier_driver",
     *                  type="string",
     *                  enum={"twitter", "telegram", "discord"},
     *                  nullable=true,
     *              ),
     *              @OA\Property(
     *                  property="verifier",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="string", example="1"),
     *                  @OA\Property(
     *                      property="types",
     *                      type="array",
     *                      @OA\Items(type="string", enum={
     *                          "discord_invite",
     *                          "telegram_invite",
     *                          "twitter_like",
     *                          "twitter_tweet",
     *                          "twitter_reply",
     *                          "twitter_space",
     *                          "twitter_follow",
     *                          "twitter_retweet",
     *                      }),
     *                  ),
     *                  @OA\Property(property="invite_link", type="string", nullable=true),
     *                  @OA\Property(property="discord_guild_id", type="string", nullable=true),
     *                  @OA\Property(property="discord_guild_name", type="string", nullable=true),
     *                  @OA\Property(property="twitter_tweet", type="string", nullable=true),
     *                  @OA\Property(property="twitter_space", type="string", nullable=true),
     *                  @OA\Property(property="twitter_follow", type="string", nullable=true),
     *                  @OA\Property(property="default_reply", type="string", nullable=true),
     *                  @OA\Property(property="default_tweet", type="string", nullable=true),
     *                  @OA\Property(
     *                      property="tweet_words",
     *                      type="array",
     *                      @OA\Items(type="string"),
     *                  ),
     *              ),
     *              @OA\Property(property="started_at", type="string"),
     *              @OA\Property(property="ended_at", type="string"),
     *              @OA\Property(property="is_invite_friends", type="boolean", example="false"),
     *              @OA\Property(property="number_of_winners", type="number", example="0"),
     *              @OA\Property(property="number_of_invites", type="number", example="0"),
     *              @OA\Property(property="number_of_participants", type="number", example="0"),
     *              @OA\Property(
     *                  property="working_users",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="assigned_users",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="status_by_dates",
     *                  type="string",
     *                  enum={"available", "upcoming", "finished"},
     *                  example="available",
     *              ),
     *              @OA\Property(property="editing_not_available", type="boolean", default="false"),
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
    public function show(Task $task)
    {
        $task->load([
            'media',
            'conditions',
            'rewards',
            'verifier',
            'ambassadorTasksInWork.ambassador',
            'ambassadorAssignments'
        ]);

        return response()->json(new TaskResource($task));
    }

    /**
     * Delete task
     * @OA\Delete (
     *     path="/api/tasks/{task}",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         in="path",
     *         name="task",
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
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can't delete this task!"),
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
    public function destroy(Task $task)
    {
        $task->loadCount('ambassadorTasks');
        if ($task->ambassador_tasks_count > 0) {
            return response()->json([
                'message' => 'Can\'t delete this task!',
            ], 400);
        }

        $task->delete();
        return response()->noContent();
    }

    /**
     * Calculate estimated amount
     * @OA\Post (
     *     path="/api/tasks/estimated-amount",
     *     tags={"Tasks"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="rewards",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="type",
     *                              type="string",
     *                              enum={"coins", "discord_role"},
     *                          ),
     *                          @OA\Property(
     *                              property="value",
     *                              type="string",
     *                          ),
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="min_level",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="max_level",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="activity_id",
     *                      type="number",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="assign_user_ids[]",
     *                      type="array",
     *                      @OA\Items(type="number"),
     *                  ),
     *                  @OA\Property(
     *                      property="number_of_participants",
     *                      type="number",
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(property="min", type="number", example="0"),
     *              @OA\Property(property="max", type="number", example="0"),
     *              @OA\Property(property="total", type="number", example="0"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="invalid",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Can't update this task!"),
     *          ),
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
    public function calculateEstimatedAmount(TaskCalculateEstimatedAmountRequest $request)
    {
        return response()->json(calculateTaskEstimatedAmount($request->validated()));
    }
}
