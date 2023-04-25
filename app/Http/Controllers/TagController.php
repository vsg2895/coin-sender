<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Http\Resources\Tag as TagResource;

class TagController extends Controller
{
    /**
     * Get List Tags
     * @OA\Get (
     *     path="/api/tags",
     *     tags={"Tags"},
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
     *         ),
     *     ),
     * )
     */
    public function index()
    {
        $tags = Tag::all();
        return response()->json(TagResource::collection($tags));
    }
}
