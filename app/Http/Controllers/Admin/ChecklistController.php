<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * To-do checklists inside tasks and projects. Every answer is the whole list plus a version string, so the browser can
 * reconcile quickly and the polling request ("anything new since version X?") stays tiny.
 * Who may do what: seeing = may open the task / project; changing = may change the task's status / add tasks to the project.
 */
class ChecklistController extends Controller
{
    private const MAX_ITEMS = 200;

    public function index(Request $request, string $type, int $id): JsonResponse
    {
        $owner = $this->owner($type, $id);
        $this->authorize('view', $owner);

        return $this->state($type, $id, $request->query('v'));
    }

    /** Add one line (body) or several (bodies, from a pasted list). "after" = the line it goes below; empty = at the end. */
    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $owner = $this->owner($type, $id);
        $this->manage($owner);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:500'],
            'bodies' => ['nullable', 'array', 'max:50'],
            'bodies.*' => ['nullable', 'string', 'max:500'],
            'after' => ['nullable', 'integer'],
            'done' => ['nullable', 'boolean'],
        ]);

        $lines = collect($data['bodies'] ?? [$data['body'] ?? ''])->map(fn ($l) => trim((string) $l))->filter(fn ($l) => $l !== '')->values();
        if ($lines->isEmpty()) {
            return response()->json(['message' => 'Type something first.'], 422);
        }

        $ids = DB::transaction(function () use ($type, $id, $lines, $data, $request) {
            $existing = ChecklistItem::where('owner_type', $type)->where('owner_id', $id)->orderBy('position')->orderBy('id')->lockForUpdate()->pluck('id')->all();
            abort_if(count($existing) + $lines->count() > self::MAX_ITEMS, 422, 'A checklist can have up to ' . self::MAX_ITEMS . ' lines.');

            $done = ! empty($data['done']);
            $new = [];
            foreach ($lines as $line) {
                $new[] = ChecklistItem::create([
                    'owner_type' => $type, 'owner_id' => $id, 'body' => $line, 'position' => 0, 'created_by' => $request->user()->id,
                    'is_done' => $done, 'done_by' => $done ? $request->user()->id : null, 'done_at' => $done ? now() : null,
                ])->id;
            }
            // after = a line id (go below it), 0 (go to the very top), nothing (go to the end)
            $afterGiven = array_key_exists('after', $data) && $data['after'] !== null;
            $at = $afterGiven && (int) $data['after'] > 0 ? array_search((int) $data['after'], $existing, true) : false;
            $pos = $afterGiven && (int) $data['after'] === 0 ? 0 : ($at === false ? count($existing) : $at + 1);
            array_splice($existing, $pos, 0, $new);
            $this->renumber($existing);

            return $new;
        });

        return $this->state($type, $id, null, ['created' => $ids]);
    }

    /** Change the text and / or tick the line. Ticking records who did it and when. */
    public function update(Request $request, ChecklistItem $item): JsonResponse
    {
        $owner = $this->owner($item->owner_type, $item->owner_id);
        $this->manage($owner);
        $data = $request->validate(['body' => ['sometimes', 'string', 'max:500'], 'done' => ['sometimes', 'boolean']]);

        if (array_key_exists('body', $data)) {
            $text = trim($data['body']);
            abort_if($text === '', 422, 'A line cannot be empty. Use the bin to remove it.');
            $item->body = $text;
        }
        if (array_key_exists('done', $data) && (bool) $data['done'] !== $item->is_done) {
            $item->is_done = (bool) $data['done'];
            $item->done_by = $item->is_done ? $request->user()->id : null;
            $item->done_at = $item->is_done ? now() : null;
        }
        $item->save();

        return $this->state($item->owner_type, $item->owner_id);
    }

    public function destroy(ChecklistItem $item): JsonResponse
    {
        $owner = $this->owner($item->owner_type, $item->owner_id);
        $this->manage($owner);
        $type = $item->owner_type;
        $id = $item->owner_id;
        $item->delete();
        $this->renumber(ChecklistItem::where('owner_type', $type)->where('owner_id', $id)->orderBy('position')->orderBy('id')->pluck('id')->all());

        return $this->state($type, $id);
    }

    /** New order after dragging: ids = every line id, top to bottom. */
    public function reorder(Request $request, string $type, int $id): JsonResponse
    {
        $owner = $this->owner($type, $id);
        $this->manage($owner);
        $data = $request->validate(['ids' => ['required', 'array', 'max:' . self::MAX_ITEMS], 'ids.*' => ['integer']]);

        $current = ChecklistItem::where('owner_type', $type)->where('owner_id', $id)->orderBy('position')->orderBy('id')->pluck('id')->all();
        $given = array_values(array_unique(array_map('intval', $data['ids'])));
        // lines that someone added meanwhile stay at the end instead of being lost
        $this->renumber(array_merge(array_values(array_intersect($given, $current)), array_values(array_diff($current, $given))));

        return $this->state($type, $id);
    }

    /* ------------------------------------------------------------ helpers */

    private function owner(string $type, int $id): Model
    {
        return match ($type) {
            'task' => Task::with('project')->findOrFail($id),
            'project' => Project::findOrFail($id),
            default => abort(404),
        };
    }

    private function manage(Model $owner): void
    {
        $this->authorize($owner instanceof Task ? 'updateStatus' : 'addTask', $owner);
    }

    private function renumber(array $ids): void
    {
        foreach (array_values($ids) as $pos => $itemId) {
            ChecklistItem::whereKey($itemId)->update(['position' => $pos]);
        }
    }

    /** The whole list now. With ?v=<version> that is unchanged the answer is just {changed:false}. */
    private function state(string $type, int $id, ?string $since = null, array $extra = []): JsonResponse
    {
        $items = ChecklistItem::with('doneBy')->where('owner_type', $type)->where('owner_id', $id)->orderBy('position')->orderBy('id')->get()->map->present()->values();
        $version = md5($items->toJson());
        if ($since !== null && $since === $version) {
            return response()->json(['changed' => false, 'v' => $version]);
        }

        return response()->json($extra + [
            'changed' => true, 'v' => $version, 'items' => $items,
            'done' => $items->where('done', true)->count(), 'total' => $items->count(),
        ]);
    }
}
