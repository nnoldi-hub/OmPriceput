<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\AgendaItem;
use App\Models\Installation;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function today(): Response
    {
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        /** @var Collection<int, AgendaItem> $openItems */
        $openItems = AgendaItem::query()
            ->with('project:id,name')
            ->whereNot('status', 'gata')
            ->whereNot('status', 'arhivat')
            ->get();

        $overdue = $openItems
            ->filter(fn (AgendaItem $item) => $item->due_at !== null && $item->due_at->lt($startOfDay))
            ->sortBy('due_at')
            ->values();

        $dueToday = $openItems
            ->filter(fn (AgendaItem $item) => $item->due_at !== null && $item->due_at->between($startOfDay, $endOfDay))
            ->sortBy('due_at')
            ->values();

        $reminders = $openItems
            ->filter(fn (AgendaItem $item) => $item->reminder_at !== null && $item->reminder_at->lte(now()))
            ->sortBy('reminder_at')
            ->values();

        $appointments = Installation::query()
            ->with('client:id,name')
            ->whereBetween('scheduled_at', [$startOfDay, $endOfDay])
            ->whereNot('status', 'cancelled')
            ->orderBy('scheduled_at')
            ->get(['id', 'client_id', 'type', 'address', 'scheduled_at', 'status']);

        return Inertia::render('Agenda/Today', [
            'overdue' => $overdue,
            'dueToday' => $dueToday,
            'appointments' => $appointments,
            'reminders' => $reminders,
        ]);
    }

    public function index(): Response
    {
        $items = AgendaItem::query()
            ->with('project:id,name')
            ->whereNot('status', 'arhivat')
            ->orderByDesc('pinned_at')
            ->orderByRaw("case status when 'inbox' then 0 when 'de_facut' then 1 when 'in_lucru' then 2 else 3 end")
            ->orderBy('due_at')
            ->get();

        return Inertia::render('Agenda/Index', [
            'items' => $items,
            'projects' => Project::query()
                ->whereNot('status', 'incheiat')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        AgendaItem::create([
            'title' => $data['title'],
            'status' => 'inbox',
        ]);

        return back()->with('success', 'Adaugat in Inbox.');
    }

    public function update(Request $request, AgendaItem $agendaItem): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:10000'],
            'type' => ['sometimes', 'required', Rule::in(AgendaItem::TYPES)],
            'status' => ['sometimes', 'required', Rule::in(AgendaItem::STATUSES)],
            'priority' => ['sometimes', 'required', Rule::in(AgendaItem::PRIORITIES)],
            'project_id' => ['sometimes', 'nullable', 'exists:projects,id'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'repeat' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        if (array_key_exists('due_at', $data) && empty($data['due_at'])) {
            $data['due_at'] = null;
        }

        if (array_key_exists('project_id', $data) && empty($data['project_id'])) {
            $data['project_id'] = null;
        }

        if (array_key_exists('status', $data)) {
            $data['completed_at'] = $data['status'] === 'gata' ? now() : null;
        }

        $agendaItem->update($data);

        return back()->with('success', 'Item actualizat.');
    }

    public function toggle(AgendaItem $agendaItem): RedirectResponse
    {
        if ($agendaItem->isCompleted()) {
            $agendaItem->update(['status' => 'de_facut', 'completed_at' => null]);
        } elseif ($agendaItem->repeat) {
            $agendaItem->advanceRepeat();
        } else {
            $agendaItem->update(['status' => 'gata', 'completed_at' => now()]);
        }

        return back()->with('success', 'Status actualizat.');
    }

    public function destroy(AgendaItem $agendaItem): RedirectResponse
    {
        $agendaItem->delete();

        return back()->with('success', 'Item sters.');
    }
}
