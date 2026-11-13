<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class FuncaoController extends Controller
{
    /**
     * Exibe a tela de gerenciamento de funções (gerentes).
     */
    public function index()
    {
        \UspTheme::activeUrl('funcoes');

        $permission = Permission::findOrCreate('manager', 'senhaunica');
        $gerentes = User::permission($permission)->orderBy('name')->get();

        return view('funcoes.index', compact('gerentes'));
    }

    /**
     * Atribui a permissão de gerente (manager) ao usuário informado pelo codpes.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codpes' => ['required', 'integer', 'min:1'],
        ]);

        $codpes = (int) $validated['codpes'];
        $user = User::where('codpes', $codpes)->first()
            ?? User::findOrCreateFromReplicado($codpes);

        if (!$user) {
            return back()->with('alert-danger', 'Pessoa não encontrada no Replicado.');
        }

        $permission = Permission::findOrCreate('manager', 'senhaunica');
        $user->givePermissionTo($permission);

        return back()->with('alert-success', "Permissão de gerente atribuída a {$user->name}.");
    }

    /**
     * Remove a permissão de gerente (manager) do usuário.
     */
    public function destroy(User $user)
    {
        $permission = Permission::findOrCreate('manager', 'senhaunica');
        $user->revokePermissionTo($permission);

        return back()->with('alert-success', "Permissão de gerente removida de {$user->name}.");
    }
}
