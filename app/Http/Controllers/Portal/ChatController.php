<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * Shared by customer and team: the order conversation. The front end polls index() every few seconds;
 * when websockets arrive, the same JSON ("feed") can simply be pushed instead.
 */
class ChatController extends Controller
{
    public function __construct(private ChatService $chat)
    {
    }

    /**
     * Poll / first load. ?after=ID new messages · ?before=ID older page · ?since=ISO changed messages (reactions, thread counts)
     * · ?thread=ID&thread_after=ID the open thread. Also marks the chat read and refreshes "is on the page".
     */
    public function index(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $feed = $this->chat->feed($order, $request->user(), $request->only(['after', 'before', 'limit', 'since', 'thread', 'thread_after']));
        if (! $request->filled('before')) {
            $this->chat->markRead($order, $request->user());
        }

        return response()->json($feed);
    }

    public function thread(Request $request, Order $order, OrderMessage $message): JsonResponse
    {
        $this->authorize('view', $order);
        abort_unless((int) $message->order_id === (int) $order->id, 404);
        $data = $this->chat->thread($order, $message->thread_root_id ?: $message->id, $request->user());
        abort_unless($data, 404);
        $data['target'] = $message->id;     // lets the front end open the thread and highlight a reply (inbox links)

        return response()->json($data);
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $this->authorize('chat', $order);
        $u = config('portal.uploads');
        $media = config('portal.media');

        $this->mergeChunkedUploads($request);

        $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'reply_to' => ['nullable', 'integer'],
            'files' => ['nullable', 'array', 'max:' . $u['max_files']],
            'files.*' => ['file', 'max:' . $u['max_kb'], function ($attr, $file, $fail) use ($u) {
                if (in_array(strtolower($file->getClientOriginalExtension()), $u['blocked_extensions'], true)) {
                    $fail('This file type is not allowed. Put it in a .zip first.');
                }
            }],
            'kinds.*' => ['nullable', 'in:file,voice,video'],
            'durations.*' => ['nullable', 'integer', 'min:0', 'max:' . max($media['voice_max_seconds'], $media['screen_max_seconds'])],
            'metas.*' => ['nullable', 'string', 'max:4000'],
            'previews.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'annotated_of.*' => ['nullable', 'integer'],
        ], [
            'files.*.max' => 'Each file can be up to ' . round($u['max_kb'] / 1024) . ' MB.',
            'files.*.uploaded' => 'A file could not be uploaded. It may be bigger than the server allows (' . ini_get('upload_max_filesize') . ').',
        ]);

        $body = trim((string) $request->input('body'));
        $files = $request->file('files', []);
        if ($body === '' && empty($files)) {
            return response()->json(['message' => 'Write a message or attach a file.', 'errors' => ['body' => ['Write a message or attach a file.']]], 422);
        }

        $extras = [];
        foreach (array_keys($files) as $i) {
            $originalId = (int) ($request->input("annotated_of.$i") ?: 0);
            $extras[$i] = [
                'kind' => $request->input("kinds.$i"),
                'duration' => $request->input("durations.$i"),
                'meta' => json_decode((string) $request->input("metas.$i"), true) ?: [],
                'preview' => $request->file("previews.$i"),
                'annotated_of' => $originalId && OrderFile::where('order_id', $order->id)->whereKey($originalId)->exists() ? $originalId : null,
            ];
        }

        $message = $this->chat->post($order, $request->user(), $body, $files, ['reply_to' => $request->integer('reply_to') ?: null, 'extras' => $extras]);

        return response()->json($this->chat->present(collect([$message]), $request->user())[0], 201);
    }

    /**
     * Big files arrive in small pieces (see chunk()) so a proxy or server body-size limit cannot break them. The message
     * request then only names the finished upload; here the pieces are joined and handed on like any other uploaded file.
     */
    private function mergeChunkedUploads(Request $request): void
    {
        $ids = (array) $request->input('upload_ids', []);
        if (! $ids) {
            return;
        }
        $userId = $request->user()->id;
        $files = (array) $request->files->get('files', []);   // not $request->file(): Laravel would cache the list before the joined files are added
        foreach ($ids as $i => $id) {
            $dir = $this->chunkDir($userId, (string) $id);
            $name = basename((string) $request->input("upload_names.$i", 'upload'));
            $total = (int) $request->input("upload_totals.$i");
            if (! is_dir($dir) || $total < 1 || count(glob($dir . '/*.part') ?: []) !== $total) {
                abort(422, 'The upload was not complete. Please try again.');
            }
            $path = $dir . '/joined.bin';
            $out = fopen($path, 'wb');
            for ($n = 0; $n < $total; $n++) {
                $in = fopen("$dir/$n.part", 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
            fclose($out);
            $files[$i] = new UploadedFile($path, $name, (string) $request->input("upload_types.$i") ?: null, null, true);
        }
        $request->files->set('files', $files);
        register_shutdown_function(function () use ($userId, $ids) {
            foreach ($ids as $id) {
                File::deleteDirectory($this->chunkDir($userId, (string) $id));
            }
        });
    }

    private function chunkDir(int $userId, string $uploadId): string
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{12,40}$/', $uploadId) === 1, 422);

        return storage_path("app/chunks/$userId/$uploadId");
    }

    /** One piece of a big file. Pieces wait in storage/app/chunks until the message is sent (abandoned ones are swept after a day). */
    public function chunk(Request $request, Order $order): JsonResponse
    {
        $this->authorize('chat', $order);
        $data = $request->validate([
            'upload' => ['required', 'string'],
            'index' => ['required', 'integer', 'min:0', 'max:2000'],
            'chunk' => ['required', 'file', 'max:2048'],
        ]);
        $dir = $this->chunkDir($request->user()->id, $data['upload']);
        File::ensureDirectoryExists($dir);
        $request->file('chunk')->move($dir, $data['index'] . '.part');

        if (random_int(1, 20) === 1) {
            foreach (glob(storage_path('app/chunks/*/*'), GLOB_ONLYDIR) ?: [] as $old) {
                if (filemtime($old) < time() - 86400) {
                    File::deleteDirectory($old);
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    /** Toggle a reaction on a message. */
    public function react(Request $request, Order $order, OrderMessage $message): JsonResponse
    {
        $this->authorize('chat', $order);
        abort_unless((int) $message->order_id === (int) $order->id && in_array($message->kind, ['text'], true), 404);
        $data = $request->validate(['emoji' => ['required', 'string', 'max:16']]);

        return response()->json($this->chat->react($message, $request->user(), $data['emoji']));
    }
}
