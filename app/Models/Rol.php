<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    public const DASHBOARD = 'dashboard';

    public const ESTADISTICAS = 'estadisticas';

    public const PROSPECTOS = 'prospectos';

    public const CLIENTES = 'clientes';

    public const COTIZACIONES = 'cotizaciones';

    public const APROBAR_COTIZACIONES = 'aprobar_cotizaciones';

    public const COSTOS = 'costos';

    public const VENDEDORES = 'vendedores';

    public const USUARIOS = 'usuarios';

    /**
     * Lo que puede hacer un rol, cada permiso con su nombre y para que sirve,
     * en el orden en que se muestran.
     */
    public const PERMISOS = [
        self::DASHBOARD => ['Ver Dashboard', 'Kilos del período, alertas y prospectos nuevos'],
        self::ESTADISTICAS => ['Ver Estadísticas', 'Indicadores, gráficos y comisiones por vendedor'],
        self::PROSPECTOS => ['Gestionar Prospectos', 'Alta, edición y seguimiento de prospectos'],
        self::CLIENTES => ['Gestionar Clientes', 'Alta y edición de clientes, direcciones y productos'],
        self::COTIZACIONES => ['Gestionar Cotizaciones', 'Crear, editar y duplicar cotizaciones'],
        self::APROBAR_COTIZACIONES => ['Aprobar Cotizaciones', 'Aprobar cotizaciones y cambiarles el estado'],
        self::COSTOS => ['Configurar Costos', 'Insumos, fletes, operativos, variables y ajustes'],
        self::VENDEDORES => ['Gestionar Vendedores', 'Alta de vendedores y sus comisiones'],
        self::USUARIOS => ['Gestionar Usuarios y Roles', 'Usuarios del sistema, roles y permisos'],
    ];

    protected $fillable = ['nombre', 'descripcion', 'permisos'];

    protected function casts(): array
    {
        return ['permisos' => 'array'];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tiene(string $permiso): bool
    {
        return in_array($permiso, $this->permisos ?? [], true);
    }

    /**
     * Los permisos del rol en el orden del catalogo, con su nombre.
     *
     * @return array<string, string>
     */
    public function nombresDePermisos(): array
    {
        return collect(self::PERMISOS)
            ->filter(fn (array $permiso, string $clave) => $this->tiene($clave))
            ->map(fn (array $permiso) => $permiso[0])
            ->all();
    }
}
