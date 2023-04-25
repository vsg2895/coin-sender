<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Http\Requests\ContactFormCreateRequest;
use App\Notifications\ContactFormRequestNotification;

use Illuminate\Support\Facades\Notification;

class ContactFormController extends Controller
{
    /**
     * Contact Form
     * @OA\Post (
     *     path="/api/contact-form",
     *     tags={"Contact Form"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="full_name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="social_link",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="website_link",
     *                      type="string",
     *                  ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="no content",
     *     ),
     * )
     */
    public function store(ContactFormCreateRequest $request)
    {
        $contact = Contact::create($request->validated());
        Notification::route('mail', config('app.admin_email'))->notify(new ContactFormRequestNotification($contact));
        return response()->noContent();
    }
}
