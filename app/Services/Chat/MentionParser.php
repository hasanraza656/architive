<?php

namespace App\Services\Chat;

use App\Models\Order;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Tags inside a message body. The composer writes them as tokens:
 *   @[Aisha Khan](u:12)   a person in this chat
 *   #[Fix the facade](t:5)  a task            #[Riverside](p:3)  a project
 * parse() checks every token (the person must be in the chat, the task/project must be visible to the sender),
 * rewrites the visible label from the database (so labels cannot be faked) and returns the clean body plus the tags.
 */
class MentionParser
{
    public const PATTERN = '/([@#])\[([^\]\n]{1,160})\]\((u|t|p):(\d+)\)/u';

    /** @return array{0:string,1:array<int,array{type:string,id:int}>} */
    public function parse(string $body, Order $order, User $sender, array $participantIds): array
    {
        $found = [];

        $clean = preg_replace_callback(self::PATTERN, function (array $m) use (&$found, $sender, $participantIds) {
            [$all, $mark, $label, $code, $id] = $m;
            $id = (int) $id;

            if ($code === 'u' && $mark === '@') {
                $user = in_array($id, $participantIds, true) ? User::find($id) : null;
                if (! $user) {
                    return '@' . $this->plain($label);
                }
                $found["user:$id"] = ['type' => 'user', 'id' => $id];

                return '@[' . $this->plain($user->name ?: $user->email) . '](u:' . $id . ')';
            }
            if ($code === 't' && $mark === '#') {
                $task = Task::find($id);
                if (! $task || ! $sender->can('view', $task)) {
                    return '#' . $this->plain($label);
                }
                $found["task:$id"] = ['type' => 'task', 'id' => $id];

                return '#[' . $this->plain($task->title) . '](t:' . $id . ')';
            }
            if ($code === 'p' && $mark === '#') {
                $project = Project::find($id);
                if (! $project || ! $sender->can('view', $project)) {
                    return '#' . $this->plain($label);
                }
                $found["project:$id"] = ['type' => 'project', 'id' => $id];

                return '#[' . $this->plain($project->name) . '](p:' . $id . ')';
            }

            return $all;
        }, $body);

        return [$clean ?? $body, array_values($found)];
    }

    /** Strips characters that would break the token syntax and shortens long names. */
    private function plain(string $s): string
    {
        return Str::limit(trim(str_replace(['[', ']', '(', ')', "\n"], ' ', $s)), 120, '…');
    }
}
