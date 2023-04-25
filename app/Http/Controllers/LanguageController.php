<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Http\Resources\Language as LanguageResource;

class LanguageController extends Controller
{
    /**
     * Get List Languages
     * @OA\Get (
     *     path="/api/languages",
     *     tags={"Languages"},
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
        $languages = Language::orderBy('name', 'ASC')->get();
        return response()->json(LanguageResource::collection($languages));
    }
}
