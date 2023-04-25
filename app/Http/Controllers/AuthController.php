<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\User as UserResource;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register user
     * @OA\Post (
     *     path="/api/auth/registration",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="country_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="languages",
     *                      type="array",
     *                      @OA\Items(type="number"),
     *                  ),
     *                  @OA\Property(
     *                      property="social_links",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="number"),
     *                          @OA\Property(property="content", type="string"),
     *                      ),
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string"),
     *              @OA\Property(property="token_type", type="string", example="token_type"),
     *              @OA\Property(property="expires_in", type="number", example="60"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
     *      ),
     * )
     */
    public function register(RegisterRequest $request)
    {
        $user = User::firstOrNew(['email' => $request->get('email')]);
        $user->type = User::TYPE_REGISTERED;
        $user->name = $request->get('name');
        $user->password = Hash::make($request->get('password'));
        $user->save();

        $user->country()->create([
            'country_id' => $request->get('country_id'),
        ]);

        $languageRecords = array_map(function ($languageId) {
            return [
                'language_id' => $languageId,
            ];
        }, $request->get('languages'));

        $socialLinkRecords = array_map(function ($socialLink) {
            return [
                'content' => $socialLink['content'],
                'social_link_id' => $socialLink['id'],
            ];
        }, $request->get('social_links'));

        $user->languages()->createMany($languageRecords);
        $user->socialLinks()->createMany($socialLinkRecords);

        return response()->json($this->respondWithToken(auth()->login($user)));
    }

    /**
     * Get a JWT via given credentials.
     * @OA\Post (
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="password",
     *                      type="string",
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string"),
     *              @OA\Property(property="token_type", type="string", example="token_type"),
     *              @OA\Property(property="expires_in", type="number", example="60"),
     *          ),
     *      ),
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);
        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Validate name
     * @OA\Post (
     *     path="/api/auth/validate-name",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     * )
     */
    public function validateName(Request $request)
    {
        $request->validate([
            'name' => 'required|regex:/^[a-zA-Z0-9\s]+$/|min:3|max:29|unique:managers,name',
        ]);

        return response()->noContent();
    }

    /**
     * Get the authenticated User.
     * @OA\Get (
     *     path="/api/auth/me",
     *     tags={"Auth"},
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="example name",
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="test@test.com",
     *              ),
     *              @OA\Property(
     *                  property="country",
     *                  type="string",
     *                  nullable="true",
     *                  example="Ukraine",
     *              ),
     *              @OA\Property(
     *                  property="languages",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="language",
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
     *              @OA\Property(
     *                  property="social_links",
     *                  type="array",
     *                  deprecated=true,
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="content",
     *                          type="string",
     *                      ),
     *                      @OA\Property(
     *                          property="social_link",
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
     *              @OA\Property(
     *                  property="social_providers",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                          example="1",
     *                      ),
     *                      @OA\Property(
     *                          property="name",
     *                          type="string",
     *                          example="test#1234",
     *                      ),
     *                      @OA\Property(
     *                          property="provider_id",
     *                          type="number",
     *                          example="1",
     *                      ),
     *                      @OA\Property(
     *                          property="provider_name",
     *                          type="string",
     *                          enum={"twitter", "telegram", "discord"},
     *                          example="discord",
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="roles",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="id",
     *                          type="number",
     *                      ),
     *                      @OA\Property(
     *                          property="name",
     *                          type="string",
     *                      ),
     *                      @OA\Property(
     *                          property="project_id",
     *                          type="number",
     *                      ),
     *                  ),
     *              ),
     *          ),
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth()->user();
        $user->load([
            'media',
            'allRoles',
            'country',
            'country.country',
            'languages',
            'languages.language',
            'socialProviders',
            'socialLinks',
            'socialLinks.link',
            'socialLinks.link.media',
        ]);

        $user->loadCount([
            'tasks',
            'checkedTasks',
        ]);

        return response()->json(new UserResource($user));
    }

    /**
     * Log the user out (Invalidate the token).
     * @OA\Post (
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Successfully logged out"),
     *          ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     * @OA\Post (
     *     path="/api/auth/refresh",
     *     tags={"Auth"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="token_type"),
     *             @OA\Property(property="expires_in", type="number", example="60"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL()
        ]);
    }
}
