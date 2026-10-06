<?php

use App\Livewire\Clientes;
use App\Livewire\Configuracion;
use App\Livewire\Cotizaciones;
use App\Livewire\Dashboard;
use App\Livewire\Entregas;
use App\Livewire\Estadisticas;
use App\Livewire\Fletes;
use App\Livewire\Insumos;
use App\Livewire\Operativos;
use App\Livewire\Prospectos;
use App\Livewire\Roles;
use App\Livewire\Usuarios;
use App\Livewire\VariablesCostos;
use App\Livewire\Vendedores;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect(auth()->check() ? auth()->user()->inicio() : route('login'));
})->name('home');

// Cada pantalla pide su permiso (ver App\Models\Rol::PERMISOS).
Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard')->middleware('permiso:dashboard');
    Route::get('estadisticas', Estadisticas::class)->name('estadisticas')->middleware('permiso:estadisticas');

    Route::middleware('permiso:prospectos')->group(function () {
        Route::get('prospectos', Prospectos\Index::class)->name('prospectos.index');
        Route::get('prospectos/create', Prospectos\Form::class)->name('prospectos.create');
        Route::get('prospectos/{contacto}/edit', Prospectos\Form::class)->name('prospectos.edit');
    });

    Route::middleware('permiso:clientes')->group(function () {
        Route::get('clientes', Clientes\Index::class)->name('clientes.index');
        Route::get('clientes/create', Clientes\Form::class)->name('clientes.create');
        Route::get('clientes/{contacto}/edit', Clientes\Form::class)->name('clientes.edit');
    });

    Route::middleware('permiso:cotizaciones')->group(function () {
        Route::get('cotizaciones', Cotizaciones\Index::class)->name('cotizaciones.index');
        Route::get('cotizaciones/create', Cotizaciones\Form::class)->name('cotizaciones.create');
        Route::get('cotizaciones/{guardada}/edit', Cotizaciones\Form::class)->name('cotizaciones.edit');
    });

    // Configuracion muestra solo las tarjetas de lo que el usuario puede administrar.
    Route::get('configuracion', Configuracion\Index::class)->name('configuracion')->middleware('permiso:costos,vendedores,usuarios');

    Route::middleware('permiso:costos')->group(function () {
        Route::get('configuracion/ajustes', Configuracion\Ajustes::class)->name('configuracion.ajustes');
        Route::get('flete-insumos', Fletes\Index::class)->name('fletes.index');
        Route::get('operativos', Operativos\Index::class)->name('operativos.index');
        Route::get('variables-costos', VariablesCostos\Index::class)->name('variables-costos.index');
        Route::get('insumos', Insumos\Index::class)->name('insumos.index');
        Route::get('insumos/{insumo}', Insumos\Detalle::class)->name('insumos.detalle');
    });

    Route::middleware('permiso:vendedores')->group(function () {
        Route::get('vendedores', Vendedores\Index::class)->name('vendedores.index');
        Route::get('vendedores/create', Vendedores\Form::class)->name('vendedores.create');
        Route::get('vendedores/{vendedor}/edit', Vendedores\Form::class)->name('vendedores.edit');

        // Las entregas de todos los pedidos, con la comision de cada una.
        Route::get('entregas', Entregas\Index::class)->name('entregas.index');
    });

    Route::middleware('permiso:usuarios')->group(function () {
        Route::get('usuarios', Usuarios\Index::class)->name('usuarios.index');
        Route::get('usuarios/create', Usuarios\Form::class)->name('usuarios.create');
        Route::get('usuarios/{usuario}/edit', Usuarios\Form::class)->name('usuarios.edit');

        Route::get('roles', Roles\Index::class)->name('roles.index');
        Route::get('roles/create', Roles\Form::class)->name('roles.create');
        Route::get('roles/{rol}/edit', Roles\Form::class)->name('roles.edit');
    });

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
