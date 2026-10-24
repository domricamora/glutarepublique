<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/** The bell in the office header: alerts for the signed-in person (App\Support\ClinicEvents). */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/notifications', [
            'notifications' => $request->user()->notifications()->latest()->limit(50)->get()
                ->map(fn (DatabaseNotification $n) => [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? '',
                    'body' => $n->data['body'] ?? '',
                    'event' => $n->data['event'] ?? '',
                    'read' => $n->read_at !== null,
                    'created_at' => $n->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /** Opens what the alert is about, and marks it read. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->to($notification->data['url'] ?? route('admin.notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All caught up.');
    }
}
