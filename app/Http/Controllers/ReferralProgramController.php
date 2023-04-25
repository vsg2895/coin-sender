<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Resources\TaskReferral;

class ReferralProgramController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission'])->only(['index']);
    }

    /**
     * Get List Referral Program
     * @OA\Get (
     *     path="/api/referral-program",
     *     tags={"Referral Program"},
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
     *                      property="status",
     *                      type="string",
     *                      enum={"available", "upcoming", "finished"},
     *                      example="available",
     *                  ),
     *                  @OA\Property(
     *                      property="referrals",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="created_at",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="referral_name",
     *                              type="string",
     *                              example="example name"
     *                          ),
     *                          @OA\Property(
     *                              property="ambassador_name",
     *                              type="string",
     *                              example="example name"
     *                          ),
     *                      ),
     *                  ),
     *                  @OA\Property(property="referrals_count", type="number", example="1"),
     *                  @OA\Property(property="ambassadors_count", type="number", example="1"),
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
    public function index()
    {
        $tasks = Task::with([
            'referrals',
            'referrals.referral',
            'referrals.ambassador',
        ]);

        $user = auth()->user();
        $user->load(['roles']);

        if (!$user->hasRole('Super Admin')) {
            $tasks = $tasks->where('project_id', getPermissionsTeamId());
        }

        $tasks = $tasks->has('referrals')->get();
        $tasks->each(static function ($task) {
           $task->referrals_count = $task->referrals->groupBy('referral_id')->count();
           $task->ambassadors_count = $task->referrals->groupBy('user_id')->count();
        });

        return response()->json(TaskReferral::collection($tasks));
    }
}
