<?php

namespace App\Http\Controllers;

use App\Contracts\TwitterServiceContract;

class TwitterController extends Controller
{
    private TwitterServiceContract $service;

    public function __construct(TwitterServiceContract $service)
    {
        $this->service = $service;
    }

    /**
     * Get twitter user
     * @OA\Get (
     *     path="/api/twitter/user/{name}",
     *     tags={"Twitter"},
     *     @OA\Parameter(
     *         in="path",
     *         name="name",
     *         required=true,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="string"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="username", type="string"),
     *              @OA\Property(property="created_at", type="string"),
     *              @OA\Property(property="profile_image_url", type="string"),
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
    public function user(string $name)
    {
        return response()->json($this->service->user($name));
    }

    /**
     * Get twitter tweet
     * @OA\Get (
     *     path="/api/twitter/tweet/{id}",
     *     tags={"Twitter"},
     *     @OA\Parameter(
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="string"),
     *              @OA\Property(property="text", type="string"),
     *              @OA\Property(property="lang", type="string"),
     *              @OA\Property(
     *                  property="user",
     *                  @OA\Property(property="id", type="string"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="username", type="string"),
     *                  @OA\Property(property="created_at", type="string"),
     *                  @OA\Property(property="profile_image_url", type="string"),
     *              ),
     *              @OA\Property(property="author_id", type="string"),
     *              @OA\Property(property="created_at", type="string"),
     *              @OA\Property(property="conversation_id", type="string"),
     *              @OA\Property(
     *                  property="edit_history_tweet_ids",
     *                  type="array",
     *                  @OA\Items(type="string"),
     *              ),
     *          ),
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
    public function tweet(string $id)
    {
        return response()->json($this->service->tweet($id));
    }

    /**
     * Get twitter space
     * @OA\Get (
     *     path="/api/twitter/space/{name}",
     *     tags={"Twitter"},
     *     @OA\Parameter(
     *         in="path",
     *         name="name",
     *         required=true,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="string"),
     *              @OA\Property(property="lang", type="string"),
     *              @OA\Property(property="state", type="string"),
     *              @OA\Property(property="title", type="string"),
     *              @OA\Property(
     *                  property="users",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="string"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="username", type="string"),
     *                  ),
     *              ),
     *              @OA\Property(
     *                  property="host_ids",
     *                  type="array",
     *                  @OA\Items(type="string"),
     *              ),
     *              @OA\Property(property="created_at", type="string"),
     *              @OA\Property(property="updated_at", type="string"),
     *              @OA\Property(property="creator_id", type="string"),
     *              @OA\Property(property="participant_count", type="string"),
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
    public function space(string $name)
    {
        return response()->json($this->service->space($name));
    }
}
