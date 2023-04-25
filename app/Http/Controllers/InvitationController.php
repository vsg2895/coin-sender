<?php

namespace App\Http\Controllers;

use App\Events\InviteUpdated;
use App\Models\{User, Invitation, ProjectMember};

class InvitationController extends Controller
{
    /**
     * Verify Invitation
     * @OA\Post (
     *     path="/api/invitations/verify/{token}",
     *     @OA\Parameter(
     *         in="path",
     *         name="token",
     *         required=true,
     *         @OA\Schema(type="string"),
     *     ),
     *     tags={"Invitations"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="example@example.com",
     *              ),
     *              @OA\Property(
     *                  property="request_registration",
     *                  type="boolean",
     *                  example="true",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
     *     ),
     * )
     */
    public function verify(Invitation $invitation)
    {
        $user = $invitation->userable;
        return response()->json(['email' => $user->email, 'request_registration' => $user->type === User::TYPE_CREATED]);
    }

    /**
     * Accept Invitation
     * @OA\Get (
     *     path="/api/invitation/accept/{token}",
     *     @OA\Parameter(
     *         in="path",
     *         name="token",
     *         required=true,
     *         @OA\Schema(type="string"),
     *     ),
     *     tags={"Invitations"},
     *     @OA\Response(
     *         response=204,
     *         description="no content",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="not found",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="invalid",
     *     ),
     * )
     */
    public function accept(Invitation $invitation)
    {
        $user = $invitation->userable;
        if ($user->type === User::TYPE_CREATED) {
            return response()->json([
                'message' => 'Manager need to be created first!',
            ], 400);
        }

        if (in_array($invitation->status, [
            Invitation::STATUS_REVOKED,
            Invitation::STATUS_ACCEPTED,
            Invitation::STATUS_DECLINED,
        ], true)) {
            return response()->json([
                'message' => 'Incorrect invitation!',
            ], 400);
        }

        $projectId = $invitation->project_id;
        setPermissionsTeamId($projectId ?? 0);

        if ($projectId) {
            $user->projectMembers()->firstOrCreate([
                'project_id' => $projectId
            ], [
                'status' => ProjectMember::STATUS_ACCEPTED,
                'project_id' => $projectId,
            ]);
        } else {
            $user->projectMembers()->delete();
        }

        $user->assignRole($invitation->role_name);
        $invitation->update(['status' => Invitation::STATUS_ACCEPTED]);

        // FIXME: ambassador_pusher?
        broadcast(new InviteUpdated($invitation->userable_id, $invitation->token, Invitation::STATUS_ACCEPTED))
            ->via('ambassador_pusher');

        return response()->noContent();
    }
}
