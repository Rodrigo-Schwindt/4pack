<?php

namespace App\Livewire\Contactos;

use App\Models\Contacto;
use App\Models\Rubro;
use App\Models\Tipo;
use App\Models\Vendedor;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Alta y edicion compartidas por prospectos y clientes, que son el mismo
 * contacto en distinto estado. Cada subclase define su estado y sus rutas.
 */
#[Layout('components.layouts.panel')]
abstract class Formulario extends Component
{
    public ?Contacto $contacto = null;

    public string $codigo = '';

    public string $nombre_comercial = '';

    public string $razon_social = '';

    public string $cuit = '';

    public string $email = '';

    public string $direccion = '';

    public string $provincia = '';

    public string $localidad = '';

    public string $telefono = '';

    public string $celular = '';

    public string $pagina_web = '';

    public ?int $rubro_id = null;

    public ?int $tipo_id = null;

    public ?int $vendedor_id = null;

    public string $observaciones = '';

    /** Catalogo que se esta dando de alta al vuelo: 'rubros', 'tipos' o null. */
    public ?string $creando = null;

    public string $nuevoCatalogo = '';

    /** Contacto::PROSPECTO o Contacto::CLIENTE */
    abstract protected function estado(): string;

    /** Prefijo de rutas: 'prospectos' o 'clientes' */
    abstract protected function ruta(): string;

    public function mount(?Contacto $contacto = null): void
    {
        if ($contacto?->exists) {
            abort_unless($contacto->estado === $this->estado(), 404);

            $this->contacto = $contacto;
            $this->codigo = $contacto->codigo;

            foreach ($this->campos() as $campo) {
                $this->$campo = $contacto->$campo ?? ($this->esNumerico($campo) ? null : '');
            }

            return;
        }

        $this->codigo = Contacto::siguienteCodigo();
    }

    public function guardar(): void
    {
        // Las subclases pueden validar mas cosas (direcciones, por ejemplo);
        // al contacto solo le llegan sus propios campos.
        $datos = Arr::only($this->validate($this->reglas()), $this->campos());

        if ($this->contacto) {
            $this->contacto->update($datos);

            $this->despuesDeGuardar($this->contacto);

            session()->flash('status', 'Cambios guardados.');

            $this->redirectRoute($this->ruta().'.edit', $this->contacto, navigate: true);

            return;
        }

        $datos['codigo'] = Contacto::siguienteCodigo();
        $datos['estado'] = $this->estado();

        $contacto = Contacto::create($datos);

        $contacto->actividades()->create([
            'fecha' => now(),
            'descripcion' => 'Se creó el '.$this->estado(),
            'autor' => $contacto->vendedor?->nombre ?? auth()->user()->name,
        ]);

        $this->despuesDeGuardar($contacto);

        session()->flash('status', ucfirst($this->estado()).' creado.');

        $this->redirectRoute($this->ruta().'.edit', $contacto, navigate: true);
    }

    /**
     * Gancho para lo que cada subclase guarde junto al contacto.
     */
    protected function despuesDeGuardar(Contacto $contacto): void
    {
        //
    }

    /**
     * Alta al vuelo de rubro / tipo sin salir del formulario.
     */
    public function abrirCatalogo(string $catalogo): void
    {
        $this->creando = $catalogo;
        $this->nuevoCatalogo = '';
        $this->resetValidation('nuevoCatalogo');
    }

    public function cancelarCatalogo(): void
    {
        $this->creando = null;
        $this->nuevoCatalogo = '';
        $this->resetValidation('nuevoCatalogo');
    }

    public function guardarCatalogo(): void
    {
        $catalogo = $this->creando;

        abort_unless(isset($this->catalogos()[$catalogo]), 404);

        $this->validate(
            ['nuevoCatalogo' => ['required', 'string', 'max:255', Rule::unique($catalogo, 'nombre')]],
            attributes: ['nuevoCatalogo' => 'nombre']
        );

        $modelo = $this->catalogos()[$catalogo];
        $creado = $modelo::create(['nombre' => trim($this->nuevoCatalogo)]);

        // Queda seleccionada la opcion recien creada.
        if ($catalogo === 'rubros') {
            $this->rubro_id = $creado->id;
        } else {
            $this->tipo_id = $creado->id;
        }

        $this->cancelarCatalogo();
    }

    /**
     * @return array<string, class-string>
     */
    protected function catalogos(): array
    {
        return ['rubros' => Rubro::class, 'tipos' => Tipo::class];
    }

    /**
     * @return list<string>
     */
    protected function campos(): array
    {
        return [
            'nombre_comercial', 'razon_social', 'cuit', 'email', 'direccion', 'provincia',
            'localidad', 'telefono', 'celular', 'pagina_web', 'rubro_id', 'tipo_id',
            'vendedor_id', 'observaciones',
        ];
    }

    protected function esNumerico(string $campo): bool
    {
        return in_array($campo, ['rubro_id', 'tipo_id', 'vendedor_id'], true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function reglas(): array
    {
        return [
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'cuit' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            'localidad' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:50'],
            'pagina_web' => ['nullable', 'string', 'max:255'],
            'rubro_id' => ['nullable', 'exists:rubros,id'],
            'tipo_id' => ['nullable', 'exists:tipos,id'],
            'vendedor_id' => ['nullable', 'exists:vendedores,id'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function datosVista(): array
    {
        return [
            'provincias' => Contacto::PROVINCIAS,
            'rubros' => Rubro::orderBy('nombre')->get(['id', 'nombre']),
            'tipos' => Tipo::orderBy('nombre')->get(['id', 'nombre']),
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre']),
            'actividades' => $this->contacto
                ? $this->contacto->actividades()->get()->map(fn ($actividad) => [
                    'id' => $actividad->id,
                    'fecha' => $actividad->fecha->format('d/m/Y'),
                    'descripcion' => $actividad->descripcion,
                    'autor' => $actividad->autor,
                ])
                : collect(),
        ];
    }
}
