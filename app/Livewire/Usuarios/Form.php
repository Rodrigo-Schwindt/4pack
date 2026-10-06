<?php

namespace App\Livewire\Usuarios;

use App\Models\Rol;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class Form extends Component
{
    public ?User $usuario = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $rol_id = '';

    public bool $activo = true;

    /** Al editar, vacia deja la contraseña que tenia. */
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(?User $usuario = null): void
    {
        if (! $usuario?->exists) {
            return;
        }

        $this->usuario = $usuario;
        $this->name = $usuario->name;
        $this->username = $usuario->username;
        $this->email = $usuario->email;
        $this->rol_id = (string) $usuario->rol_id;
        $this->activo = $usuario->activo;
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($this->usuario)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->usuario)],
            'rol_id' => ['required', 'exists:roles,id'],
            'activo' => ['required', 'boolean'],
            'password' => [$this->usuario ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);

        if ($this->usuario?->is(auth()->user()) && ! $this->activo) {
            $this->addError('activo', 'No podés desactivar tu propio usuario.');

            return;
        }

        if ($datos['password'] === '' || $datos['password'] === null) {
            unset($datos['password']);
        }

        $nuevo = $this->usuario === null;

        // Si con el cambio no queda nadie que administre usuarios, se deshace.
        DB::transaction(function () use ($datos) {
            // Los usuarios los da de alta un administrador: no hace falta verificar el email.
            $usuario = $this->usuario ?? (new User)->forceFill(['email_verified_at' => now()]);
            $usuario->fill($datos)->save();

            Acceso::asegurarAdministrador('rol_id');
        });

        session()->flash('status', $nuevo ? 'Usuario creado.' : 'Usuario actualizado.');

        $this->redirectRoute('usuarios.index', navigate: true);
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'username' => 'usuario',
            'email' => 'email',
            'rol_id' => 'rol',
            'activo' => 'estado',
            'password' => 'contraseña',
        ];
    }

    public function render()
    {
        return view('livewire.usuarios.form', [
            'roles' => Rol::orderBy('nombre')->pluck('nombre', 'id'),
        ])->title($this->usuario ? 'Editar usuario' : 'Nuevo Usuario');
    }
}
