<?php

namespace App\Http\Controllers\Technical;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Ticket;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Technical/Dashboard', [
            'stats' => [
                'equipment' => Equipment::count(),
                'lowStock' => Equipment::lowStock()
                    ->count(),
                'scheduled' => Installation::where('status', 'scheduled')->count(),
                'inProgress' => Installation::where('status', 'in_progress')->count(),
                'openTickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            ],
            'pendingVisits' => Installation::with('client:id,name')
                ->whereNull('scheduled_at')
                ->where('status', 'scheduled')
                ->latest('id')
                ->take(5)
                ->get(['id', 'client_id', 'type', 'requested_type', 'scheduled_at', 'status']),
            'upcomingInstallations' => Installation::with('client:id,name', 'technician:id,name')
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->whereNotNull('scheduled_at')
                ->orderBy('scheduled_at')
                ->take(5)
                ->get(['id', 'client_id', 'technician_id', 'type', 'scheduled_at', 'status']),
            'openTicketsList' => Ticket::with('client:id,name')
                ->whereIn('status', ['open', 'in_progress'])
                ->latest()
                ->take(5)
                ->get(['id', 'client_id', 'subject', 'priority', 'status']),
        ]);
    }
}
