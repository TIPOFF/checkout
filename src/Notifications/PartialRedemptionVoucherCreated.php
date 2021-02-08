<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Notifications;

use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartialRedemptionVoucherCreated extends Notification
{
    use Queueable;

    /**
     * Voucher.
     *
     * @var Voucher
     */
    public $voucher;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Voucher $voucher)
    {
        $this->voucher = $voucher;
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
        $message = (new MailMessage)
            ->subject('Partial Redemption')
            ->line('Your voucher was partially redeemed.')
            ->line('New amount: ' . $this->voucher->decoratedAmount())
            ->line('New voucher code: ' . $this->voucher->code);

        return $message;
    }
}
