<?php

namespace App\Http\Controllers;

use App\Models\Ambassador;
use App\Http\Requests\{
    ProjectLeaderboardRequest,
    AmbassadorLeaderboardRequest,
};

use App\Http\Resources\AmbassadorLeaderboardCollection;

class LeaderboardController extends Controller
{
    /**
     * Leaderboard
     * @OA\Get (
     *     path="/api/leaderboard",
     *     tags={"Leaderboard"},
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
     *         name="activity_id",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="order_by_total_points",
     *         required=true,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example={"asc", "desc"}),
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
     *                  property="rank",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="points",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="tasks_done",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="tasks_points",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="total_points",
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
    public function index(AmbassadorLeaderboardRequest $request)
    {
        $users = Ambassador::query();

        $orderBy = $request->get('order_by_total_points') ?: 'desc';
        $activityId = $request->get('activity_id');

        if ($activityId) {
            $users = $users->whereHas('activities', function ($query) use ($activityId) {
                    $query->where('activity_id', $activityId)
                        ->active();
                })
                ->withCount(['tasksIsDone as tasks_count' => function ($query) use ($activityId) {
                    $query->withoutGlobalScopes()
                        ->whereRelation('task', fn ($q) => $q->withoutGlobalScopes()->where('activity_id', $activityId));
                }])
                ->withSum([
                    'levelPoints as task_points_count' => function ($query) use ($activityId) {
                        $query->where('activity_id', $activityId)
                            ->whereRaw('level = users.level');
                    },
                ], 'points')
                ->withSum([
                    'levelPoints as all_task_points_count' => fn ($query) => $query->where('activity_id', $activityId),
                ], 'points');
        } else {
            $users = $users->whereHas('activities', fn ($query) => $query->active())
                ->withCount(['tasksIsDone as tasks_count' => function ($query) {
                    $query->withoutGlobalScopes()
                        ->whereRelation('task', fn ($q) => $q->withoutGlobalScopes()->whereNotNull('activity_id'));
                }])
                ->withSum(['levelPoints as task_points_count' => function ($query) {
                    $query->whereNotNull('activity_id')
                        ->whereRaw('level = users.level');
                }], 'points')
                ->withSum([
                    'levelPoints as all_task_points_count' => fn ($query) => $query->whereNotNull('activity_id'),
                ], 'points');
        }

        if ($request->filled('levels')) {
            $users = $users->whereIn('level', $request->get('levels'));
        }

        $page = $request->get('page') ?: 1;
        $perPage = $request->get('per_page') ?: 15;

        $users = $users->orderBy('all_task_points_count', $orderBy)->paginate($perPage);
        $totalUsers = $users->total();

        $users->map(function ($user, $key) use ($page, $perPage, $orderBy, $totalUsers) {
            /**
             * @var $key int
             */
            $user['position'] = $orderBy === 'asc'
                ? ($totalUsers - ($perPage * ($page - 1))) - $key
                : ($key + ($perPage * ($page - 1))) + 1;

            return $user;
        });

        return response()->json(new AmbassadorLeaderboardCollection($users));
    }

    /**
     * Project Leaderboard
     * @OA\Get (
     *     path="/api/leaderboard/project",
     *     tags={"Leaderboard"},
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
     *         name="project_id",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="activity_id",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="order_by_total_points",
     *         required=true,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example={"asc", "desc"}),
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
     *                  property="points",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="tasks_done",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="tasks_points",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="total_points",
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
    public function project(ProjectLeaderboardRequest $request)
    {
        $users = Ambassador::query();

        $orderBy = $request->get('order_by_total_points') ?: 'desc';
        $projectId = $request->get('project_id');
        $activityId = $request->get('activity_id');

        if ($activityId) {
            $users = $users->whereHas('projectMembers', fn ($query) => $query->where('project_id', $projectId))
                ->whereHas('activities', function ($query) use ($activityId) {
                    $query->where('activity_id', $activityId)
                        ->active();
                })
                ->withCount(['tasksIsDone as tasks_count' => function ($query) use ($projectId, $activityId) {
                    $query->whereRelation('task', function ($query) use ($projectId, $activityId) {
                        $query->where('project_id', $projectId)
                            ->when($activityId, fn ($q) => $q->where('activity_id', $activityId));
                    });
                }])
                ->withSum([
                    'levelPoints as all_task_points_count' => function ($query) use ($projectId, $activityId) {
                        $query->where('project_id', $projectId)
                            ->when($activityId, fn ($q) => $q->where('activity_id', $activityId));
                    },
                ], 'points');
        } else {
            $users = $users->whereHas('projectMembers', fn ($query) => $query->where('project_id', $projectId))
                ->withCount(['tasksIsDone as tasks_count' => function ($query) use ($projectId) {
                    $query->whereRelation('task', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    });
                }])
                ->withSum([
                    'levelPoints as all_task_points_count' => function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    },
                ], 'points');
        }

        $page = $request->get('page') ?: 1;
        $perPage = $request->get('per_page') ?: 15;

        $users = $users->orderBy('all_task_points_count', $orderBy)->paginate($perPage);
        $totalUsers = $users->total();

        $users->map(function ($user, $key) use ($page, $perPage, $orderBy, $totalUsers) {
            /**
             * @var $key int
             */
            $user['position'] = $orderBy === 'asc'
                ? ($totalUsers - ($perPage * ($page - 1))) - $key
                : ($key + ($perPage * ($page - 1))) + 1;

            return $user;
        });

        return response()->json(new AmbassadorLeaderboardCollection($users));
    }
}
