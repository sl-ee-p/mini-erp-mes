<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Notifications;

use App\Models\Material;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Material $material) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unit = $this->material->unit->label();

        return (new MailMessage)
            ->subject("Low stock: {$this->material->name}")
            ->line("{$this->material->name} ({$this->material->sku}) dropped below its minimum stock level.")
            ->line("On hand: {$this->material->stock_qty} {$unit} · Minimum: {$this->material->min_stock} {$unit}")
            ->action('Open material', route('materials.show', $this->material));
    }
}
