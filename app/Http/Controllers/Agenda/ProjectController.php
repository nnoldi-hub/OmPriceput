<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Client;
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
            ->with('project:id,name')
            ->whereNot('status', 'arhivat')
            ->orderByRaw("case status when 'gata' then 1 else 0 end")
            ->orderBy('due_at')
            ->get();

        $doneCount = $items->where('status', 'gata')->count();
        $totalCount = $items->count();

        return Inertia::render('Agenda/Projects/Show', [
            'project' => $project,
            'items' => $items,
            'progress' => $totalCount > 0 ? (int) round(($doneCount / $totalCount) * 100) : 0,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
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
