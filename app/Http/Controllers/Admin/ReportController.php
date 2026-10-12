<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\Measurement;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(): Response
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $revenueByMonth = $months->map(function ($month) {
            return [
                'month' => $month->format('M Y'),
                'amount' => (float) Invoice::where('status', 'paid')
                    ->whereBetween('paid_at', [$month, $month->copy()->endOfMonth()])
                    ->sum('amount'),
            ];
        });

        $offersByMonth = $months->map(function ($month) {
            return [
                'month' => $month->format('M Y'),
                'count' => Offer::whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->count(),
            ];
        });

        return Inertia::render('Admin/Reports/Index', [
            'sales' => [
                'totalClients' => Client::count(),
                'newLeadsThisMonth' => Client::where('status', 'lead')->whereMonth('created_at', now()->month)->count(),
                'offersByStatus' => Offer::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
                'conversionRate' => $this->conversionRate(),
                'offersByMonth' => $offersByMonth,
            ],
            'technical' => [
                'installationsByStatus' => Installation::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
                'ticketsByStatus' => Ticket::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
                'ticketsByPriority' => Ticket::select('priority', DB::raw('count(*) as total'))->groupBy('priority')->pluck('total', 'priority'),
            ],
            'financial' => [
                'revenueByMonth' => $revenueByMonth,
                'unpaidTotal' => (float) Invoice::whereIn('status', ['unpaid', 'overdue'])
                    ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as total')
                    ->value('total'),
                'paidTotal' => (float) Invoice::where('status', 'paid')->sum('amount'),
                'overdueCount' => Invoice::where('status', 'overdue')->count(),
                'expensesTotal' => (float) Expense::sum('amount'),
                'expensesByCategory' => Expense::select('category', DB::raw('sum(amount) as total'))->groupBy('category')->pluck('total', 'category'),
                'expensesBySupplier' => Expense::query()
                    ->leftJoin('suppliers', 'suppliers.id', '=', 'expenses.supplier_id')
                    ->selectRaw("COALESCE(suppliers.name, 'Fara furnizor') as supplier, SUM(expenses.amount) as total")
                    ->groupBy('suppliers.name')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->pluck('total', 'supplier'),
            ],
        ]);
    }

    private function conversionRate(): float
    {
        $total = Client::count();

        if ($total === 0) {
            return 0;
        }

        return round((Client::where('status', 'client')->count() / $total) * 100, 1);
    }

    public function profit(Request $request): Response
    {
        $from = $request->date('from') ?? now()->subMonths(5)->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $installations = Installation::query()
            ->with(['client:id,name', 'technician:id,name', 'offer:id,total_amount'])
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->get()
            ->map(function (Installation $installation) {
                $report = $installation->cost_report;

                return [
                    'id' => $installation->id,
                    'client_id' => $installation->client_id,
                    'client_name' => $installation->client?->name ?? 'Necunoscut',
                    'technician_id' => $installation->technician_id,
                    'technician_name' => $installation->technician?->name ?? 'Nealocat',
                    'completed_at' => $installation->completed_at?->format('d.m.Y'),
                    'type' => $installation->type,
                    'offer_value' => $report['offer_value'],
                    'material_cost' => $report['material_cost'],
                    'labor_cost' => $report['labor_cost'],
                    'actual_expenses' => $report['actual_expenses'],
                    'profit' => $report['final_profit'] ?? 0,
                ];
            });

        $byClient = $installations->groupBy('client_name')->map(function ($rows, $clientName) {
            return [
                'client' => $clientName,
                'installations' => $rows->count(),
                'revenue' => round($rows->sum('offer_value'), 2),
                'cost' => round($rows->sum('material_cost') + $rows->sum('labor_cost') + $rows->sum('actual_expenses'), 2),
                'profit' => round($rows->sum('profit'), 2),
            ];
        })->sortByDesc('profit')->values();

        $byTechnician = $installations->groupBy('technician_name')->map(function ($rows, $technicianName) {
            return [
                'technician' => $technicianName,
                'installations' => $rows->count(),
                'revenue' => round($rows->sum('offer_value'), 2),
                'cost' => round($rows->sum('material_cost') + $rows->sum('labor_cost') + $rows->sum('actual_expenses'), 2),
                'profit' => round($rows->sum('profit'), 2),
            ];
        })->sortByDesc('profit')->values();

        return Inertia::render('Admin/Reports/Profit', [
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'summary' => [
                'installations' => $installations->count(),
                'revenue' => round($installations->sum('offer_value'), 2),
                'cost' => round($installations->sum('material_cost') + $installations->sum('labor_cost') + $installations->sum('actual_expenses'), 2),
                'profit' => round($installations->sum('profit'), 2),
            ],
            'byClient' => $byClient,
            'byTechnician' => $byTechnician,
            'installations' => $installations->sortByDesc('completed_at')->values(),
        ]);
    }

    public function measurements(Request $request): Response
    {
        $from = $request->date('from') ?? now()->subMonths(5)->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $areaPrice = (float) (Setting::get('pricing_area_m2') ?? '0');
        $perimeterPrice = (float) (Setting::get('pricing_perimeter_ml') ?? '0');

        $measurements = Measurement::query()
            ->with(['client:id,name', 'objects', 'offer:id,measurement_id,total_amount'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $rows = $measurements->map(function (Measurement $measurement) use ($areaPrice, $perimeterPrice) {
            $area = $measurement->totalArea();
            $perimeter = $measurement->totalPerimeter();

            return [
                'id' => $measurement->id,
                'name' => $measurement->name,
                'location' => $measurement->location,
                'client_name' => $measurement->client?->name ?? 'Fara client',
                'status' => $measurement->status,
                'created_at' => $measurement->created_at?->format('d.m.Y'),
                'area' => round($area, 1),
                'perimeter' => round($perimeter, 1),
                'estimate' => round($area * $areaPrice + $perimeter * $perimeterPrice, 2),
                'offer' => $measurement->offer ? [
                    'id' => $measurement->offer->id,
                    'total_amount' => (float) $measurement->offer->total_amount,
                ] : null,
            ];
        });

        $months = collect();
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lte($to->copy()->startOfMonth())) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        $byMonth = $months->map(function ($month) {
            return [
                'month' => $month->format('M Y'),
                'amount' => Measurement::whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->count(),
            ];
        });

        $byStatus = array_merge(
            ['in_progress' => 0, 'completed' => 0],
            Measurement::select('status', DB::raw('count(*) as total'))
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all()
        );

        return Inertia::render('Admin/Reports/Measurements', [
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'summary' => [
                'measurements' => $measurements->count(),
                'completed' => $measurements->where('status', 'completed')->count(),
                'offers' => $measurements->filter(fn ($m) => $m->offer)->count(),
                'totalArea' => round($measurements->sum(fn ($m) => $m->totalArea()), 1),
                'totalPerimeter' => round($measurements->sum(fn ($m) => $m->totalPerimeter()), 1),
                'estimatedValue' => round($measurements->sum(function ($m) use ($areaPrice, $perimeterPrice) {
                    return $m->totalArea() * $areaPrice + $m->totalPerimeter() * $perimeterPrice;
                }), 2),
                'offerValue' => round($measurements->sum(fn ($m) => (float) optional($m->offer)->total_amount), 2),
            ],
            'byMonth' => $byMonth,
            'byStatus' => $byStatus,
            'measurements' => $rows->sortByDesc('id')->values(),
        ]);
    }
}
