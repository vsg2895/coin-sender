<?php

namespace App\Http\Controllers;

use App\Models\{
    AmbassadorSkill,
    AmbassadorActivity,
    AmbassadorActivityLink,
};

class AmbassadorActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission']);
        $this->authorizeResource(AmbassadorActivity::class, 'ambassadorActivity');
    }

    /**
     * Approve ambassador activities
     * @OA\Post (
     *     path="/api/activities/approve/{activity}",
     *     tags={"Activities"},
     *     @OA\Parameter(
     *         in="path",
     *         name="activity",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
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
     *      @OA\Response(
     *          response=403,
     *          description="unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="This action is unauthorized."),
     *          ),
     *      ),
     *      security={{ "apiAuth": {} }},
     * )
     */
    public function approve(AmbassadorActivity $ambassadorActivity)
    {
        $ambassadorActivity->update(['status' => AmbassadorActivity::STATUS_APPROVED]);
        return response()->noContent();
    }

    /**
     * Decline ambassador activities
     * @OA\Post (
     *     path="/api/activities/decline/{activity}",
     *     tags={"Activities"},
     *     @OA\Parameter(
     *         in="path",
     *         name="activity",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
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
     *      @OA\Response(
     *          response=403,
     *          description="unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="This action is unauthorized."),
     *          ),
     *      ),
     *      security={{ "apiAuth": {} }},
     * )
     */
    public function decline(AmbassadorActivity $ambassadorActivity)
    {
        $ambassadorActivity->update(['status' => AmbassadorActivity::STATUS_DECLINED]);
        return response()->noContent();
    }

    /**
     * Delete activity
     * @OA\Delete (
     *     path="/api/activities/{activity}",
     *     tags={"Activities"},
     *     @OA\Parameter(
     *         in="path",
     *         name="activity",
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
    public function destroy(AmbassadorActivity $ambassadorActivity)
    {
        $userId = $ambassadorActivity->user_id;

        $ambassadorActivity->load([
            'activity',
            'activity.links',
            'activity.skills',
        ]);

        $ambassadorActivity->delete();

        AmbassadorSkill::whereIn('id', $ambassadorActivity->activity->skills->keys())
            ->where('user_id', $userId)
            ->delete();

        AmbassadorActivityLink::whereIn('id', $ambassadorActivity->activity->links->keys())
            ->where('user_id', $userId)
            ->delete();

        return response()->noContent();
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'approve' => 'approve',
            'decline' => 'decline',
            'destroy' => 'delete',
        ];
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * @return array
     */
    protected function resourceMethodsWithoutModels()
    {
        return [];
    }
}
