<?php

namespace App\Http\Controllers;

use App\Models\SocialProvider;

use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class SocialAuthenticationController extends Controller
{
    /**
     * Redirect url for user social authentication
     * @OA\Get (
     *     path="/api/auth/{provider}/redirect",
     *     tags={"Social Authentication"},
     *     @OA\Parameter(
     *         in="path",
     *         name="provider",
     *         required=true,
     *         @OA\Schema(type="string", enum={"discord", "twitter"}, example={"discord", "twitter"}),
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
    public function redirectProvider(string $provider)
    {
        return [
            'redirect_url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl(),
        ];
    }

    /**
     * Callback for user social authentication
     * @OA\Get (
     *     path="/api/auth/{provider}/callback",
     *     tags={"Social Authentication"},
     *     @OA\Parameter(
     *         in="path",
     *         name="provider",
     *         required=true,
     *         @OA\Schema(type="string", enum={"discord", "twitter"}, example={"discord", "twitter"}),
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
    public function handleProviderCallback(string $provider)
    {
        $user = auth()->user();

        try {
            /**
             * @var AbstractProvider $socialite
             */
            $socialite = Socialite::driver($provider);
            $socialiteUser = $socialite->stateless()->user();

            if ($provider !== 'telegram') {
                $socialProviderExists = SocialProvider::where('provider_id', $socialiteUser->getId())
                    ->where('provider_name', $provider)
                    ->whereRaw('NOT (model_id = ? and model_type = ?)', [auth()->id(), 'App\Models\Manager'])
                    ->exists();

                if ($socialProviderExists) {
                    return response()->json([
                        'message' => 'Social provider already attached!',
                    ], 400);
                }
            }

            $user->socialProviders()->firstOrCreate([
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
     * Delete user social authentication
     * @OA\Delete (
     *     path="/api/auth/{provider}",
     *     tags={"Social Authentication"},
     *     @OA\Parameter(
     *         in="path",
     *         name="provider",
     *         required=true,
     *         @OA\Schema(type="string", enum={"discord", "twitter"}, example={"discord", "twitter"}),
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
    public function destroy(string $provider)
    {
        $user = auth()->user();
        $user->socialProviders()->where('provider_name', $provider)->delete();
        return response()->noContent();
    }
}
