<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Http\Resources\Skill as SkillResource;

class SkillController extends Controller
{
    /**
     * Get List Skills
     * @OA\Get (
     *     path="/api/skills",
     *     tags={"Skills"},
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
     *                  property="activity_id",
     *                  type="number",
     *                  example="1"
     *              ),
     *         ),
     *     ),
     * )
     */
    public function index()
    {
        $skills = Skill::all();
        return response()->json(SkillResource::collection($skills));
    }
}
