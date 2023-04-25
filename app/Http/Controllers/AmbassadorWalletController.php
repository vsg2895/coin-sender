<?php

namespace App\Http\Controllers;

use App\Models\Ambassador;

use App\Http\Resources\{
    AmbassadorWallet as AmbassadorWalletResource,
    AmbassadorWalletHistoryCollection,
    AmbassadorWalletWithdrawalRequestCollection,
};

class AmbassadorWalletController extends Controller
{
    /**
     * Get List Ambassador Wallets
     * @OA\Get (
     *     path="/api/ambassadors/wallets/{ambassador}",
     *     tags={"Ambassador Wallets"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassador",
     *         required=true,
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
     *                  property="balance",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="address",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="coin_type",
     *                  nullable=true,
     *                  @OA\Property(property="id", type="number", example="1"),
     *                  @OA\Property(property="name", type="string", example="example name"),
     *                  @OA\Property(property="type_of_chain", type="string", example="example name"),
     *              ),
     *              @OA\Property(
     *                  property="is_primary",
     *                  type="boolean",
     *                  example="true",
     *              ),
     *              @OA\Property(
     *                  property="balance_in_usd",
     *                  type="number",
     *                  example="1",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
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
    public function index(Ambassador $ambassador)
    {
        $ambassadorWallets = $ambassador->wallets;
        return response()->json(AmbassadorWalletResource::collection($ambassadorWallets));
    }

    /**
     * Get List Ambassador Wallet History
     * @OA\Get (
     *     path="/api/ambassadors/wallets/{ambassador}/history",
     *     tags={"Ambassador Wallets"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassador",
     *         required=true,
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
     *                  property="date",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="value",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="points",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="value_in_usd",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="ambassador_wallet",
     *                  @OA\Property(
     *                      property="id",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="balance",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="address",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="coin_type",
     *                      nullable=true,
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="name", type="string", example="example name"),
     *                      @OA\Property(property="type_of_chain", type="string", example="example name"),
     *                  ),
     *                  @OA\Property(
     *                      property="is_primary",
     *                      type="boolean",
     *                      example="true",
     *                  ),
     *                  @OA\Property(
     *                      property="balance_in_usd",
     *                      type="number",
     *                      example="1",
     *                  ),
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
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
    public function history(Ambassador $ambassador)
    {
        $ambassadorHistoryWallets = $ambassador->historyWallets()->with(['wallet', 'task', 'task.project'])->whereHas('task.project')->paginate(10);
        return response()->json(new AmbassadorWalletHistoryCollection($ambassadorHistoryWallets));
    }

    /**
     * Get List Ambassador Withdrawal Requests
     * @OA\Get (
     *     path="/api/ambassadors/wallets/{ambassador}/withdrawal-requests",
     *     tags={"Ambassador Wallets"},
     *     @OA\Parameter(
     *         in="path",
     *         name="ambassador",
     *         required=true,
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
     *                  property="status",
     *                  type="string",
     *                  enum={"pending", "canceled", "executed", "accepted"},
     *                  example="pending",
     *              ),
     *              @OA\Property(
     *                  property="tx_hash",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="ambassador_wallet",
     *                  @OA\Property(
     *                      property="id",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="balance",
     *                      type="number",
     *                      example="1",
     *                  ),
     *                  @OA\Property(
     *                      property="address",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="coin_type",
     *                      nullable=true,
     *                      @OA\Property(property="id", type="number", example="1"),
     *                      @OA\Property(property="name", type="string", example="example name"),
     *                      @OA\Property(property="type_of_chain", type="string", example="example name"),
     *                  ),
     *                  @OA\Property(
     *                      property="is_primary",
     *                      type="boolean",
     *                      example="true",
     *                  ),
     *                  @OA\Property(
     *                      property="balance_in_usd",
     *                      type="number",
     *                      example="1",
     *                  ),
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
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
    public function withdrawalRequests(Ambassador $ambassador)
    {
        $ambassadorWithdrawalRequests = $ambassador->withdrawalRequests()->with(['wallet'])->paginate(10);
        return response()->json(new AmbassadorWalletWithdrawalRequestCollection($ambassadorWithdrawalRequests));
    }
}
