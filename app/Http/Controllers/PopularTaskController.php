<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\PopularTaskListRequest;
use App\Http\Resources\PopularTask as PopularTaskResource;

class PopularTaskController extends Controller
{
    /**
     * Get Popular Tasks
     * @OA\Get (
     *     path="/api/tasks/popular",
     *     @OA\Parameter(
     *         in="query",
     *         name="project_id",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Tasks"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="number", example="1"),
     *              @OA\Property(
     *                  property="task",
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *                  @OA\Property(property="deadline", type="string"),
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
     *                      property="times_completed",
     *                      type="number",
     *                      example="1",
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
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function index(PopularTaskListRequest $request)
    {
        $projectId = $request->get('project_id');

        $tasks = Task::query();
        if ($request->has('project_id')) {
            $tasks = $tasks->whereRelation('project', function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
                ->with(['activity'])
                ->whereHas('ambassadorTasksCompleted', function ($query) use ($projectId) {
                    $query->whereRelation('task', 'project_id', $projectId);
                })
                ->withCount(['ambassadorTasksCompleted' => function ($query) use ($projectId) {
                    $query->whereRelation('task', 'project_id', $projectId);
                }]);
        } else {
            $tasks = $tasks->with(['project', 'activity'])
                ->whereHas('ambassadorTasksCompleted')
                ->withCount(['ambassadorTasksCompleted']);
        }

        $tasks = $tasks->orderBy('ambassador_tasks_completed_count')
            ->limit(5)
            ->get();

        return response()->json(PopularTaskResource::collection($tasks));
    }
}
