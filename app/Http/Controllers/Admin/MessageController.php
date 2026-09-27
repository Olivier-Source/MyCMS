<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function index(): View
    {
        return view('admin.messages.index', [
            'messages' => ContactMessage::latest()->paginate(25),
            'retention' => config('mycms.messages_retention_months'),
        ]);
    }

    public function show(ContactMessage $message): View
    {
        if (! $message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.messages.show', ['message' => $message]);
    }

    public function unread(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => null]);

        return redirect()->route('admin.messages.index')->with('status', __('Message marked as unread.'));
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();
        ActivityLog::record('message.deleted', __('Contact message deleted'));

        return redirect()->route('admin.messages.index')->with('status', __('Message permanently deleted.'));
    }
}
