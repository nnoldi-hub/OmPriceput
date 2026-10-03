<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\Offer;
use App\Models\Installation;
use App\Notifications\TicketUpdated;
use App\Notifications\OfferAvailable;
use App\Notifications\OfferStatusChanged;
use App\Services\SmsService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $client = $request->user()->clientProfile;
        $invoices = $client->invoices;

        return Inertia::render('Client/Dashboard', [
            'client' => $client,
            'openTickets' => $client->tickets()->whereIn('status', ['open', 'in_progress'])->count(),
            'scheduledWorks' => $client->installations()->where('status', 'scheduled')->whereNotNull('scheduled_at')->count(),
            'unpaidInvoices' => $invoices->whereIn('status', ['unpaid', 'overdue'])->count(),
            'balance' => round($invoices->whereIn('status', ['unpaid', 'overdue'])->sum(fn ($invoice) => max((float) $invoice->amount - (float) $invoice->paid_amount, 0)), 2),
            'unreadNotifications' => $request->user()->unreadNotifications()->count(),
            'recentTickets' => $client->tickets()->with('assignedTo:id,name')->latest()->take(5)->get(),
        ]);
    }

    public function tickets(Request $request): Response
    {
        $client = $request->user()->clientProfile;

        return Inertia::render('Client/Tickets/Index', ['tickets' => $client->tickets()->with(['assignedTo:id,name', 'events.user:id,name'])->latest()->paginate(10)]);
    }

    public function addTicketComment(Request $request, int $ticket): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $record = $request->user()->clientProfile->tickets()->findOrFail($ticket);
        $record->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        $record->events()->create([
            'user_id' => $request->user()->id,
            'type' => 'comment',
            'description' => 'Răspuns adăugat de client: '.$data['body'],
        ]);

        $staff = \App\Models\User::role(['admin', 'tehnic', 'suport'])->get();
        Notification::send($staff, new TicketUpdated($record, 'Clientul a răspuns la cererea #'.$record->id.'.'));

        return back()->with('success', 'Răspunsul a fost trimis.');
    }

    public function offers(Request $request): Response
    {
        return Inertia::render('Client/Offers/Index', [
            'offers' => $request->user()->clientProfile->offers()
                ->with('items')
                ->whereIn('status', ['sent', 'accepted', 'rejected'])
                ->latest()
                ->get(),
        ]);
    }

    public function updateOfferStatus(Request $request, int $offer): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);
        $record = $request->user()->clientProfile->offers()->findOrFail($offer);
        abort_unless($record->status === 'sent', 422, 'Oferta nu mai este disponibila pentru raspuns.');

        $record->update(['status' => $data['status']]);
        $record->load('user');

        if ($data['message']) {
            $record->client->tickets()->latest()->first()?->events()->create([
                'user_id' => $request->user()->id,
                'type' => 'comment',
                'description' => 'Răspuns la ofertă: '.$data['message'],
            ]);
        }

        if ($record->user) {
            $record->user->notify(new OfferStatusChanged($record, $data['status'], $data['message'] ?? null));
        }

        if ($data['status'] === 'accepted') {
            $record->client->update(['status' => 'client']);
            $this->createInstallationFromAcceptedOffer($record);
        }

        return back()->with('success', $data['status'] === 'accepted'
            ? 'Oferta a fost acceptata.'
            : 'Oferta a fost respinsa.');
    }

    public function storeTicket(Request $request, SmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);
        $data['client_id'] = $request->user()->clientProfile->id;
        $ticket = Ticket::create($data);
        $ticket->events()->create([
            'user_id' => $request->user()->id,
            'type' => 'created',
            'description' => 'Cerere trimisă de client.',
        ]);
        $clientUser = $ticket->client->user;
        if ($clientUser) {
            Notification::send($clientUser, new TicketUpdated($ticket, 'Cererea ta a fost înregistrată și va fi preluată de echipa noastră.'));
        }
        $staff = \App\Models\User::role(['admin', 'tehnic', 'suport'])->get();
        Notification::send($staff, new TicketUpdated($ticket, 'A fost creată o cerere nouă de către '.$ticket->client->name.'.'));
        if ($ticket->client->phone) {
            $sms->send($ticket->client->phone, "Cererea #{$ticket->id} a fost inregistrata.");
        }

        return back()->with('success', 'Cererea a fost trimisa.');
    }

    public function works(Request $request): Response
    {
        $client = $request->user()->clientProfile;

        return Inertia::render('Client/Works/Index', ['works' => $client->installations()->with('technician:id,name')->latest('scheduled_at')->paginate(10)]);
    }

    public function workReport(Request $request, int $installation): SymfonyResponse
    {
        $record = $request->user()->clientProfile->installations()->with(['client', 'technician:id,name'])->findOrFail($installation);

        return Pdf::loadView('pdfs.installation-report', [
            'installation' => $record,
            'settings' => Setting::allSettings(),
        ])
            ->stream("raport-lucrare-{$record->id}.pdf");
    }

    public function invoices(Request $request): Response
    {
        $client = $request->user()->clientProfile;
        $invoices = $client->invoices()->with('payments')->latest('issued_at')->paginate(10);

        return Inertia::render('Client/Invoices/Index', [
            'invoices' => $invoices,
            'summary' => [
                'invoiceTotal' => (float) $client->invoices->sum('amount'),
                'invoicePaid' => (float) $client->invoices->sum('paid_amount'),
                'invoiceBalance' => (float) $client->invoices->whereIn('status', ['unpaid', 'overdue'])->sum(fn ($invoice) => max((float) $invoice->amount - (float) $invoice->paid_amount, 0)),
            ],
        ]);
    }

    public function invoicePdf(Request $request, int $invoice): SymfonyResponse
    {
        $record = $request->user()->clientProfile->invoices()->findOrFail($invoice);
        $record->load('client');

        return Pdf::loadView('pdfs.invoice', [
            'invoice' => $record,
            'settings' => Setting::allSettings(),
        ])->stream("factura-{$record->invoice_number}.pdf");
    }

    public function statementPdf(Request $request): SymfonyResponse
    {
        $client = $request->user()->clientProfile;
        $invoices = $client->invoices()->with('payments')->latest()->get();
        $payments = $invoices->flatMap->payments->sortByDesc('paid_at')->values();

        return Pdf::loadView('pdfs.client-statement', [
            'client' => $client,
            'invoices' => $invoices,
            'payments' => $payments,
            'summary' => [
                'invoiceTotal' => (float) $invoices->sum('amount'),
                'invoicePaid' => (float) $invoices->sum('paid_amount'),
                'invoiceBalance' => (float) $invoices->whereIn('status', ['unpaid', 'overdue'])->sum(fn ($invoice) => max((float) $invoice->amount - (float) $invoice->paid_amount, 0)),
                'overdueInvoices' => $invoices->where('status', 'overdue')->count(),
            ],
            'settings' => Setting::allSettings(),
        ])->stream('situatie-financiara-'.str($client->name)->slug().'.pdf');
    }

    public function subscriptions(Request $request): Response
    {
        return Inertia::render('Client/Subscriptions/Index', ['subscriptions' => $request->user()->clientProfile->subscriptions()->latest()->get()]);
    }

    public function equipment(Request $request): Response
    {
        return Inertia::render('Client/Equipment/Index', ['equipment' => Equipment::where('client_id', $request->user()->clientProfile->id)->latest()->get()]);
    }

    public function shopOrders(Request $request): Response
    {
        $client = $request->user()->clientProfile;

        return Inertia::render('Client/ShopOrders/Index', [
            'orders' => $client->shopOrders()->with('items')->latest()->paginate(10),
        ]);
    }

    public function shopOrderShow(Request $request, int $order): Response
    {
        $record = $request->user()->clientProfile->shopOrders()->with(['items', 'invoice'])->findOrFail($order);

        return Inertia::render('Client/ShopOrders/Show', ['order' => $record]);
    }

    public function notifications(Request $request): Response
    {
        return Inertia::render('Client/Notifications/Index', ['notifications' => $request->user()->notifications()->latest()->paginate(20)]);
    }

    public function readNotification(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->update(['read_at' => now()]);

        return back();
    }

    public function readAllNotifications(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Toate notificarile au fost marcate ca citite.');
    }

    private function createInstallationFromAcceptedOffer(Offer $offer): void
    {
        if (Installation::where('offer_id', $offer->id)->exists()) {
            return;
        }

        $offer->load(['items.equipment', 'items.service']);

        $type = in_array($offer->job_type, Installation::TYPES, true) ? $offer->job_type : 'instalare';

        Installation::create([
            'client_id' => $offer->client_id,
            'offer_id' => $offer->id,
            'type' => $type,
            'requested_type' => $type,
            'address' => trim(($offer->client->address ?? '').' '.($offer->client->city ?? '')),
            'status' => 'scheduled',
            'checklist' => Installation::defaultChecklist($type),
            'material_items' => $offer->items->whereNotNull('equipment_id')->map(fn ($item) => [
                'equipment_id' => $item->equipment_id,
                'name' => $item->equipment?->name ?? $item->description,
                'unit' => $item->equipment?->unit ?? 'buc',
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
            'service_items' => $offer->items->whereNotNull('service_id')->map(fn ($item) => [
                'service_id' => $item->service_id,
                'name' => $item->service?->name ?? $item->description,
                'unit' => $item->service?->unit ?? 'ora',
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
            'notes' => 'Lucrare generata automat la acceptarea devizului #'.$offer->id.'.',
        ]);
    }
}
