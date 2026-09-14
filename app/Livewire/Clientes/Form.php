<?php

namespace App\Livewire\Clientes;

use App\Livewire\Contactos\Formulario;
use App\Models\Contacto;
use App\Models\FleteZona;

class Form extends Formulario
{
    /** Solapa activa del detalle: 'datos' o 'productos'. */
    public string $solapa = 'datos';

    /**
     * Direcciones de entrega en pantalla. Se guardan junto con el cliente, asi
     * tambien se pueden cargar durante el alta, cuando todavia no tiene id.
     *
     * @var list<array{id: ?int, flete_zona_id: ?string, direccion: string, codigo_postal: string, observaciones: string}>
     */
    public array $direcciones = [];

    /** Indice de la direccion que se esta editando. */
    public ?int $editandoDireccion = null;

    /** Indice de la direccion que esta dando de alta una zona nueva. */
    public ?int $creandoZonaEn = null;

    public string $nuevaZona = '';

    protected function estado(): string
    {
        return Contacto::CLIENTE;
    }

    protected function ruta(): string
    {
        return 'clientes';
    }

    public function mount(?Contacto $contacto = null): void
    {
        parent::mount($contacto);

        $this->direcciones = $this->contacto
            ? $this->contacto->direcciones->map(fn ($direccion) => [
                'id' => $direccion->id,
                'flete_zona_id' => (string) $direccion->flete_zona_id,
                'direccion' => $direccion->direccion,
                'codigo_postal' => (string) $direccion->codigo_postal,
                'observaciones' => (string) $direccion->observaciones,
            ])->all()
            : [];
    }

    public function verSolapa(string $solapa): void
    {
        // La solapa de productos recien tiene sentido con el cliente ya creado.
        if ($solapa === 'productos' && ! $this->contacto) {
            return;
        }

        $this->solapa = $solapa;
    }

    public function nuevaDireccion(): void
    {
        $this->direcciones[] = ['id' => null, 'flete_zona_id' => '', 'direccion' => '', 'codigo_postal' => '', 'observaciones' => ''];

        $this->editandoDireccion = array_key_last($this->direcciones);
    }

    public function editarDireccion(int $indice): void
    {
        $this->editandoDireccion = isset($this->direcciones[$indice]) ? $indice : null;
    }

    public function listoDireccion(): void
    {
        if ($this->editandoDireccion === null) {
            return;
        }

        $this->validateOnly('direcciones.'.$this->editandoDireccion.'.direccion', $this->reglasDirecciones());

        $this->editandoDireccion = null;
    }

    /**
     * La zona sale del catalogo de flete, pero si todavia no esta se crea desde
     * aca: queda pendiente de precios y flete la sugiere al cargar una zona.
     */
    public function abrirZona(int $indice): void
    {
        $this->creandoZonaEn = isset($this->direcciones[$indice]) ? $indice : null;
        $this->nuevaZona = '';
        $this->resetValidation('nuevaZona');
    }

    public function cancelarZona(): void
    {
        $this->creandoZonaEn = null;
        $this->nuevaZona = '';
        $this->resetValidation('nuevaZona');
    }

    public function guardarZona(): void
    {
        if ($this->creandoZonaEn === null) {
            return;
        }

        $this->validate(
            ['nuevaZona' => ['required', 'string', 'max:255']],
            attributes: ['nuevaZona' => 'zona']
        );

        // Si ya existe no es un error: se elige la que esta.
        $zona = FleteZona::firstOrCreate(['nombre' => trim($this->nuevaZona)]);

        $this->direcciones[$this->creandoZonaEn]['flete_zona_id'] = (string) $zona->id;

        $this->cancelarZona();
    }

    public function quitarDireccion(int $indice): void
    {
        unset($this->direcciones[$indice]);

        $this->direcciones = array_values($this->direcciones);
        $this->editandoDireccion = null;
        $this->cancelarZona();
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    protected function reglas(): array
    {
        return array_merge(parent::reglas(), $this->reglasDirecciones());
    }

    /**
     * @return array<string, mixed>
     */
    protected function reglasDirecciones(): array
    {
        return [
            'direcciones' => ['array'],
            'direcciones.*.flete_zona_id' => ['nullable', 'exists:flete_zonas,id'],
            'direcciones.*.direccion' => ['required', 'string', 'max:255'],
            'direcciones.*.codigo_postal' => ['nullable', 'string', 'max:20'],
            'direcciones.*.observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function despuesDeGuardar(Contacto $contacto): void
    {
        $conservar = array_filter(array_column($this->direcciones, 'id'));

        $contacto->direcciones()->whereNotIn('id', $conservar ?: [0])->delete();

        foreach ($this->direcciones as $fila) {
            $datos = [
                'flete_zona_id' => $fila['flete_zona_id'] ?: null,
                'direccion' => trim($fila['direccion']),
                'codigo_postal' => $fila['codigo_postal'] !== '' ? $fila['codigo_postal'] : null,
                'observaciones' => trim($fila['observaciones'] ?? '') !== '' ? trim($fila['observaciones']) : null,
            ];

            if ($fila['id']) {
                $contacto->direcciones()->whereKey($fila['id'])->update($datos);

                continue;
            }

            $contacto->direcciones()->create($datos);
        }
    }

    public function validationAttributes(): array
    {
        return collect($this->direcciones)
            ->keys()
            ->flatMap(fn (int $indice) => [
                'direcciones.'.$indice.'.direccion' => 'dirección',
                'direcciones.'.$indice.'.flete_zona_id' => 'zona',
                'direcciones.'.$indice.'.codigo_postal' => 'código postal',
                'direcciones.'.$indice.'.observaciones' => 'observaciones',
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.clientes.form', array_merge($this->datosVista(), [
            'zonas' => FleteZona::orderBy('nombre')->get(['id', 'nombre']),
            // Placeholder hasta que exista el modulo de productos.
            'productos' => collect(range(1, 8))->map(fn (int $i) => [
                'id' => $i,
                'nombre' => 'Flowpack Bilaminado impreso Carrefour Caseras x 700g',
            ]),
        ]))->title($this->contacto ? 'Cliente '.$this->codigo : 'Nuevo Cliente');
    }
}
