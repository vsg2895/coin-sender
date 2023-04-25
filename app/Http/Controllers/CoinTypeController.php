<?php

namespace App\Http\Controllers;

use App\Models\CoinType;

use App\Http\Requests\{
    CoinTypeCreateRequest,
    CoinTypeUpdateRequest,
};

use App\Http\Resources\CoinType as CoinTypeResource;

class CoinTypeController extends Controller
{
    /**
     * Get List Coin Types
     * @OA\Get (
     *     path="/api/coin-types",
     *     tags={"Coin Types"},
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
     *                  property="rpc_url",
     *                  type="string",
     *                  example="example rpc url"
     *              ),
     *              @OA\Property(
     *                  property="chain_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="type_of_chain",
     *                  type="string",
     *                  example="example type of chain",
     *              ),
     *              @OA\Property(
     *                  property="block_explorer_url",
     *                  type="string",
     *                  example="example block explorer url",
     *              ),
     *         ),
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
    public function index()
    {
        $coinTypes = CoinType::all();
        return response()->json(CoinTypeResource::collection($coinTypes));
    }

    /**
     * Create Coin Type
     * @OA\Post (
     *     path="/api/coin-types",
     *     @OA\Parameter(
     *         in="path",
     *         name="coinType",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Coin Types"},
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
     *                  property="rpc_url",
     *                  type="string",
     *                  example="example rpc url"
     *              ),
     *              @OA\Property(
     *                  property="chain_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="type_of_chain",
     *                  type="string",
     *                  example="example type of chain",
     *              ),
     *              @OA\Property(
     *                  property="block_explorer_url",
     *                  type="string",
     *                  example="example block explorer url",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
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
    public function store(CoinTypeCreateRequest $request)
    {
        $coinType = CoinType::create($request->validated());
        return response()->json(new CoinTypeResource($coinType));
    }

    /**
     * Update Coin Type
     * @OA\Put (
     *     path="/api/coin-types/{coinType}",
     *     @OA\Parameter(
     *         in="path",
     *         name="coinType",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Coin Types"},
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
     *                  property="rpc_url",
     *                  type="string",
     *                  example="example rpc url"
     *              ),
     *              @OA\Property(
     *                  property="chain_id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="type_of_chain",
     *                  type="string",
     *                  example="example type of chain",
     *              ),
     *              @OA\Property(
     *                  property="block_explorer_url",
     *                  type="string",
     *                  example="example block explorer url",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
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
    public function update(CoinType $coinType, CoinTypeUpdateRequest $request)
    {
        $coinType->update($request->validated());
        return response()->json(new CoinTypeResource($coinType));
    }

    /**
     * Delete Coin Type
     * @OA\Delete (
     *     path="/api/coin-types/{coinType}",
     *     @OA\Parameter(
     *         in="path",
     *         name="coinType",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *     tags={"Coin Types"},
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
    public function destroy(CoinType $coinType)
    {
        $coinType->delete();
        return response()->noContent();
    }
}
