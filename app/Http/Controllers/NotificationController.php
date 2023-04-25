<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationReadRequest;
use App\Http\Resources\UserNotification as UserNotificationResource;

use Illuminate\Http\{Response, JsonResponse};

class NotificationController extends Controller
{
    /**
     * Get Notifications
     * @OA\Get (
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                          example="1",
     *                      ),
     *                      @OA\Property(
     *                          property="type",
     *                          type="string",
     *                          enum={
     *                              "task_on_review",
     *                              "approve_activity",
     *                              "withdrawal_request",
     *                              "task_after_revision",
     *                          },
     *                          example="task_on_review",
     *                      ),
     *                      @OA\Property(
     *                          property="read",
     *                          type="boolean",
     *                          example="false",
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          @OA\Property(property="task_id", type="number", nullable=true),
     *                          @OA\Property(property="task_name", type="number", nullable=true),
     *                          @OA\Property(property="project_id", type="number", nullable=true),
     *                          @OA\Property(property="project_name", type="string", nullable=true),
     *                          @OA\Property(property="activity_name", type="string", nullable=true),
     *                          @OA\Property(property="ambassador_id", type="number", nullable=true),
     *                          @OA\Property(property="ambassador_name", type="string", nullable=true),
     *                          @OA\Property(property="invitation_status", type="string", nullable=true),
     *                      ),
     *                      @OA\Property(
     *                          property="buttons",
     *                          @OA\Property(property="accept", type="string", nullable=true),
     *                          @OA\Property(property="reject", type="string", nullable=true),
     *                      ),
     *                      @OA\Property(property="created_at", type="string"),
     *                      @OA\Property(property="invitation_token", type="string", nullable=true),
     *                  ),
     *              ),
     *              @OA\Property(property="unread_count", type="number", example="0"),
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
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $notifications = $user->notifications()->with(['invitation'])->get();

        return response()->json([
            'data' => UserNotificationResource::collection($notifications),
            'unread_count' => $notifications->filter(fn ($notification) => $notification->unread())->count(),
        ]);
    }

    /**
     * Read Notifications
     * @OA\Post (
     *     path="/api/notifications/read",
     *     tags={"Notifications"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="ids",
     *                      type="array",
     *                      @OA\Items(type="number"),
     *                  ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="no content",
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
    public function read(NotificationReadRequest $request): Response
    {
        $ids = $request->get('ids');
        $user = auth()->user();

        $user->unreadNotifications->when(!empty($ids), fn ($query) => $query->whereIn('id', $ids))
            ->markAsRead();

        return response()->noContent();
    }
}
