<?php

namespace App\Console\Commands;

use App\Models\AgendaItem;
use App\Models\User;
use App\Notifications\AgendaReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendAgendaReminders extends Command
{
    protected $signature = 'agenda:send-reminders';

    protected $description = 'Trimite mementouri pentru itemele din agenda cu reminder_at atins';

    public function handle(): int
    {
        $items = AgendaItem::with('project:id,name')
            ->whereNotIn('status', ['gata', 'arhivat'])
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->whereNull('reminded_at')
            ->get();

        if ($items->isEmpty()) {
            $this->info('Niciun mementou de trimis.');

            return self::SUCCESS;
        }

        $admins = User::role('admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('Nu exista utilizatori admin pentru mementourile din agenda.');

            return self::FAILURE;
        }

        foreach ($items as $item) {
            Notification::send($admins, new AgendaReminder($item));
            $item->update(['reminded_at' => now()]);
        }

        $this->info("Au fost trimise {$items->count()} mementouri din agenda.");

        return self::SUCCESS;
    }
}
