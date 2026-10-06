<?php

namespace App\Livewire\Usuarios;

use App\Livewire\Concerns\Paginado;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Gestión de Usuarios')]
class Index extends Component
{
    use Paginado;

    public function eliminar(int $id): void
    {
        $usuario = User::findOrFail($id);

        if ($usuario->is(auth()->user())) {
            $this->addError('lista', 'No podés eliminar tu propio usuario.');

            return;
        }

        // Si era el ultimo que administraba usuarios, no se borra.
        DB::transaction(function () use ($usuario) {
            $usuario->delete();

            Acceso::asegurarAdministrador('lista');
        });

        session()->flash('status', 'Usuario eliminado.');
    }

    public function render()
    {
        return view('livewire.usuarios.index', [
            'usuarios' => User::with('rol')->orderBy('name')->paginate(self::POR_PAGINA),
        ]);
    }
}
