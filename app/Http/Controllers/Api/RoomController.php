<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rooms = Room::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->with('activeBooking.charges')
            ->orderBy('room_number')
            ->get();

        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);
        $data = $request->validate([
            'room_number' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'type' => 'required|in:ac,non_ac',
            'category' => 'required|in:single,double,triple,suite,dormitory',
            'floor' => 'nullable|string|max:20',
            'max_guests' => 'integer|min:1|max:20',
            'rate_per_night' => 'required|numeric|min:0',
            'amenities' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $data['branch_id'] = $request->user()->branch_id;

        $room = Room::create($data);

        return response()->json($room, 201);
    }

    public function show(Request $request, Room $room)
    {
        $this->authorizeBranch($request, $room);

        $room->load(['activeBooking.charges', 'bookings' => fn($q) => $q->latest()->limit(10)]);

        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $this->authorizeManage($request);
        $this->authorizeBranch($request, $room);

        $data = $request->validate([
            'room_number' => 'sometimes|string|max:20',
            'name' => 'nullable|string|max:100',
            'type' => 'sometimes|in:ac,non_ac',
            'category' => 'sometimes|in:single,double,triple,suite,dormitory',
            'floor' => 'nullable|string|max:20',
            'max_guests' => 'integer|min:1|max:20',
            'rate_per_night' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:available,occupied,cleaning,maintenance',
            'amenities' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $room->update($data);

        return response()->json($room);
    }

    public function destroy(Request $request, Room $room)
    {
        $this->authorizeManage($request);
        $this->authorizeBranch($request, $room);

        if ($room->bookings()->whereIn('status', ['reserved', 'checked_in'])->exists()) {
            return response()->json(['message' => 'Cannot delete room with active bookings.'], 422);
        }

        $room->delete();

        return response()->json(['message' => 'Room deleted.']);
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'owner', 'manager'], true) && !$user->is_super_admin) {
            abort(403, 'Only admin or manager can add or edit rooms.');
        }
    }

    private function authorizeBranch(Request $request, Room $room): void
    {
        $user = $request->user();
        if (!$user->isAdmin() && $room->branch_id !== $user->branch_id) {
            abort(403);
        }
    }
}
