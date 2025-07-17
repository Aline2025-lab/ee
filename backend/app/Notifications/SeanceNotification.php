<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Seance;

class SeanceNotification extends Notification
{
    use Queueable;

    public $seance;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->seance = $seance;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database','mail'];
    }

    /**
     * Définit la représentation de la notification pour le canal 'database'.
     * C'est ce qui sera stocké dans la base de données.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'seance_id' => $this->seance->id,
            'seance_titre' => $this->seance->titre,
            'message' => $this->message,
            'sender_id' => auth()->user()->id, // On sait qui a envoyé la notif
        ];
    }


    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Notification concernant votre séance')
                    ->greeting('Bonjour ' . $notifiable->prenom . ',')
                    ->line('Vous avez reçu une nouvelle notification concernant la séance : ' . $this->seance->titre)
                    ->line('Message : ' . $this->message)
                    ->action('Voir la séance', url('/seances/{seance}' . $this->seance->id)) // Adaptez l'URL
                    ->line('Merci d\'utiliser notre application !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
