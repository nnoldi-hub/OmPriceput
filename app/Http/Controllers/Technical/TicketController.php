<?php

namespace App\Http\Controllers\Technical;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketUpdated;
use App\Services\SmsService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = Ticket::query()
            ->with(['client:id,name', 'assignedTo:id,name'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('priority')->toString(), fn ($query, $priority) => $query->where('priority', $priority))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Technical/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only('status', 'priority'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Technical/Tickets/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'technicians' => User::role('tehnic')->orderBy('name')->get(['id', 'name']),
            'preselectedClientId' => $request->integer('client_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $ticket = Ticket::create($this->validateData($request));
        $ticket->events()->create([
            'user_id' => $request->user()->id,
            'type' => 'created',
            'description' => 'Tichet creat.',
        ]);

        return redirect()->route('technical.tickets.show', $ticket)->with('success', 'Tichet creat.');
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load(['client', 'assignedTo:id,name', 'installation', 'comments.user:id,name', 'events.user:id,name']);

        return Inertia::render('Technical/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function edit(Ticket $ticket): Response
    {
        return Inertia::render('Technical/Tickets/Edit', [
            'ticket' => $ticket,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'technicians' => User::role('tehnic')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update($this->validateData($request));

        return redirect()->route('technical.tickets.show', $ticket)->with('success', 'Tichet actualizat.');
    }

    public function updateStatus(Request $request, Ticket $ticket, SmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update($data);
        $ticket->events()->create([
            'user_id' => $request->user()->id,
            'type' => 'status',
            'description' => 'Status actualizat la: '.$data['status'].'.',
        ]);
        $this->notifyClient($ticket, 'Statusul cererii a fost actualizat la '.$data['status'].'.');
        if ($ticket->client->phone) {
            $sms->send($ticket->client->phone, "Cererea #{$ticket->id} are statusul {$data['status']}.");
        }

        return back()->with('success', 'Status tichet actualizat.');
    }

    public function addComment(Request $request, Ticket $ticket, SmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $ticket->events()->create([
            'user_id' => $request->user()->id,
            'type' => 'comment',
            'description' => 'Răspuns adăugat: '.$data['body'],
        ]);
        $this->notifyClient($ticket, 'Ai primit un răspuns nou la cererea ta.');
        if ($ticket->client->phone) {
            $sms->send($ticket->client->phone, "Ai primit un raspuns la cererea #{$ticket->id}.");
        }

        return back()->with('success', 'Comentariu adaugat.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ticket->delete();

        return redirect()->route('technical.tickets.index')->with('success', 'Tichet sters.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'installation_id' => ['nullable', 'exists:installations,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);
    }

    private function notifyClient(Ticket $ticket, string $message): void
    {
        if ($ticket->client->user) {
            $ticket->client->user->notify(new TicketUpdated($ticket, $message));
        } elseif ($ticket->client->email && config('notifications.mail_enabled')) {
            Notification::route('mail', $ticket->client->email)
                ->notify(new TicketUpdated($ticket, $message));
        }
    }
}
