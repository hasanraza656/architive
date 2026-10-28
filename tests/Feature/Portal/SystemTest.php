<?php

namespace Tests\Feature\Portal;

use App\Models\Role;
use App\Models\User;

class SystemTest extends PortalTestCase
{
    public function test_only_the_super_admin_can_open_the_system_page(): void
    {
        $role = Role::create(['name' => 'Everything', 'slug' => 'everything', 'permissions' => ['team.view', 'team.manage_roles']]);
        $staff = User::create(['role' => 'staff', 'role_id' => $role->id, 'first_name' => 'Sam', 'last_name' => 'S', 'email' => 's@example.test', 'password' => bcrypt('x'), 'is_active' => true]);

        $this->actingAs($staff)->get('/admin/system')->assertRedirect();
        $this->actingAs($staff)->post('/admin/system/migrate')->assertRedirect();
        $this->actingAs($this->customer())->get('/admin/system')->assertRedirect();

        $this->actingAs($this->admin())->get('/admin/system')->assertOk()->assertSee('Database updates')->assertSee('up to date');
    }

    public function test_migrate_and_clear_cache_report_back(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/system/migrate')->assertRedirect()->assertSessionHas('info');   // nothing pending in the test database
        $this->post('/admin/system/cache')->assertRedirect()->assertSessionHas('success');
    }
}
