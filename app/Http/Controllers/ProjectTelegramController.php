<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Contracts\TelegramServiceContract;
use App\Http\Requests\ProjectTelegramUpdateRequest;

class ProjectTelegramController extends Controller
{
    /**
     * Get project telegram group
     * @OA\Get (
     *     path="/api/projects/{project}/telegram/group",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects Telegram Group"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="string"),
     *              @OA\Property(property="title", type="string"),
     *              @OA\Property(property="username", type="string"),
     *              @OA\Property(
     *                  property="newTask",
     *                  nullable=true,
     *                  @OA\Property(property="active", type="boolean"),
     *              ),
     *              @OA\Property(
     *                  property="newProject",
     *                  nullable=true,
     *                  @OA\Property(property="active", type="boolean"),
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Telegram bot is not connected to this project!",
     *             ),
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
    public function index(Project $project, TelegramServiceContract $telegramService)
    {
        // FIXME: Duplicate check telegram provider
        $socialProvider = $project->socialProviders()
            ->where('provider_name', 'telegram_bot')
            ->first();

        if (!$socialProvider) {
            return response()->json([
                'message' => 'Telegram bot is not connected to this project!',
            ], 400);
        }

        $chat = $telegramService->getChat($socialProvider->provider_id);
        return response()->json($chat + ($socialProvider->notifications ?? []));
    }

    /**
     * Update project telegram group notifications
     * @OA\Put (
     *     path="/api/projects/{project}/telegram/group",
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Projects Telegram Group"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="newTask",
     *                      @OA\Property(property="active", type="boolean"),
     *                  ),
     *                  @OA\Property(
     *                      property="newProject",
     *                      @OA\Property(property="active", type="boolean"),
     *                  ),
     *             ),
     *         ),
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
     *             @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Telegram bot is not connected to this project!",
     *             ),
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
    public function update(Project $project, ProjectTelegramUpdateRequest $request)
    {
        // FIXME: Duplicate check telegram provider
        $socialProvider = $project->socialProviders()
            ->where('provider_name', 'telegram_bot')
            ->first();

        if (!$socialProvider) {
            return response()->json([
                'message' => 'Telegram bot is not connected to this project!',
            ], 400);
        }

        $socialProvider->update(['notifications' => $request->validated()]);
        return response()->noContent();
    }
}
