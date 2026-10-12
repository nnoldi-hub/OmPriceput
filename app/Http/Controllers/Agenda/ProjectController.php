<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Measurement;
use App\Models\Offer;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::query()
            ->with('client:id,name')
            ->withCount([
                'agendaItems as open_items_count' => fn ($q) => $q->whereNot('status', 'gata')->whereNot('status', 'arhivat'),
                'agendaItems as done_items_count' => fn ($q) => $q->where('status', 'gata'),
                'agendaItems as total_items_count' => fn ($q) => $q->whereNot('status', 'arhivat'),
            ])
            ->orderByRaw("case status when 'incheiat' then 1 else 0 end")
            ->orderBy('name')
            ->get();

        return Inertia::render('Agenda/Projects/Index', [
            'projects' => $projects,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = Project::create($this->validateData($request));

        return redirect()->route('agenda.projects.show', $project)->with('success', 'Proiect creat.');
    }

    public function show(Project $project): Response
    {
        $project->load('client:id,name');

        $items = $project->agendaItems()
            ->with(['project:id,name', 'tags:id,name'])
            ->whereNot('status', 'arhivat')
            ->orderByRaw("case status when 'gata' then 1 else 0 end")
            ->orderBy('due_at')
            ->get();

        $doneCount = $items->where('status', 'gata')->count();
        $totalCount = $items->count();

        $measurements = $project->measurements()
            ->with('offer:id,measurement_id,total_amount')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Measurement $measurement) => [
                'id' => $measurement->id,
                'name' => $measurement->name,
                'status' => $measurement->status,
                'status_label' => $measurement->statusLabel(),
                'total_area' => $measurement->totalArea(),
                'total_perimeter' => $measurement->totalPerimeter(),
                'created_at' => $measurement->created_at?->format('d.m.Y'),
                'offer' => $measurement->offer ? [
                    'id' => $measurement->offer->id,
                    'total_amount' => (float) $measurement->offer->total_amount,
                ] : null,
            ]);

        $offers = $project->offers()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Offer $offer) => [
                'id' => $offer->id,
                'title' => $offer->title,
                'status' => $offer->status,
                'status_label' => Offer::STATUS_LABELS[$offer->status] ?? $offer->status,
                'total_amount' => (float) $offer->total_amount,
                'valid_until' => $offer->valid_until?->format('d.m.Y'),
                'created_at' => $offer->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('Agenda/Projects/Show', [
            'project' => $project,
            'items' => $items,
            'progress' => $totalCount > 0 ? (int) round(($doneCount / $totalCount) * 100) : 0,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'measurements' => $measurements,
            'offers' => $offers,
        ]);
    }

    public function storeMeasurement(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $measurement = $project->measurements()->create([
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'client_id' => $project->client_id,
            'status' => 'in_progress',
        ]);

        return redirect()->route('measurements.show', $measurement)->with('success', 'Masuratoare pornita.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validateData($request));

        return back()->with('success', 'Proiect actualizat.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('agenda.projects.index')->with('success', 'Proiect sters.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Project::TYPES)],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'client_id' => ['sometimes', 'nullable', 'exists:clients,id'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        if (array_key_exists('client_id', $data) && empty($data['client_id'])) {
            $data['client_id'] = null;
        }

        return $data;
    }
}
