<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\PageView;
use App\Models\ShopOrder;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $roles = $request->user()->getRoleNames();
        $isAdmin = $roles->contains('admin');
        $stats = [];

        if ($isAdmin || $roles->contains('vanzari')) {
            $stats['leads'] = Client::where('status', 'lead')->count();
            $stats['pipelineValue'] = (float) Offer::whereIn('status', ['draft', 'sent'])->sum('total_amount');
        }

        if ($isAdmin || $roles->contains('tehnic') || $roles->contains('suport')) {
            $stats['openTickets'] = Ticket::whereIn('status', ['open', 'in_progress'])->count();
            $stats['lowStock'] = Equipment::lowStock()->count();
        }

        if ($isAdmin) {
            $stats['unpaidAmount'] = (float) Invoice::whereIn('status', ['unpaid', 'overdue'])
                ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as total')
                ->value('total');
            $stats['newShopOrders'] = ShopOrder::where('status', 'new')->count();
            $stats['todayPageViews'] = PageView::human()->whereDate('created_at', today())->count();
        }

        return Inertia::render('Dashboard', ['stats' => $stats]);
    }
}
