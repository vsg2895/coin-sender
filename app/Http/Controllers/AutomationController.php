<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Contracts\TelegramServiceContract;
use App\Http\Requests\AutomationConnectTelegramRequest;

class AutomationController extends Controller
{
    /**
     * Connect project telegram
     * @OA\Post (
     *     path="/api/automations/connect-telegram",
     *     tags={"Automations"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="chat_id",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="project_id",
     *                      type="number",
     *                  ),
     *             )
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
     *                  @OA\Examples(value="Telegram chat already connected to this project!"),
     *                  @OA\Examples(value="Bot must first be invited to the chat!"),
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
    public function connectTelegram(AutomationConnectTelegramRequest $request, TelegramServiceContract $telegramService)
    {
        $project = Project::findOrFail($request->get('project_id'));
        if ($project->socialProviders()->where('provider_name', 'telegram_bot')->exists()) {
            return response()->json([
                'message' => 'Telegram bot already connected to this project!',
            ], 400);
        }

        $chatId = $request->get('chat_id');
        $telegramChat = $telegramService->getChat($chatId);

        if (empty($telegramChat) || ($telegramChat['id'] !== $chatId
            && $telegramChat['username'] !== substr($chatId, 1))) {
            return response()->json([
                'message' => 'Bot must first be invited to the chat!',
            ], 400);
        }

        $project->socialProviders()->create([
            'name' => $telegramChat['title'],
            'provider_id' => $telegramChat['id'],
            'provider_name' => 'telegram_bot',
        ]);

        return response()->noContent();
    }
}
