<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Http\Resources\Country as CountryResource;

class CountryController extends Controller
{
    /**
     * Get List Countries
     * @OA\Get (
     *     path="/api/countries",
     *     tags={"Countries"},
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
        $countries = Country::all();
        return response()->json(CountryResource::collection($countries));
    }
}
