<?php

namespace App\Http\Controllers\Admin\Team;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Roles: a name plus a set of permissions, generated from config/permissions.php. */
class RoleController extends Controller
{
    public function index()
    {
        return view('portal.admin.team.roles', ['roles' => Role::withCount('users')->orderBy('name')->get()]);
    }

    public function create(Request $request)
    {
        $preset = Permissions::presets()[$request->query('from')] ?? null;

        return view('portal.admin.team.role-form', [
            'role' => new Role(['color' => $preset['color'] ?? '#FFD60A', 'permissions' => $preset['permissions'] ?? []]),
            'presets' => Permissions::presets(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $role = Role::create($data + ['slug' => $this->slug($data['name'])]);

        return redirect()->route('admin.team.roles.index')->with('success', "Role “{$role->name}” was created.");
    }

    public function edit(Role $role)
    {
        return view('portal.admin.team.role-form', ['role' => $role, 'presets' => Permissions::presets()]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $role->update($this->validated($request, $role));

        return redirect()->route('admin.team.roles.index')->with('success', "Role “{$role->name}” was saved. The change applies to everyone with this role right away.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $count = $role->users()->count();
        if ($count > 0) {
            return back()->with('error', "“{$role->name}” still has $count " . Str::plural('person', $count) . '. Give them another role first, then delete it.');
        }
        $role->delete();

        return redirect()->route('admin.team.roles.index')->with('success', 'Role deleted.');
    }

    private function validated(Request $request, ?Role $role): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ], ['name.unique' => 'There is already a role with this name.']);

        return [
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#FFD60A',
            'permissions' => Permissions::clean($data['permissions'] ?? []),
        ];
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        for ($i = 2; Role::where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
