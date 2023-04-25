<?php

namespace App\Http\Controllers;

use App\Models\SocialLink;
use App\Http\Requests\SocialLinkListRequest;
use App\Http\Resources\SocialLink as SocialLinkResource;

class SocialLinkController extends Controller
{
    /**
     * Get List Social Links
     * @OA\Get (
     *     path="/api/social-links",
     *     tags={"Social Links"},
     *     @OA\Parameter(
     *         in="query",
     *         name="assigned_to",
     *         required=true,
     *         @OA\Schema(type="string", enum={"project", "ambassador"}, example="ambassador"),
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
     *              @OA\Property(property="icon", type="string", nullable="true"),
     *         ),
     *     ),
     * )
     */
    public function index(SocialLinkListRequest $request)
    {
        $assignedTo = $request->get('assigned_to') ?? SocialLink::ASSIGNED_TO_AMBASSADOR;
        $socialLinks = SocialLink::where('assigned_to', $assignedTo)->with(['media'])->orderBy('order')->get();
        return response()->json(SocialLinkResource::collection($socialLinks));
    }
}
