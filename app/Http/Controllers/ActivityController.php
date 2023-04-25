<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Http\Requests\ActivityCreateRequest;
use App\Http\Requests\ActivityUpdateRequest;
use App\Http\Resources\Activity as ActivityResource;

class ActivityController extends Controller
{
    /**
     * Get List Activities
     * @OA\Get (
     *     path="/api/activities",
     *     tags={"Activities"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="example name"
     *              ),
     *              @OA\Property(
     *                  property="links",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number"),
     *                      @OA\Property(
     *                          property="link",
     *                          type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="number"),
     *                              @OA\Property(property="name", type="string"),
     *                              @OA\Property(property="icon", type="string", nullable="true"),
     *                          ),
     *                      ),
     *                      @OA\Property(property="link_id", type="number"),
     *                      @OA\Property(property="activity_id", type="number"),
     *                  )
     *              ),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function index()
    {
        $activites = Activity::with(['links', 'links.link', 'links.link.media'])->get();
        return response()->json(ActivityResource::collection($activites));
    }

    /**
     * Get activity
     * @OA\Get (
     *     path="/api/activities/{activity}",
     *     @OA\Parameter(
     *         in="path",
     *         name="activity",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Activities"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="example name"
     *              ),
     *              @OA\Property(
     *                  property="links",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="number"),
     *                      @OA\Property(
     *                          property="link",
     *                          type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="number"),
     *                              @OA\Property(property="name", type="string"),
     *                              @OA\Property(property="icon", type="string", nullable="true"),
     *                          ),
     *                      ),
     *                      @OA\Property(property="link_id", type="number"),
     *                      @OA\Property(property="activity_id", type="number"),
     *                  )
     *              ),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function show(Activity $activity)
    {
        $activity->load(['links', 'links.link', 'links.link.media']);
        return response()->json(new ActivityResource($activity));
    }

    /**
     * Create activity
     * @OA\Post (
     *     path="/api/activities",
     *     tags={"Activities"},
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
     *          ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
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
    public function store(ActivityCreateRequest $request)
    {
        $activity = Activity::create($request->validated());
        return response()->json(new ActivityResource($activity));
    }

    /**
     * Update activity
     * @OA\Put (
     *     path="/api/activities/{activity}",
     *     @OA\Parameter(
     *         in="path",
     *         name="activity",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Activities"},
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
     *          ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="errors",
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
    public function update(Activity $activity, ActivityUpdateRequest $request)
    {
        $activity->update($request->validated());
        return response()->json(new ActivityResource($activity));
    }
}
