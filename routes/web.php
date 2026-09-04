<?php

use App\Livewire\Clientes;
use App\Livewire\Configuracion;
use App\Livewire\Cotizaciones;
use App\Livewire\Dashboard;
use App\Livewire\Fletes;
use App\Livewire\Insumos;
use App\Livewire\Prospectos;
use App\Livewire\Vendedores;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('prospectos', Prospectos\Index::class)->name('prospectos.index');
    Route::get('prospectos/create', Prospectos\Form::class)->name('prospectos.create');
    Route::get('prospectos/{contacto}/edit', Prospectos\Form::class)->name('prospectos.edit');

    Route::get('clientes', Clientes\Index::class)->name('clientes.index');
    Route::get('clientes/create', Clientes\Form::class)->name('clientes.create');
    Route::get('clientes/{contacto}/edit', Clientes\Form::class)->name('clientes.edit');

    // Listado estatico hasta que exista el modulo de cotizaciones.
    Route::get('cotizaciones', Cotizaciones\Index::class)->name('cotizaciones.index');
    Route::get('cotizaciones/create', Cotizaciones\Form::class)->name('cotizaciones.create');

    Route::get('configuracion', Configuracion\Index::class)->name('configuracion');
    Route::get('configuracion/ajustes', Configuracion\Ajustes::class)->name('configuracion.ajustes');
    Route::get('flete-insumos', Fletes\Index::class)->name('fletes.index');

    Route::get('insumos', Insumos\Index::class)->name('insumos.index');
    Route::get('insumos/{insumo}', Insumos\Detalle::class)->name('insumos.detalle');

    Route::get('vendedores', Vendedores\Index::class)->name('vendedores.index');
    Route::get('vendedores/create', Vendedores\Form::class)->name('vendedores.create');
    Route::get('vendedores/{vendedor}/edit', Vendedores\Form::class)->name('vendedores.edit');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
