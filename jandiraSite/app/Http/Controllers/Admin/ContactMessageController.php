<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('busca')->toString());
        $status = $request->string('status')->toString();

        if (! in_array($status, ['todos', 'nao-lidas', 'lidas'], true)) {
            $status = 'todos';
        }

        $messages = ContactMessage::query()
            ->when($search !== '', function ($query) use ($search) {
                $pattern = "%{$search}%";

                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery
                        ->where('name', 'ilike', $pattern)
                        ->orWhere('email', 'ilike', $pattern)
                        ->orWhere('phone', 'ilike', $pattern)
                        ->orWhere('subject', 'ilike', $pattern)
                        ->orWhere('message', 'ilike', $pattern);
                });
            })
            ->when($status === 'nao-lidas', fn ($query) => $query->whereNull('read_at'))
            ->when($status === 'lidas', fn ($query) => $query->whereNotNull('read_at'))
            ->orderByRaw('read_at IS NOT NULL')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ContactMessage $message) => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'subject' => $message->subject,
                'message' => $message->message,
                'is_read' => $message->isRead(),
                'read_at' => $message->read_at?->format('d/m/Y H:i'),
                'created_at' => $message->created_at?->format('d/m/Y H:i'),
                'is_registered_customer' => $message->user_id !== null,
            ]);

        return Inertia::render('admin/messages/index', [
            'messages' => $messages,
            'stats' => [
                'total' => ContactMessage::query()->count(),
                'unread' => ContactMessage::query()->whereNull('read_at')->count(),
                'read' => ContactMessage::query()->whereNotNull('read_at')->count(),
                'today' => ContactMessage::query()->where('created_at', '>=', now()->startOfDay())->count(),
            ],
            'filters' => [
                'busca' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function toggleRead(ContactMessage $message): RedirectResponse
    {
        $isNowRead = ! $message->isRead();

        $message->update([
            'read_at' => $isNowRead ? now() : null,
        ]);

        return back()->with(
            'success',
            $isNowRead ? 'Mensagem marcada como lida.' : 'Mensagem marcada como não lida.',
        );
    }
}
