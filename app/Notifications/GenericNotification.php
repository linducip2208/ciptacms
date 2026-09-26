<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable; use Illuminate\Notifications\Notification;
class GenericNotification extends Notification {
    use Queueable;
    public function __construct(public string $subject, public string $body, public array $data=[]) {}
    public function via($n): array { return ['database','mail']; }
    public function toMail($n){ return (new \Illuminate\Notifications\Messages\MailMessage)->subject($this->subject)->line($this->body); }
    public function toArray($n): array { return ['subject'=>$this->subject,'body'=>$this->body]+$this->data; }
}
