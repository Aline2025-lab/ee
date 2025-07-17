<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;



class EnvoiIdentifiants extends Mailable
{
    use Queueable, SerializesModels;

    public $utilisateur;
    public $motDePasse;
    public $matricule;

    /**
     * Create a new message instance.
     */
    public function __construct($utilisateur, $motDePasse, $matricule)
    {
        $this->utilisateur = $utilisateur;
        $this->motDePasse = $motDePasse;
        $this->matricule = $matricule;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Envoi Identifiants',
        );
    }



    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
      $confirmationUrl = route('verification.email', ['id' => $this->utilisateur->id, 'code' => $this->utilisateur->verification_code]);
        $html = "
            <html>
                <body style='font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;'>
                    <div style='max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);'>
                        <h2 style='color: #333;'>Bonjour {$this->utilisateur->prenom},</h2>
                        <p>Bienvenue sur la galaxie <strong>BE I.T Classroom</strong> 🎓.</p>
                        <p>Voici vos identifiants de connexion :</p>
                        <ul style='line-height: 1.8;'>
                            <li><strong>Login :</strong> {$this->utilisateur->login}</li>
                            <li><strong>Mot de passe :</strong> {$this->motDePasse}</li>
                            <li><strong>Matricule :</strong> {$this->matricule}</li>
                        </ul>
                        <p><strong>Important :</strong> Vous devrez changer votre mot de passe lors de votre première connexion.</p>
                        <hr style='margin: 20px 0;'>
                        <p>Nous sommes ravis de vous accueillir sur notre plateforme.</p>
                        <p style='margin-bottom: 30px;'>Cliquez simplement sur le bouton ci-dessous pour confirmer votre adresse email :</p>
                        <div style='text-align: center; margin-bottom: 30px;'>
                            <a href='{$confirmationUrl}' style='display: inline-block; padding: 12px 24px; background-color: #0a7cff; color: #fff; text-decoration: none; border-radius: 5px;'>
                                Confirmer mon adresse email
                            </a>
                        </div>
                        <p>À très bientôt sur <strong>BE I.T Classroom</strong> !</p>
                        <p style='font-size: 12px; color: #888;'>Si vous n’avez pas demandé cette inscription, vous pouvez ignorer ce message.</p>
                    </div>
                </body>
            </html>
        ";
        return $this->subject('Bienvenue sur BE I.T Classroom')->html($html);
    
    }
}
