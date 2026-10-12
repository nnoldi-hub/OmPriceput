<?php

namespace App\Http\Controllers;

use App\Models\AgendaItem;
use App\Models\Client;
use App\Models\Offer;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        $empty = ['clients' => [], 'offers' => [], 'tickets' => [], 'agenda_items' => [], 'projects' => []];

        if (mb_strlen($query) < 2) {
            return response()->json($empty);
        }

        $user = $request->user();
        $canSales = $user->hasAnyRole(['admin', 'vanzari']);
        $canTechnical = $user->hasAnyRole(['admin', 'tehnic', 'suport']);
        $canAgenda = $user->hasAnyRole(['admin']);

        return response()->json([
            'clients' => $canSales || $canTechnical
                ? Client::where('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->take(5)
                    ->get(['id', 'name', 'phone'])
                : [],
            'offers' => $canSales
                ? Offer::with('client:id,name')
                    ->where('title', 'like', "%{$query}%")
                    ->take(5)
                    ->get(['id', 'client_id', 'title', 'status'])
                : [],
            'tickets' => $canTechnical
                ? Ticket::with('client:id,name')
                    ->where('subject', 'like', "%{$query}%")
                    ->take(5)
                    ->get(['id', 'client_id', 'subject', 'status'])
                : [],
            'agenda_items' => $canAgenda
                ? AgendaItem::with('project:id,name')
                    ->whereNot('status', 'arhivat')
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                            ->orWhere('text', 'like', "%{$query}%");
                    })
                    ->take(5)
                    ->get(['id', 'title', 'type', 'status', 'project_id', 'due_at'])
                : [],
            'projects' => $canAgenda
                ? Project::with('client:id,name')
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    })
                    ->take(5)
                    ->get(['id', 'name', 'type', 'status', 'client_id'])
                : [],
        ]);
    }
}
