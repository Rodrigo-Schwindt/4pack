<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable // implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'rol_id',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Primera pantalla de cada permiso, en el orden en que se prueban para
     * elegir a donde entra el usuario.
     */
    private const INICIOS = [
        Rol::DASHBOARD => 'dashboard',
        Rol::COTIZACIONES => 'cotizaciones.index',
        Rol::PROSPECTOS => 'prospectos.index',
        Rol::CLIENTES => 'clientes.index',
        Rol::ESTADISTICAS => 'estadisticas',
        Rol::COSTOS => 'configuracion',
        Rol::VENDEDORES => 'configuracion',
        Rol::USUARIOS => 'configuracion',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    /**
     * Si tiene alguno de los permisos. Un usuario inactivo o sin rol no puede nada.
     */
    public function puede(string ...$permisos): bool
    {
        if (! $this->activo || $this->rol === null) {
            return false;
        }

        foreach ($permisos as $permiso) {
            if ($this->rol->tiene($permiso)) {
                return true;
            }
        }

        return false;
    }

    /** A donde entra al iniciar sesion: la primera pantalla que tiene permitida. */
    public function inicio(): string
    {
        foreach (self::INICIOS as $permiso => $ruta) {
            if ($this->puede($permiso)) {
                return route($ruta, absolute: false);
            }
        }

        return route('settings.profile', absolute: false);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }
}
