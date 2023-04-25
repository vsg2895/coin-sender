<?php

namespace App\Http\Controllers;

use App\Models\Blockchain;
use App\Http\Resources\Blockchain as BlockchainResource;

class BlockchainController extends Controller
{
    /**
     * Get List Blockchains
     * @OA\Get (
     *     path="/api/blockchains",
     *     tags={"Blockchains"},
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
        $blockchains = Blockchain::all();
        return response()->json(BlockchainResource::collection($blockchains));
    }
}
