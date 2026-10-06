<?php

use App\Livewire\Auth\Login;
use App\Livewire\Roles;
use App\Livewire\Usuarios;
use App\Models\Contacto;
use App\Models\Cotizacion;
use App\Models\Rol;
use App\Models\User;
use Livewire\Livewire;

function rol(string $nombre): Rol
{
    return Rol::where('nombre', $nombre)->firstOrFail();
}

beforeEach(function () {
    $this->admin = User::factory()->create(['name' => 'Roberto Garcia', 'username' => 'admin']);
    $this->actingAs($this->admin);
});

test('las pantallas de usuarios y roles responden', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/usuarios', '/usuarios/create', '/roles', '/roles/create']);

test('la migración deja los roles base con sus permisos', function () {
    expect(rol('Administrador')->permisos)->toBe(array_keys(Rol::PERMISOS))
        ->and(rol('Vendedor')->tiene(Rol::USUARIOS))->toBeFalse()
        ->and(rol('Supervisor')->tiene(Rol::APROBAR_COTIZACIONES))->toBeTrue();
});

test('el abm de usuarios da de alta, edita sin tocar la contraseña y elimina', function () {
    Livewire::test(Usuarios\Form::class)
        ->set('name', 'Juan Pérez')
        ->set('username', 'vend')
        ->set('email', 'vendedor@4pack.com')
        ->set('rol_id', (string) rol('Vendedor')->id)
        ->set('password', 'secreta123')
        ->set('password_confirmation', 'secreta123')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('usuarios.index'));

    $juan = User::firstWhere('username', 'vend');
    $clave = $juan->password;

    expect($juan->rol->nombre)->toBe('Vendedor')
        ->and($juan->activo)->toBeTrue()
        ->and(Hash::check('secreta123', $clave))->toBeTrue();

    // Sin contraseña nueva queda la que tenía.
    Livewire::test(Usuarios\Form::class, ['usuario' => $juan])
        ->set('name', 'Juan Pérez Gómez')
        ->set('activo', false)
        ->call('guardar')
        ->assertHasNoErrors();

    expect($juan->fresh())->name->toBe('Juan Pérez Gómez')->activo->toBeFalse()->password->toBe($clave);

    Livewire::test(Usuarios\Index::class)
        ->assertSee('Juan Pérez Gómez')
        ->assertSee('Inactivo')
        ->call('eliminar', $juan->id);

    expect(User::find($juan->id))->toBeNull();
});

test('el usuario y el email no se repiten, y la contraseña es obligatoria en el alta', function () {
    Livewire::test(Usuarios\Form::class)
        ->set('name', 'Otro')
        ->set('username', 'admin')
        ->set('email', $this->admin->email)
        ->set('rol_id', (string) rol('Vendedor')->id)
        ->call('guardar')
        ->assertHasErrors(['username' => 'unique', 'email' => 'unique', 'password' => 'required']);
});

test('nadie se puede desactivar ni eliminar a sí mismo', function () {
    Livewire::test(Usuarios\Form::class, ['usuario' => $this->admin])
        ->set('activo', false)
        ->call('guardar')
        ->assertHasErrors('activo');

    Livewire::test(Usuarios\Index::class)
        ->call('eliminar', $this->admin->id)
        ->assertHasErrors('lista');

    expect($this->admin->fresh()->activo)->toBeTrue();
});

test('siempre queda al menos un usuario activo que administre usuarios', function () {
    // Sacarle el permiso al único rol que lo tiene se deshace.
    Livewire::test(Roles\Form::class, ['rol' => rol('Administrador')])
        ->set('permisos', [Rol::DASHBOARD])
        ->call('guardar')
        ->assertHasErrors('permisos');

    expect(rol('Administrador')->tiene(Rol::USUARIOS))->toBeTrue();

    // Pasar al único administrador a Vendedor, también.
    Livewire::test(Usuarios\Form::class, ['usuario' => $this->admin])
        ->set('rol_id', (string) rol('Vendedor')->id)
        ->call('guardar')
        ->assertHasErrors('rol_id');

    expect($this->admin->fresh()->rol->nombre)->toBe('Administrador');

    // Con otro administrador activo, sí.
    User::factory()->create(['username' => 'otro.admin']);

    Livewire::test(Usuarios\Form::class, ['usuario' => $this->admin])
        ->set('rol_id', (string) rol('Vendedor')->id)
        ->call('guardar')
        ->assertHasNoErrors();
});

