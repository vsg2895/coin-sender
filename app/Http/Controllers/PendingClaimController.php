<?php

namespace App\Http\Controllers;

use App\Models\AmbassadorWalletWithdrawalRequest;
use App\Http\Resources\PendingClaim as PendingClaimResource;

class PendingClaimController extends Controller
{
    /**
     * Get Pending Claims
     * @OA\Get (
     *     path="/api/pending-claims",
     *     tags={"Pending Claims"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="number", example="1"),
     *              @OA\Property(property="value", type="string", example="0.0001"),
     *              @OA\Property(property="symbol", type="string", example="BTC"),
     *              @OA\Property(property="talent", type="string", example="example talent"),
     *              @OA\Property(property="created_at", type="string"),
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
        $pendingClaims = AmbassadorWalletWithdrawalRequest::whereStatus(AmbassadorWalletWithdrawalRequest::STATUS_PENDING)
            ->with(['wallet', 'wallet.coinType', 'ambassador'])
            ->orderBy('created_at')
            ->limit(6)
            ->get();

        return response()->json(PendingClaimResource::collection($pendingClaims));
    }
}
