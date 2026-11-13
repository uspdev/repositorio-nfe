<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FuncoesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'admin', 'guard_name' => 'senhaunica']);
        Permission::firstOrCreate(['name' => 'boss', 'guard_name' => 'senhaunica']);
        Permission::firstOrCreate(['name' => 'manager', 'guard_name' => 'senhaunica']);
        Permission::firstOrCreate(['name' => 'user', 'guard_name' => 'senhaunica']);
    }

    public function test_non_admin_cannot_access_funcoes_routes(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findByName('manager', 'senhaunica'));

        $this->actingAs($manager)->get(route('funcoes.index'))->assertForbidden();
        $this->actingAs($manager)->post(route('funcoes.store'), ['codpes' => 123456])->assertForbidden();
        $this->actingAs($manager)->delete(route('funcoes.destroy', $manager))->assertForbidden();
    }

    public function test_admin_can_view_funcoes_and_list_managers(): void
    {
        $admin = $this->createAdminUser();
        $manager = User::factory()->create(['name' => 'Maria Gerente', 'codpes' => 111222]);
        $manager->givePermissionTo(Permission::findByName('manager', 'senhaunica'));

        $this->actingAs($admin)
            ->get(route('funcoes.index'))
            ->assertOk()
            ->assertSee('Funções')
            ->assertSee('Gerentes')
            ->assertSee('Maria Gerente')
            ->assertSee('111222');
    }

    public function test_admin_can_assign_and_revoke_manager_permission(): void
    {
        $admin = $this->createAdminUser();
        $usuario = User::factory()->create(['name' => 'Joao Usuario', 'codpes' => 654321]);

        $this->actingAs($admin)
            ->from(route('funcoes.index'))
            ->post(route('funcoes.store'), ['codpes' => 654321])
            ->assertRedirect(route('funcoes.index'))
            ->assertSessionHas('alert-success');

        $this->assertTrue($usuario->fresh()->hasPermissionTo('manager', 'senhaunica'));

        $this->actingAs($admin)
            ->from(route('funcoes.index'))
            ->delete(route('funcoes.destroy', $usuario))
            ->assertRedirect(route('funcoes.index'))
            ->assertSessionHas('alert-success');

        $this->assertFalse($usuario->fresh()->hasPermissionTo('manager', 'senhaunica'));
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findByName('admin', 'senhaunica'));

        return $user;
    }
}
