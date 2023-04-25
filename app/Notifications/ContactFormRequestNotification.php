<?php

namespace App\Notifications;

use App\Models\Contact;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactFormRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->replyTo(config('app.admin_email'), 'Admin')
            ->subject('Contact Form Request')
            ->greeting('Hello! I want to submit my project to you for consideration...')
            ->lines([
                'Company / Project Name: ' . $this->contact->name,
                'Full Name: ' . $this->contact->full_name,
                'Email: ' . $this->contact->email,
                'Website Link: ' . $this->contact->website_link,
                'Social Link: ' . $this->contact->social_link,
            ]);
    }
}
