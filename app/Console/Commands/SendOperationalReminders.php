<?php

namespace App\Console\Commands;

use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\OperationalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendOperationalReminders extends Command
{
    protected $signature = 'operations:send-reminders';

    protected $description = 'Trimite remindere pentru facturi, programari si stoc scazut';

    public function handle(): int
    {
        $admins = User::role('admin')->get();
        $technicalUsers = User::role(['admin', 'tehnic'])->get();
        $sent = 0;

        $overdue = Invoice::where('status', 'overdue')
            ->whereRaw('amount > paid_amount')
            ->count();
        if ($overdue > 0 && $admins->isNotEmpty()) {
            Notification::send($admins, new OperationalReminder(
                'overdue_invoices',
                'Facturi restante',
                "{$overdue} facturi au sold restant si necesita urmarire.",
                route('admin.invoices.index', ['status' => 'overdue']),
            ));
            $sent++;
        }

        $tomorrow = Installation::with('technician')
            ->where('status', 'scheduled')
            ->whereDate('scheduled_at', today()->addDay())
            ->get();
        foreach ($tomorrow as $installation) {
            $recipients = $installation->technician ? collect([$installation->technician]) : $technicalUsers;
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new OperationalReminder(
                    'tomorrow_installation',
                    'Programare pentru maine',
                    "Exista o lucrare programata pentru {$installation->scheduled_at->format('d.m.Y H:i')}.",
                    route('technical.installations.show', $installation),
                ));
                $sent++;
            }
        }

        $lowStock = Equipment::lowStock()->count();
        if ($lowStock > 0 && $technicalUsers->isNotEmpty()) {
            Notification::send($technicalUsers, new OperationalReminder(
                'low_stock',
                'Stoc sub pragul minim',
                "{$lowStock} materiale necesita reaprovizionare.",
                route('technical.equipment.index', ['low_stock' => 1]),
            ));
            $sent++;
        }

        $this->info("Au fost trimise {$sent} notificari operationale.");

        return self::SUCCESS;
    }
}
