<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Http\Requests\{
    EventListRequest,
    EventStoreRequest,
};

use App\Http\Resources\Event as EventResource;

class EventController extends Controller
{
    /**
     * Get List Events
     * @OA\Get (
     *     path="/api/events",
     *     tags={"Events"},
     *     @OA\Parameter(
     *         in="query",
     *         name="project_id",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
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
     *                  property="description",
     *                  type="string",
     *                  nullable=true,
     *                  example="example description"
     *              ),
     *              @OA\Property(
     *                  property="started_at",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="ended_at",
     *                  type="string",
     *              ),
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
    public function index(EventListRequest $request)
    {
        $projectId = $request->get('project_id');

        $events = Event::when($request->has('project_id'), function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })->get();

        return response()->json(EventResource::collection($events));
    }

    /**
     * Create event
     * @OA\Post (
     *     path="/api/events",
     *     tags={"Events"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="title",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="description",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="project_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="started_at",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="ended_at",
     *                      type="number",
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
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
    public function store(EventStoreRequest $request)
    {
        Event::create($request->validated());
        return response()->noContent();
    }
}
