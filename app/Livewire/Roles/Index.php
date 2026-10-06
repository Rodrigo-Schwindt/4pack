<?php

namespace App\Livewire\Roles;

use App\Models\Rol;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Gestión de roles')]
class Index extends Component
{
    public function eliminar(int $id): void
    {
        $rol = Rol::withCount('usuarios')->findOrFail($id);

        // Un rol en uso dejaria a esos usuarios sin acceso a nada.
        if ($rol->usuarios_count > 0) {
            $this->addError('lista', sprintf('«%s» lo tienen %d %s: asignales otro rol antes de eliminarlo.', $rol->nombre, $rol->usuarios_count, $rol->usuarios_count === 1 ? 'usuario' : 'usuarios'));

            return;
        }

        $rol->delete();

        session()->flash('status', 'Rol eliminado.');
    }

    public function render()
    {
        return view('livewire.roles.index', [
            'roles' => Rol::withCount('usuarios')->orderBy('id')->get(),
        ]);
    }
}
