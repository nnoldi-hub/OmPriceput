<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $this->markOverdueInvoices();
        $offersCount = Offer::count();
        $acceptedOffers = Offer::where('status', 'accepted')->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'clients' => Client::count(),
                'leads' => Client::where('status', 'lead')->count(),
                'offers' => Offer::count(),
                'acceptedOffers' => $acceptedOffers,
                'acceptedValue' => (float) Offer::where('status', 'accepted')->sum('total_amount'),
                'installationsActive' => Installation::whereIn('status', ['scheduled', 'in_progress'])->count(),
                'installationsCompleted' => Installation::where('status', 'completed')->count(),
                'conversionRate' => $offersCount > 0 ? round(($acceptedOffers / $offersCount) * 100, 1) : 0,
                'users' => User::count(),
                'invoicesUnpaid' => Invoice::where('status', 'unpaid')->count(),
                'invoicesOverdue' => Invoice::where('status', 'overdue')->count(),
                'unpaidAmount' => (float) Invoice::whereIn('status', ['unpaid', 'overdue'])
                    ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as total')
                    ->value('total'),
                'overdueAmount' => (float) Invoice::where('status', 'overdue')
                    ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as total')
                    ->value('total'),
                'revenuePaid' => (float) Invoice::where('status', 'paid')->sum('amount'),
                'revenueThisMonth' => (float) Invoice::where('status', 'paid')
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount'),
                'pipelineValue' => (float) Offer::whereIn('status', ['draft', 'sent'])->sum('total_amount'),
                'overdueActivities' => Activity::where('status', 'pending')->where('due_at', '<', now())->count(),
            ],
            'alerts' => $this->buildAlerts(),
            'recentLeads' => Client::where('status', 'lead')
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'phone', 'email', 'source', 'notes', 'created_at']),
            'pendingOffers' => Offer::with('client:id,name')
                ->whereIn('status', ['draft', 'sent'])
                ->latest()
                ->take(5)
                ->get(['id', 'client_id', 'title', 'status', 'total_amount', 'created_at']),
            'activeInstallations' => Installation::with('client:id,name')
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->orderBy('scheduled_at')
                ->take(5)
                ->get(['id', 'client_id', 'type', 'status', 'scheduled_at']),
            'openTickets' => Ticket::with('client:id,name')
                ->whereIn('status', ['open', 'in_progress'])
                ->latest()
                ->take(5)
                ->get(['id', 'client_id', 'subject', 'priority', 'status']),
            'overdueActivities' => Activity::with('client:id,name')
                ->where('status', 'pending')
                ->where('due_at', '<', now())
                ->orderBy('due_at')
                ->take(5)
                ->get(['id', 'client_id', 'title', 'priority', 'due_at']),
            'upcomingActivities' => Activity::with('client:id,name')
                ->where('status', 'pending')
                ->where(function ($query) {
                    $query->whereNull('due_at')->orWhere('due_at', '>=', now());
                })
                ->orderByRaw('due_at is null')
                ->orderBy('due_at')
                ->take(5)
                ->get(['id', 'client_id', 'title', 'priority', 'due_at']),
        ]);
    }

    private function buildAlerts(): array
    {
        $alerts = [];

        $lowStockCount = Equipment::lowStock()->count();
        if ($lowStockCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$lowStockCount} echipamente cu stoc sub 5 bucati.",
                'href' => route('technical.equipment.index', ['low_stock' => true]),
            ];
        }

        $overdueInvoices = Invoice::where('status', 'overdue')->count();
        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$overdueInvoices} facturi restante (scadenta depasita).",
                'href' => route('admin.invoices.index', ['status' => 'overdue']),
            ];
        }

        $highPriorityOpenTickets = Ticket::where('priority', 'high')->whereIn('status', ['open', 'in_progress'])->count();
        if ($highPriorityOpenTickets > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$highPriorityOpenTickets} tichete cu prioritate ridicata deschise.",
                'href' => route('technical.tickets.index', ['priority' => 'high']),
            ];
        }

        $overdueActivities = Activity::where('status', 'pending')->where('due_at', '<', now())->count();
        if ($overdueActivities > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$overdueActivities} activitati comerciale au termenul depasit.",
                'href' => route('sales.activities.index', ['status' => 'pending']),
            ];
        }

        return $alerts;
    }

    private function markOverdueInvoices(): void
    {
        Invoice::where('status', 'unpaid')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', today())
            ->update(['status' => 'overdue']);
    }
}
