<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission']);
    }

    /**
     * Dashboard
     * @OA\Get (
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(property="total", type="number", example="1"),
     *              @OA\Property(property="upcoming", type="number", example="1"),
     *              @OA\Property(property="available", type="number", example="1"),
     *              @OA\Property(property="finished", type="number", example="1"),
     *              @OA\Property(
     *                  property="on_review",
     *                  @OA\Property(property="total", type="number", example="1"),
     *                  @OA\Property(property="rejected", type="number", example="1"),
     *                  @OA\Property(property="on_revision", type="number", example="1"),
     *                  @OA\Property(property="to_approve", type="number", example="1"),
     *                  @OA\Property(property="approved", type="number", example="1"),
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
     */
    public function index()
    {
        // FIXME: Minimize conditions

        $user = auth()->user();
        $user->load(['roles']);

        $projectIds = $user->hasRole('Super Admin') ? [] : $user->projectMembers->pluck('project_id')->toArray();
        $projectIdPlaceholders = implode(',', array_fill(0, count($projectIds), '?'));

        /**
         * @var $sumTasks object|null
         * @var $sumOnReviewUserTasks object|null
         */
        $sumTasks = null;
        $sumOnReviewUserTasks = null;

        if ($projectIdPlaceholders === '') {
            if (!$user->hasRole('Catapult Manager')) {
                $sumTasks = DB::selectOne("
                    SELECT
                        COUNT(*) AS total,
                        SUM(IF(date(`started_at`) > CURDATE(), 1, 0)) AS upcoming,
                        SUM(IF(date(`started_at`) <= CURDATE() AND date(`ended_at`) > SUBDATE(CURDATE(), 1), 1, 0)) AS available,
                        SUM(IF(date(`ended_at`) <= SUBDATE(CURDATE(), 1), 1, 0)) AS finished
                    FROM tasks
                ");

                $sumOnReviewUserTasks = DB::selectOne("
                    SELECT
                        SUM(IF(status IN ('done', 'reject', 'on_revision', 'waiting_for_review'), 1, 0)) AS total,
                        SUM(IF(status = 'rejected', 1, 0)) AS rejected,
                        SUM(IF(status = 'on_revision', 1, 0)) AS on_revision,
                        SUM(IF(status = 'waiting_for_review', 1, 0)) AS to_approve,
                        SUM(IF(status = 'done', 1, 0)) AS approved
                    FROM user_tasks
                ");
            }
        } else {
            $sumTasks = DB::selectOne("
                SELECT
                    COUNT(*) AS total,
                    SUM(IF(date(`started_at`) > CURDATE(), 1, 0)) AS upcoming,
                    SUM(IF(date(`started_at`) <= CURDATE() AND date(`ended_at`) > SUBDATE(CURDATE(), 1), 1, 0)) AS available,
                    SUM(IF(date(`ended_at`) <= SUBDATE(CURDATE(), 1), 1, 0)) AS finished
                FROM tasks WHERE `tasks`.`project_id` IN ($projectIdPlaceholders)
            ", $projectIds);

            if ($user->hasAnyRole(['Catapult Manager', 'Project Administrator', 'Project Owner'])) {
                $sumOnReviewUserTasks = DB::selectOne("
                    SELECT
                        SUM(IF(status IN ('done', 'rejected', 'on_revision', 'waiting_for_review'), 1, 0)) AS total,
                        SUM(IF(status = 'rejected', 1, 0)) AS rejected,
                        SUM(IF(status = 'on_revision', 1, 0)) AS on_revision,
                        SUM(IF(status = 'waiting_for_review', 1, 0)) AS to_approve,
                        SUM(IF(status = 'done', 1, 0)) AS approved
                    FROM user_tasks WHERE EXISTS (SELECT * FROM `tasks` WHERE `tasks`.`id` = `user_tasks`.`task_id` AND `tasks`.`project_id` IN ($projectIdPlaceholders))
                ", $projectIds);
            } else {
                $userId = auth()->id();
                $sumOnReviewUserTasks = DB::selectOne("
                    SELECT
                        SUM(IF((status IN ('done', 'on_revision') AND manager_id = ?) OR status IN ('rejected', 'waiting_for_review'), 1, 0)) AS total,
                        SUM(IF(status = 'rejected', 1, 0)) AS rejected,
                        SUM(IF(status = 'on_revision' AND manager_id = ?, 1, 0)) AS on_revision,
                        SUM(IF(status = 'waiting_for_review', 1, 0)) AS to_approve,
                        SUM(IF(status = 'done', 1, 0)) AS approved
                    FROM user_tasks WHERE EXISTS (SELECT * FROM `tasks` WHERE `tasks`.`id` = `user_tasks`.`task_id` AND `tasks`.`project_id` IN ($projectIdPlaceholders))
                ", array_merge([$userId, $userId, $userId], $projectIds));
            }
        }

        $result = [
            'total' => 0,
            'finished' => 0,
            'upcoming' => 0,
            'available' => 0,
            'on_review' => [
                'total' => 0,
                'rejected' => 0,
                'on_revision' => 0,
                'to_approve' => 0,
                'approved' => 0,
            ],
        ];

        if (!is_null($sumTasks)) {
            $result['total'] = (int) ($sumTasks->total ?? 0);
            $result['finished'] = (int) ($sumTasks->finished ?? 0);
            $result['upcoming'] = (int) ($sumTasks->upcoming ?? 0);
            $result['available'] = (int) ($sumTasks->available ?? 0);
        }

        if (!is_null($sumOnReviewUserTasks)) {
            $result['on_review'] = [
                'total' => (int) ($sumOnReviewUserTasks->total ?? 0),
                'on_revision' => (int) ($sumOnReviewUserTasks->on_revision ?? 0),
                'to_approve' => (int) ($sumOnReviewUserTasks->to_approve ?? 0),
                'approved' => (int) ($sumOnReviewUserTasks->approved ?? 0),
                'rejected' => (int) ($sumOnReviewUserTasks->rejected ?? 0),
            ];
        }

        return response()->json($result);
    }

    /**
     * Dashboard Overview
     * @OA\Get (
     *     path="/api/dashboard/overview",
     *     tags={"Dashboard"},
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(property="talents", type="number", example="1"),
     *              @OA\Property(property="projects", type="number", example="1"),
     *              @OA\Property(property="usdtPaid", type="number", example="1"),
     *              @OA\Property(property="managers", type="number", example="1"),
     *              @OA\Property(property="allManagers", type="number", example="1"),
     *              @OA\Property(property="pendingClaims", type="number", example="1"),
     *              @OA\Property(property="reviewedTasks", type="number", example="1"),
     *              @OA\Property(property="activeTalents", type="number", example="1"),
     *              @OA\Property(property="joiningTalents", type="number", example="1"),
     *              @OA\Property(property="overdueDeadlines", type="number", example="1"),
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
    public function overview()
    {
        $user = auth()->user();
        $user->load(['roles']);

        $data = [
            'talents' => 0,
            'projects' => 0,
            'usdtPaid' => 0,
            'managers' => 0,
            'allManagers' => 0,
            'pendingClaims' => 0,
            'reviewedTasks' => 0,
            'activeTalents' => 0,
            'joiningTalents' => 0,
            'overdueDeadlines' => 0,
        ];

        return response()->json($data);
    }
}
