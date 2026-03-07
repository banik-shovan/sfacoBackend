<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlumniEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = AlumniEvent::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'events' => $events->map(fn (AlumniEvent $event) => $event->toApiArray())->values(),
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $events = AlumniEvent::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'events' => $events->map(fn (AlumniEvent $event) => $event->toApiArray())->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $event = AlumniEvent::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $event->toApiArray(),
        ], 201);
    }

    public function update(Request $request, AlumniEvent $event): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $event->fill([
            ...$validated,
            'updated_by' => $request->user()->id,
        ])->save();

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $event->fresh()->toApiArray(),
        ]);
    }

    public function destroy(AlumniEvent $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'date_label' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
            'action_label' => ['nullable', 'string', 'max:120'],
            'action_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