test('el abm de roles crea con permisos, exige al menos uno y no borra un rol en uso', function () {
    Livewire::test(Roles\Form::class)
        ->set('nombre', 'Costos')
        ->set('descripcion', 'Solo la configuración de costos')
        ->call('guardar')
        ->assertHasErrors(['permisos' => 'required']);

    Livewire::test(Roles\Form::class)
        ->set('nombre', 'Costos')
        ->set('descripcion', 'Solo la configuración de costos')
        ->set('permisos', [Rol::COSTOS, Rol::DASHBOARD])
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('roles.index'));

    // Se guardan en el orden del catálogo.
    expect(rol('Costos')->permisos)->toBe([Rol::DASHBOARD, Rol::COSTOS]);

    Livewire::test(Roles\Index::class)
        ->assertSee('Costos')
        ->call('eliminar', rol('Administrador')->id)
        ->assertHasErrors('lista')
        ->call('eliminar', rol('Costos')->id);

    expect(Rol::where('nombre', 'Costos')->exists())->toBeFalse()
        ->and(Rol::where('nombre', 'Administrador')->exists())->toBeTrue();
});

test('un vendedor solo entra a sus pantallas y el menú solo muestra esas', function () {
    $vendedor = User::factory()->create(['rol_id' => rol('Vendedor')->id]);
    $this->actingAs($vendedor);

    foreach (['/dashboard', '/prospectos', '/clientes', '/cotizaciones'] as $url) {
        $this->get($url)->assertOk();
    }

    foreach (['/estadisticas', '/configuracion', '/usuarios', '/roles', '/insumos', '/vendedores', '/flete-insumos'] as $url) {
        $this->get($url)->assertForbidden();
    }

    $this->get('/dashboard')
        ->assertSee('href="/cotizaciones"', false)
        ->assertDontSee('href="/estadisticas"', false)
        ->assertDontSee('href="/configuracion"', false);
});

test('las acciones de livewire también respetan el permiso de la pantalla', function () {
    $vendedor = User::factory()->create(['rol_id' => rol('Vendedor')->id]);
    $otro = User::factory()->create(['username' => 'victima']);

    // El mismo pedido que manda el navegador al tocar "eliminar" en /usuarios.
    preg_match('/wire:snapshot="([^"]+)"/', $this->get('/usuarios')->assertOk()->getContent(), $snapshot);
    $pedido = ['components' => [[
        'snapshot' => html_entity_decode($snapshot[1]),
        'updates' => new stdClass,
        'calls' => [['path' => '', 'method' => 'eliminar', 'params' => [$otro->id]]],
    ]]];
    $uri = app(\Livewire\Mechanisms\HandleRequests\HandleRequests::class)->getUpdateUri();

    $this->actingAs($vendedor)->postJson($uri, $pedido, ['X-Livewire' => '1'])->assertForbidden();

    expect(User::find($otro->id))->not->toBeNull();

    // Como administrador el mismo pedido pasa: el 403 era por el permiso.
    $this->actingAs($this->admin)->postJson($uri, $pedido, ['X-Livewire' => '1'])->assertOk();

    expect(User::find($otro->id))->toBeNull();
});

test('aprobar o cambiar el estado de una cotización pide su permiso', function () {
    $vendedor = User::factory()->create(['rol_id' => rol('Vendedor')->id]);
    $cliente = Contacto::create(['codigo' => Contacto::siguienteCodigo(), 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    $cotizacion = Cotizacion::create(['numero' => '000001/2026', 'fecha' => today(), 'contacto_id' => $cliente->id, 'estado' => Cotizacion::PENDIENTE, 'datos' => []]);

    $this->actingAs($vendedor);

    Livewire::test(App\Livewire\Cotizaciones\Index::class)
        ->call('cambiarEstado', $cotizacion->id)
        ->assertForbidden();

    expect($cotizacion->fresh()->estado)->toBe(Cotizacion::PENDIENTE);
});

test('un usuario inactivo no puede iniciar sesión y uno activo entra a su primera pantalla', function () {
    auth()->logout();

    User::factory()->create(['username' => 'dado.de.baja', 'password' => 'secreta123', 'activo' => false]);

    Livewire::test(Login::class)
        ->set('username', 'dado.de.baja')
        ->set('password', 'secreta123')
        ->call('login')
        ->assertHasErrors('username');

    expect(auth()->check())->toBeFalse();

    // Un rol sin dashboard entra por lo primero que tiene permitido.
    $costos = Rol::create(['nombre' => 'Costos', 'permisos' => [Rol::COSTOS]]);
    User::factory()->create(['username' => 'costos', 'password' => 'secreta123', 'rol_id' => $costos->id]);

    Livewire::test(Login::class)
        ->set('username', 'costos')
        ->set('password', 'secreta123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/configuracion');
});

test('a un usuario desactivado con la sesión abierta lo saca en el próximo clic', function () {
    $juan = User::factory()->create(['rol_id' => rol('Vendedor')->id]);
    $this->actingAs($juan);

    $juan->update(['activo' => false]);

    $this->get('/dashboard')->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});
