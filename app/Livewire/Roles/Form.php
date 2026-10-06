<?php

namespace App\Livewire\Roles;

use App\Models\Rol;
use App\Support\Acceso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class Form extends Component
{
    public ?Rol $rol = null;

    public string $nombre = '';

    public string $descripcion = '';

    /** @var list<string> claves de Rol::PERMISOS */
    public array $permisos = [];

    public function mount(?Rol $rol = null): void
    {
        if (! $rol?->exists) {
            return;
        }

        $this->rol = $rol;
        $this->nombre = $rol->nombre;
        $this->descripcion = (string) $rol->descripcion;
        $this->permisos = array_values($rol->permisos ?? []);
    }

    public function todos(): void
    {
        $this->permisos = array_keys(Rol::PERMISOS);
    }

    public function ninguno(): void
    {
        $this->permisos = [];
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('roles', 'nombre')->ignore($this->rol)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'permisos' => ['required', 'array', 'min:1'],
            'permisos.*' => [Rule::in(array_keys(Rol::PERMISOS))],
        ], ['permisos.required' => 'Elegí al menos un permiso.', 'permisos.min' => 'Elegí al menos un permiso.']);

        // Siempre en el orden del catalogo.
        $datos['permisos'] = array_values(array_intersect(array_keys(Rol::PERMISOS), $datos['permisos']));
        $datos['descripcion'] = trim((string) $datos['descripcion']) !== '' ? trim($datos['descripcion']) : null;

        // Sacarle "usuarios" al ultimo rol que lo tiene dejaria el sistema sin administrador.
        $nuevo = $this->rol === null;

        DB::transaction(function () use ($datos) {
            ($this->rol ?? new Rol)->fill($datos)->save();

            Acceso::asegurarAdministrador('permisos');
        });

        session()->flash('status', $nuevo ? 'Rol creado.' : 'Rol actualizado.');

        $this->redirectRoute('roles.index', navigate: true);
    }

    public function validationAttributes(): array
    {
        return ['nombre' => 'nombre', 'descripcion' => 'descripción', 'permisos' => 'permisos'];
    }

    public function render()
    {
        return view('livewire.roles.form', [
            'catalogo' => Rol::PERMISOS,
            'usuarios' => $this->rol?->usuarios()->count() ?? 0,
        ])->title($this->rol ? 'Editar rol' : 'Nuevo Rol');
    }
}
