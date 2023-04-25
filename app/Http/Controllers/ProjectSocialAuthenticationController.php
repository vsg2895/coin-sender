<?php

namespace App\Http\Controllers;

use App\Models\{Project, SocialProvider};

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class ProjectSocialAuthenticationController extends Controller
{
    /**
     * Redirect url for project social authentication
     * @OA\Get (
     *     path="/api/projects/auth/{provider}/redirect",
     *     tags={"Project Social Authentication"},
     *     @OA\Parameter(
     *         in="query",
     *         name="project_id",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="path",
     *         name="provider",
     *         required=true,
     *         @OA\Schema(type="string", enum={"twitter", "discord_bot"}, example={"twitter", "discord_bot"}),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="redirect_url", type="string"),
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
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function redirectProvider(Request $request, string $provider)
    {
        $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        return [
            'redirect_url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl(),
        ];
    }

    /**
     * Callback for project social authentication
     * @OA\Get (
     *     path="/api/projects/auth/{provider}/callback",
     *     tags={"Project Social Authentication"},
     *     @OA\Parameter(
     *         in="query",
     *         name="state",
     *         required=false,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(
     *         in="path",
     *         name="provider",
     *         required=true,
     *         @OA\Schema(type="string", enum={"twitter", "discord_bot"}, example={"twitter", "discord_bot"}),
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
    public function handleProviderCallback(Request $request, string $provider)
    {
        $project = Project::findOrFail($request->get('state'));

        try {
            /**
             * @var AbstractProvider $socialite
             */
            $socialite = Socialite::driver($provider);
            $socialiteUser = $socialite->stateless()->user();

            $socialProviderExists = SocialProvider::where('provider_id', $socialiteUser->getId())
                ->where('provider_name', $provider)
                ->whereRaw('NOT (model_id = ? and model_type = ?)', [$project->id, 'App\Models\Project'])
                ->exists();

            if ($socialProviderExists) {
                return response()->json([
                    'message' => 'Social provider already attached!',
                ], 400);
            }

            $project->socialProviders()->firstOrCreate([
                'name' => $socialiteUser->getNickname() ?: $socialiteUser->getName(),
                'provider_id' => $socialiteUser->getId(),
                'provider_name' => $provider,
            ]);

            return response()->noContent();
        } catch (Exception $error) {
            Log::error($error);
            return response()->json([
                'message' => 'Oops, account connection failed!',
            ], 400);
        }
    }

    /**
     * Delete project social authentication
     * @OA\Delete (
     *     path="/api/projects/{project}/auth/{provider}",
     *     tags={"Project Social Authentication"},
     *     @OA\Parameter(
     *         in="path",
     *         name="project",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="path",
     *         name="provider",
     *         required=true,
     *         @OA\Schema(type="string", enum={"twitter", "telegram_bot", "discord_bot"}, example={"twitter", "discord_bot"}),
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
    public function destroy(Project $project, string $provider)
    {
        $project->socialProviders()->where('provider_name', $provider)->delete();
        return response()->noContent();
    }
}
