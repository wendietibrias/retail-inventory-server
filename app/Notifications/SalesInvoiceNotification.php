<?php

namespace App\Notifications;

use App\Channels\CustomDatabaseChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $changeTitle;
    public $changeDescription;
    public $status;
    public $priority;
    public $senderId;
    public $salesInvoiceId;
    public $salesInvoiceCode;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($message, $actionType,$changeTitle,$changeDescription,$status,$priority,$senderId,$salesInvoiceCode,$salesInvoiceId)
    {
        $this->changeTitle = $changeTitle;
        $this->changeDescription = $changeDescription;
        $this->status = $status;
        $this->priority = $priority;
        $this->senderId = $senderId;
        $this->salesInvoiceCode = $salesInvoiceCode;
        $this->salesInvoiceId = $salesInvoiceId;
        $this->actionType = $actionType;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [CustomDatabaseChannel::class,'broadcast'];
    }

    public function toDatabase(){
        $user = User::with(['roles'])->find($this->senderId)->first();
        return [
            "title"=> $this->changeTitle,
            "message"=> $this->message,
            'sales_invoice_id'=> $this->salesInvoiceId,
            'priority'=> $this->priority,
            'action_url'=> "/sales-invoices/$this->salesInvoiceId",
            'sender_id'=>$this->senderId,
            'sender_name'=>$user->name,
            'sender_role'=>$user->roles(),
        ];
    }

    public function toBroadcast(){

        return new BroadcastMessage([
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
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
