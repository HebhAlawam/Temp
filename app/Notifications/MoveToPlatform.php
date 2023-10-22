<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MoveToPlatform extends Notification
{
    use Queueable;
    protected $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->url = 'https://www.platform.osboha180.com';
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
            ->from('no-replay@osboha180.com', 'Osboha 180')
            ->subject('������ || ��� ���� ������� ������')
            ->line('��� ���� ������ ������ ')
            ->line('���� ���� ���� ')
            ->line('')
            ->line('�������� ����� �� ���� ����� ����ȡ  �� ��� ���� �� �� ���� ������ 180 ���� ����� ���� ���� ���� ������� �� ������.')
            ->line('')
            ->line('�������� ��� ������')
            ->action('���� ������: ', $this->url);

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
