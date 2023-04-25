<?php

namespace App\Http\Controllers;

use App\Models\AmbassadorWalletWithdrawalRequest;
use App\Http\Resources\AmbassadorWalletWithdrawalRequestCollection;

use SWeb3\SWeb3;
use SWeb3\SWeb3_Contract;
use Brick\Math\BigDecimal;

class AmbassadorWalletWithdrawalRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['teams.permission'])->except(['index']);
        $this->authorizeResource(AmbassadorWalletWithdrawalRequest::class, 'walletWithdrawalRequest');
    }

    /**
     * Get List Withdrawal Requests
     * @OA\Get (
     *     path="/api/ambassadors/wallets/withdrawal-requests",
     *     tags={"Ambassador Wallets"},
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
    public function index()
    {
        $withdrawalRequests = AmbassadorWalletWithdrawalRequest::with(['wallet', 'ambassador'])
            ->where('status', AmbassadorWalletWithdrawalRequest::STATUS_PENDING)
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json(new AmbassadorWalletWithdrawalRequestCollection($withdrawalRequests));
    }

    /**
     * Accept ambassador wallet withdrawal request
     * @OA\Post (
     *     path="/api/withdrawal-requests/accept/{walletWithdrawalRequest}",
     *     tags={"Ambassador Wallets"},
     *     @OA\Parameter(
     *         in="path",
     *         name="walletWithdrawalRequest",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found",
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Unauthorized"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="This action is unauthorized."),
     *          ),
     *      ),
     *      security={{ "apiAuth": {} }},
     * )
     */
    public function accept(AmbassadorWalletWithdrawalRequest $walletWithdrawalRequest)
    {
        $walletWithdrawalRequest->load(['wallet']);

        $web3 = new SWeb3(config('web3.host'));
        $address = config('web3.address');

        $web3->chainId = 97;
        $web3->setPersonalData($address, config('web3.private_key'));

        $errorReporting = error_reporting();

        error_reporting(0);

        $contract = new SWeb3_Contract($web3, config('web3.contract_address'), '[{"inputs":[{"internalType":"contract ERC20","name":"token","type":"address"},{"internalType":"address","name":"recipient","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"tokenSender","outputs":[],"stateMutability":"nonpayable","type":"function"}]');
        $transaction = $contract->send('tokenSender', [
            'token' => config('web3.contract_token'),
            'recipient' => $walletWithdrawalRequest->wallet->address,
            'amount' => $web3->utils->toWei((string) $walletWithdrawalRequest->value, 'ether'),
        ], [
            'nonce' => $web3->getNonce($address),
            'from' => $address,
        ]);

        error_reporting($errorReporting);

        $walletWithdrawalRequest->update([
            'status' => AmbassadorWalletWithdrawalRequest::STATUS_ACCEPTED,
            'tx_hash' => $transaction->result,
        ]);

        return response()->noContent();
    }

    /**
     * Cancel ambassador wallet withdrawal request
     * @OA\Post (
     *     path="/api/withdrawal-requests/cancel/{walletWithdrawalRequest}",
     *     tags={"Ambassador Wallets"},
     *     @OA\Parameter(
     *         in="path",
     *         name="walletWithdrawalRequest",
     *         required=true,
     *         @OA\Schema(type="number"),
     *     ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found",
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Unauthorized"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="This action is unauthorized."),
     *          ),
     *      ),
     *      security={{ "apiAuth": {} }},
     * )
     */
    public function cancel(AmbassadorWalletWithdrawalRequest $walletWithdrawalRequest)
    {
        $walletWithdrawalRequest->load(['wallet']);

        $walletWithdrawalRequest->wallet->balance = (string) BigDecimal::of($walletWithdrawalRequest->wallet->balance)->minus($walletWithdrawalRequest->value);
        $walletWithdrawalRequest->wallet->save();

        $walletWithdrawalRequest->update(['status' => AmbassadorWalletWithdrawalRequest::STATUS_CANCELED]);
        return response()->noContent();
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'index' => 'viewAny',
            'accept' => 'approve',
            'cancel' => 'decline',
        ];
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * @return array
     */
    protected function resourceMethodsWithoutModels()
    {
        return ['index'];
    }
}
