<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Profile Update
     * @OA\Put (
     *     path="/api/profile/update",
     *     tags={"Profile"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="country_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      property="languages",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="id",
     *                              type="number",
     *                          ),
     *                          @OA\Property(
     *                              property="language_id",
     *                              type="number",
     *                          ),
     *                      ),
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
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
    public function update(ProfileUpdateRequest $request)
    {
        $user = auth()->user();

        $user->country()->update([
            'country_id' => $request->get('country_id'),
        ]);

        // FIXME: duplicate code :c

        $languages = $request->get('languages');
        $languageIds = [];

        foreach ($languages as $language) {
            if (isset($language['id'])) {
                $languageIds[] = $language['id'];
            }
        }

        $userLanguages = $user->languages();
        $userLanguages->whereNotIn('id', $languageIds)->delete();

        $upsertLanguages = array_map(function ($language) use ($user) {
            if (!isset($language['id'])) {
                $language['id'] = null;
            }

            $language['manager_id'] = $user->id;
            return $language;
        }, $languages);

        $userLanguages->upsert($upsertLanguages, ['id', 'content']);
        $user->update(['name' => $request->get('name')]);

        return response()->noContent();
    }

    /**
     * Delete Profile Avatar
     * @OA\Delete (
     *     path="/api/profile/delete-avatar",
     *     tags={"Profile"},
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
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
    public function deleteAvatar()
    {
        auth()->user()->clearMediaCollection();
        return response()->noContent();
    }

    /**
     * Update Profile Avatar
     * @OA\Post (
     *     path="/api/profile/update-avatar",
     *     tags={"Profile"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="image",
     *                      type="file",
     *                  ),
     *             ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="no content",
     *      ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
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
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'image' => 'required|mimes:jpg,jpeg,png|max:10000',
        ]);

        $user = auth()->user();

        $user->clearMediaCollection();
        $user->addMediaFromRequest('image')->toMediaCollection();

        return response()->noContent();
    }
}
