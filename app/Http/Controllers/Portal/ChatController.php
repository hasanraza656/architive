<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared by admin and customer: the order conversation. The front end polls index() every few seconds;
 * when websockets arrive, the same JSON ("feed") can simply be pushed instead.
 */
class ChatController extends Controller
{
    public function __construct(private ChatService $chat)
    {
    }

    /** Poll: messages after ?after=ID, plus seen/status. Also marks them as read and refreshes "is on the page". */
    public function index(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $feed = $this->chat->feed($order, $request->user(), (int) $request->query('after', 0));
        $this->chat->markRead($order, $request->user());

        return response()->json($feed);
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $this->authorize('chat', $order);
        $u = config('portal.uploads');

        $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:' . $u['max_files']],
            'files.*' => ['file', 'max:' . $u['max_kb'], function ($attr, $file, $fail) use ($u) {
                if (in_array(strtolower($file->getClientOriginalExtension()), $u['blocked_extensions'], true)) {
                    $fail('This file type is not allowed. Put it in a .zip first.');
                }
            }],
        ], [
            'files.*.max' => 'Each file can be up to ' . round($u['max_kb'] / 1024) . ' MB.',
            'files.*.uploaded' => 'A file could not be uploaded. It may be bigger than the server allows (' . ini_get('upload_max_filesize') . ').',
        ]);

        $body = trim((string) $request->input('body'));
        $files = $request->file('files', []);
        if ($body === '' && empty($files)) {
            return response()->json(['message' => 'Write a message or attach a file.', 'errors' => ['body' => ['Write a message or attach a file.']]], 422);
        }

        $message = $this->chat->post($order, $request->user(), $body, $files);

        return response()->json($this->chat->toArray($message->load('user', 'files'), $request->user()), 201);
    }
}
