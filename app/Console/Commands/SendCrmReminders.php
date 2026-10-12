<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendCrmReminders extends Command
{
    protected $signature = 'crm:send-reminders';

    protected $description = 'Trimite remindere pentru activitatile comerciale restante';

    public function handle(): int
    {
        $activities = Activity::with(['client', 'assignedTo'])
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where(function ($query) {
                $query->whereNull('last_reminded_at')
                    ->orWhere('last_reminded_at', '<', now()->startOfDay());
            })
            ->get();

        $admins = null;
        $sent = 0;

        foreach ($activities as $activity) {
            $recipients = $activity->assignedTo ? collect([$activity->assignedTo]) : collect();

            if ($recipients->isEmpty()) {
                $admins ??= User::role('admin')->get();
                $recipients = $admins;
            }

            if ($recipients->isEmpty()) {
                $this->warn("Nu exista destinatari pentru activitatea #{$activity->id}.");

                continue;
            }

            Notification::send($recipients, new ActivityReminder($activity));
            $activity->update(['last_reminded_at' => now()]);
            $sent++;
        }

        $this->info("Au fost trimise {$sent} remindere CRM.");

        return self::SUCCESS;
    }
}
