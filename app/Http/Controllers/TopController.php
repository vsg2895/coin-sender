<?php

namespace App\Http\Controllers;

use App\Models\{User, Ambassador};
use App\Http\Requests\TopTalentListRequest;
use App\Http\Resources\{
    ReviewerTop as ReviewerTopResource,
    AmbassadorTop as AmbassadorTopResource,
};

class TopController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission']);
    }

    /**
     * Get Top Talents
     * @OA\Get (
     *     path="/api/top/talents",
     *     tags={"Top"},
     *     @OA\Parameter(
     *         in="query",
     *         name="project_id",
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
     *                  example="example name"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="level",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="avatar",
     *                  type="string",
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
     *                          example="approved",
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
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function talents(TopTalentListRequest $request)
    {
        $projectId = $request->get('project_id');
        $ambassadors = Ambassador::with(['media', 'activities', 'activities.activity'])
            ->whereHas('activities', fn ($query) => $query->active());

        if ($request->has('project_id')) {
            $ambassadors = $ambassadors->whereHas('projectMembers', fn ($query) => $query->where('project_id', $projectId))
                ->withSum([
                    'levelPoints as all_task_points_count' => fn ($query) => $query->where('project_id', $projectId),
                ], 'points');
        } else {
            $ambassadors = $ambassadors->whereHas('projectMembers')
                ->withSum(['levelPoints as all_task_points_count'], 'points');
        }

        $ambassadors = $ambassadors->orderBy('all_task_points_count')
            ->limit(3)
            ->get();

        return response()->json(AmbassadorTopResource::collection($ambassadors));
    }

    /**
     * Get Top Reviewers
     * @OA\Get (
     *     path="/api/top/reviewers",
     *     tags={"Top"},
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
     *                  property="overdue",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="reviewed",
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
    public function reviewers()
    {
        /**
         * @var User $user
         */
        $user = auth()->user();
        $user->load(['roles']);

        $managers = User::withCount(['checkedTasks as reviewed' => function ($query) use ($user) {
            if (!$user->hasRole('Super Admin')) {
                $query->whereRelation('task', 'project_id', getPermissionsTeamId());
            }
        }])
            ->orderBy('reviewed')
            ->limit(6)
            ->get();

        return response()->json(ReviewerTopResource::collection($managers));
    }
}
