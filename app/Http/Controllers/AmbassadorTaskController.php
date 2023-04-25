<?php

namespace App\Http\Controllers;

use App\Models\AmbassadorTask;

use App\Http\Requests\{
    AmbassadorTaskDoneRequest,
    AmbassadorTaskListRequest,
    AmbassadorTaskReturnRequest,
};

use App\Http\Resources\{
    AmbassadorTaskCollection,
    AmbassadorTask as AmbassadorTaskResource,
};

use App\Notifications\{
    TaskAcceptedNotification,
    TaskRejectedNotification,
    TaskReturnedNotification,
    TaskOnRevisionNotification,
    Social\AmbassadorTaskCompletedSocialNotification,
};

use Illuminate\Support\Facades\DB;

class AmbassadorTaskController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission'])->except(['show']);
        $this->authorizeResource(AmbassadorTask::class, 'ambassadorTask');
    }

    /**
     * Get List Ambassador Tasks
     * @OA\Get (
     *     path="/api/ambassadors/tasks",
     *     tags={"Ambassador Tasks"},
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
     *         name="to_date",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="from_date",
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
     *         @OA\Schema(type="string", enum={"done", "rejected", "on_revision", "waiting_for_review"}, example={"done", "on_revision", "waiting_for_review"}),
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
     *                  property="user",
     *                  @OA\Property(
     *                      property="id",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      example="example name",
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="task",
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
     *                      property="text",
     *                      type="string",
     *                      example="example text",
     *                  ),
     *                  @OA\Property(
     *                      property="min_level",
     *                      type="number",
     *                      example="1",
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="max_level",
     *                      type="number",
     *                      example="1",
     *                      nullable=true,
     *                  ),
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
     *                      property="coin_type",
     *                      nullable=true,
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="name", type="string", example="example name"),
     *                  ),
     *                  @OA\Property(
     *                      property="autovalidate",
     *                      type="boolean",
     *                      default="false",
     *                  ),
     *                  @OA\Property(
     *                      property="verifier_driver",
     *                      type="string",
     *                      enum={"twitter", "telegram", "discord"},
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="started_at",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="ended_at",
     *                      type="string",
     *                  ),
     *                  @OA\Property(property="editing_not_available", type="boolean", default="false"),
     *              ),
     *              @OA\Property(
     *                  property="status",
     *                  type="string",
     *                  enum={"done", "returned", "on_revision", "in_progress", "waiting_for_review"},
     *                  example="in_progress",
     *              ),
     *              @OA\Property(
     *                  property="user_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="task_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="manager_id",
     *                  nullable=true,
     *                  type="number",
     *                  example="1",
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
    public function index(AmbassadorTaskListRequest $request)
    {
        $user = auth()->user();
        $status = $request->get('status', 'all');
        $search = '%'.$request->get('search').'%';

        $tasks = AmbassadorTask::where('status', $status === 'all'
            ? [
                AmbassadorTask::STATUS_DONE,
                AmbassadorTask::STATUS_REJECTED,
                AmbassadorTask::STATUS_ON_REVISION,
                AmbassadorTask::STATUS_WAITING_FOR_REVIEW,
            ]
            : $status
        )->with(['task', 'manager', 'ambassador']);

        if ($request->has('search')) {
            $tasks = $tasks->where(function ($query) use ($search) {
                $query->whereRelation('ambassador', 'name', 'LIKE', $search)
                    ->orWhereRelation('task', 'name', 'LIKE', $search);
            });
        }

        $orderByColumn = 'id';
        if ($status === AmbassadorTask::STATUS_DONE) {
            $orderByColumn = 'completed_at';
        } else if ($status === AmbassadorTask::STATUS_ON_REVISION) {
            $orderByColumn = 'revised_at';
            $user->load(['roles']);
            if (!$user->hasAnyRole(['Super Admin', 'Catapult Manager', 'Project Administrator', 'Project Owner'])) {
                $tasks = $tasks->where('manager_id', auth()->id());
            }
        } if ($status === AmbassadorTask::STATUS_WAITING_FOR_REVIEW) {
            $orderByColumn = 'reported_at';
        }

        $tasks = $tasks->orderByDesc($orderByColumn)->paginate($request->get('per_page') ?: 10);
        return response()->json(new AmbassadorTaskCollection($tasks));
    }

    /**
     * Get ambassador task
     * @OA\Get (
     *     path="/api/ambassadors/tasks/{ambassadorTask}",
     *     tags={"Ambassador Tasks"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassadorTask",
     *         required=true,
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
     *                  property="user",
     *                  @OA\Property(
     *                      property="id",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      example="example name",
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="task",
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
     *                      property="text",
     *                      type="string",
     *                      example="example text",
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
     *                              type="string",
     *                              enum={"="},
     *                          ),
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="min_level",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="max_level",
     *                      type="number",
     *                      example="1",
     *                  ),
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
     *                      property="coin_type",
     *                      nullable=true,
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="name", type="string", example="example name"),
     *                  ),
     *                  @OA\Property(
     *                      property="autovalidate",
     *                      type="boolean",
     *                      default="false",
     *                  ),
     *                  @OA\Property(
     *                      property="verifier_driver",
     *                      type="string",
     *                      enum={"twitter", "telegram", "discord"},
     *                      nullable=true,
     *                  ),
     *                  @OA\Property(
     *                      property="started_at",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="ended_at",
     *                      type="string",
     *                  ),
     *                  @OA\Property(property="editing_not_available", type="boolean", default="false"),
     *              ),
     *              @OA\Property(
     *                  property="report",
     *                  nullable=true,
     *                  type="string",
     *                  example="example report",
     *              ),
     *              @OA\Property(
     *                  property="report_attachments",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="url", type="string", example="https://example.com/1.png"),
     *                      @OA\Property(property="mime_type", type="string", example="image/png"),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="status",
     *                  type="string",
     *                  enum={"done", "returned", "on_revision", "in_progress", "waiting_for_review"},
     *                  example="in_progress",
     *              ),
     *              @OA\Property(
     *                  property="user_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="task_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="invites",
     *                  nullable=true,
     *                  @OA\Property(property="code", type="string"),
     *                  @OA\Property(property="count", type="number", example="0"),
     *              ),
     *              @OA\Property(
     *                  property="manager_id",
     *                  nullable=true,
     *                  type="number",
     *                  example="1",
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
    public function show(AmbassadorTask $ambassadorTask)
    {
        $ambassadorTask->load(['media', 'ambassador', 'task', 'task.media', 'task.rewards', 'task.conditions']);

        if ($ambassadorTask->task->is_invite_friends) {
            $ambassadorTask->loadCount(['referrals']);
        }

        return response()->json(new AmbassadorTaskResource($ambassadorTask));
    }

    /**
     * Ambassador task done
     * @OA\Post (
     *     path="/api/ambassadors/tasks/done/{ambassadorTask}",
     *     tags={"Ambassador Tasks"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassadorTask",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="rating",
     *                      type="number",
     *                  ),
     *             ),
     *         ),
     *      ),
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
     *                  property="status",
     *                  type="string",
     *                  enum={"done", "returned", "on_revision", "in_progress", "waiting_for_review"},
     *                  example="done",
     *              ),
     *              @OA\Property(
     *                  property="user_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="task_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="manager_id",
     *                  nullable=true,
     *                  type="number",
     *                  example="1",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can't mark this task as done!"),
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
    public function done(AmbassadorTask $ambassadorTask, AmbassadorTaskDoneRequest $request)
    {
        if ($ambassadorTask->status !== AmbassadorTask::STATUS_ON_REVISION) {
            return response()->json([
                'message' => 'Can\'t mark this task as done!',
            ], 400);
        }

        $task = $ambassadorTask->task;
        $rating = $request->get('rating');
        $projectId = $task->project_id;

        $ambassadorTask->load(['task', 'ambassador']);
        $ambassador = $ambassadorTask->ambassador;

        foreach ($task->rewards as $reward) {
            app($reward->type, ['taskReward' => $reward, 'rating' => $rating])->giveTo($ambassador, $task);
        }

        $ambassador->update([
            'points' => DB::raw("points + $rating"),
            'total_points' => DB::raw("total_points + $rating"),
        ]);

        $levelPoints = $ambassador->levelPoints()->firstOrCreate([
            'level' => $ambassador->level,
            'project_id' => $projectId,
            'activity_id' => $task->activity_id,
        ], [
            'points' => 0,
        ]);

        $levelPoints->increment('points', $rating);

        $ambassadorTask->update([
            'status' => AmbassadorTask::STATUS_DONE,
            'rating' => $rating,
            'completed_at' => now(),
        ]);

        // FIXME: Move to event? Best conditions? Rework logic?
        if ($ambassador->tasksIsDone()->whereRelation('task.project', 'id', $projectId)->count() === 1) {
            $ambassadorProject = $ambassador->projectMembers()->where('project_id', $projectId)
                ->whereNotNull('referral_code')
                ->first();

            if ($ambassadorProject) {
                $ambassadorTaskByReferralCode = AmbassadorTask::firstWhere('referral_code', $ambassadorProject->referral_code);
                $ambassadorTaskByReferralCode?->referrals()->create([
                    'user_id' => $ambassadorTaskByReferralCode->user_id,
                    'task_id' => $ambassadorTaskByReferralCode->task_id,
                    'referral_id' => $ambassador->id,
                ]);
            }
        }

        dispatch(static function () use ($task, $ambassador, $ambassadorTask) {
            $managerName = $ambassadorTask->manager->name;

            $ambassador->notify(new TaskAcceptedNotification($managerName, $ambassadorTask));
            $task->project->notify(new AmbassadorTaskCompletedSocialNotification(
                $ambassadorTask,
                $ambassador->name,
                $managerName,
            ));
        })->afterResponse();

        return response()->json($ambassadorTask);
    }

    /**
     * Ambassador task reject
     * @OA\Post (
     *     path="/api/ambassadors/tasks/reject/{ambassadorTask}",
     *     tags={"Ambassador Tasks"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassadorTask",
     *         required=true,
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
     *                  property="status",
     *                  type="string",
     *                  enum={"done", "rejected", "returned", "on_revision", "in_progress", "waiting_for_review"},
     *                  example="done",
     *              ),
     *              @OA\Property(
     *                  property="user_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="task_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="manager_id",
     *                  nullable=true,
     *                  type="number",
     *                  example="1",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can't reject this task!"),
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
    public function reject(AmbassadorTask $ambassadorTask)
    {
        if ($ambassadorTask->status !== AmbassadorTask::STATUS_ON_REVISION) {
            return response()->json([
                'message' => 'Can\'t reject this task!',
            ], 400);
        }

        $ambassadorTask->update(['status' => AmbassadorTask::STATUS_REJECTED]);
        $ambassadorTask->ambassador->notify(new TaskRejectedNotification(
            $ambassadorTask->manager->name,
            $ambassadorTask,
        ));

        return response()->json($ambassadorTask);
    }

    /**
     * Ambassador task return
     * @OA\Post (
     *     path="/api/ambassadors/tasks/return/{ambassadorTask}",
     *     tags={"Ambassador Tasks"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassadorTask",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="text",
     *                      type="string",
     *                  ),
     *             ),
     *         ),
     *      ),
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
     *                  property="status",
     *                  type="string",
     *                  enum={"done", "returned", "on_revision", "in_progress", "waiting_for_review"},
     *                  example="done",
     *              ),
     *              @OA\Property(
     *                  property="user_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="task_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="manager_id",
     *                  nullable=true,
     *                  type="number",
     *                  example="1",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can't return this task!"),
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
    public function return(AmbassadorTask $ambassadorTask, AmbassadorTaskReturnRequest $request)
    {
        if ($ambassadorTask->status !== AmbassadorTask::STATUS_ON_REVISION) {
            return response()->json([
                'message' => 'Can\'t return this task!',
            ], 400);
        }

        $text = $request->get('text');

        $ambassadorTask->load(['task', 'ambassador']);
        $ambassadorTask->update(['status' => AmbassadorTask::STATUS_RETURNED]);

        $ambassadorTask->comments()->create([
            'text' => $text,
            'user_id' => $ambassadorTask->user_id,
        ]);

        $ambassadorTask->ambassador->notify(new TaskReturnedNotification(
            $text,
            $ambassadorTask->manager->name,
            $ambassadorTask,
        ));

        return response()->json($ambassadorTask);
    }

    /**
     * Take ambassador task on revision
     * @OA\Post (
     *     path="/api/ambassadors/tasks/take-on-revision/{ambassadorTask}",
     *     tags={"Ambassador Tasks"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassadorTask",
     *         required=true,
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
     *                  property="status",
     *                  type="string",
     *                  enum={"done", "returned", "on_revision", "in_progress", "waiting_for_review"},
     *                  example="done",
     *              ),
     *              @OA\Property(
     *                  property="user_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="task_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="manager_id",
     *                  nullable=true,
     *                  type="number",
     *                  example="1",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can't take this task on revision!"),
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
    public function takeOnRevision(AmbassadorTask $ambassadorTask)
    {
        if ($ambassadorTask->status !== AmbassadorTask::STATUS_WAITING_FOR_REVIEW) {
            return response()->json([
                'message' => 'Can\'t take this task on revision!',
            ], 400);
        }

        $user = auth()->user();
        $ambassadorTask->update([
            'status' => AmbassadorTask::STATUS_ON_REVISION,
            'manager_id' => $user->id,
            'revised_at' => now(),
        ]);

        $ambassadorTask->ambassador->notify(new TaskOnRevisionNotification(
            $user->name,
            $ambassadorTask,
        ));

        return response()->json($ambassadorTask);
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return array_merge(parent::resourceAbilityMap(), [
            'done' => 'done',
            'return' => 'return',
            'takeOnRevision' => 'takeOnRevision',
        ]);
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * @return array
     */
    protected function resourceMethodsWithoutModels()
    {
        return ['index'];
    }
}
