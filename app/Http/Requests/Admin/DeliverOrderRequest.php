<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The "deliver work" form: at least one file, optional note. */
class DeliverOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $u = config('portal.uploads');

        return [
            'note' => ['nullable', 'string', 'max:3000'],
            'files' => ['required', 'array', 'min:1', 'max:' . $u['max_files']],
            'files.*' => ['file', 'max:' . $u['max_kb'], function ($attr, $file, $fail) use ($u) {
                if (in_array(strtolower($file->getClientOriginalExtension()), $u['blocked_extensions'], true)) {
                    $fail('This file type is not allowed. Put it in a .zip first.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Attach at least one file to deliver.',
            'files.*.max' => 'Each file can be up to ' . round(config('portal.uploads.max_kb') / 1024) . ' MB.',
            'files.*.uploaded' => 'A file could not be uploaded. It may be bigger than the server allows (' . ini_get('upload_max_filesize') . ').',
        ];
    }
}
