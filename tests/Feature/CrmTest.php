<?php

use App\Livewire\Clientes;
use App\Livewire\Prospectos;
use App\Livewire\Vendedores;
use App\Models\Contacto;
use App\Models\Rubro;
use App\Models\Tipo;
use App\Models\User;
use App\Models\Vendedor;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Administrador']));
});

/**
 * El formulario de cotizacion no muestra nada hasta que hay un cliente elegido.
 */
function cotizacionConCliente(string $razonSocial = 'Arcor')
{
    $cliente = Contacto::create([
        'codigo' => Contacto::siguienteCodigo(),
        'estado' => Contacto::CLIENTE,
        'razon_social' => $razonSocial,
    ]);

    return Livewire::test(App\Livewire\Cotizaciones\Form::class)->set('cliente_id', $cliente->id);
}

test('las vistas del panel responden', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/dashboard', '/prospectos', '/clientes', '/cotizaciones', '/cotizaciones/create', '/configuracion', '/vendedores']);

test('las vistas del panel exigen sesión', function (string $url) {
    auth()->logout();

    $this->get($url)->assertRedirect('/login');
})->with(['/dashboard', '/prospectos', '/clientes', '/cotizaciones', '/cotizaciones/create', '/configuracion', '/vendedores']);

test('el listado de prospectos deja filtrar por vendedor', function () {
    $ariel = Vendedor::create(['nombre' => 'Ariel', 'comision' => 2, 'activo' => true]);
    $carlos = Vendedor::create(['nombre' => 'Carlos', 'comision' => 1, 'activo' => true]);

    Contacto::create(['codigo' => '000445', 'estado' => Contacto::PROSPECTO, 'razon_social' => 'Alican', 'vendedor_id' => $ariel->id]);
    Contacto::create(['codigo' => '000446', 'estado' => Contacto::PROSPECTO, 'razon_social' => 'Cabrales', 'vendedor_id' => $carlos->id]);
    Contacto::create(['codigo' => '000447', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor', 'vendedor_id' => $ariel->id]);

    Livewire::test(Prospectos\Index::class)
        ->assertSee('Alican')
        ->assertSee('Cabrales')
        ->assertDontSee('Arcor')
        ->call('filtrar', $ariel->id)
        ->assertSee('Alican')
        ->assertDontSee('Cabrales');
});

test('crear un prospecto le asigna código correlativo y registra la actividad', function () {
    Livewire::test(Prospectos\Form::class)
        ->set('razon_social', 'Alican')
        ->set('localidad', 'Lomas de Zamora')
        ->call('guardar')
        ->assertHasNoErrors();

    $contacto = Contacto::firstWhere('razon_social', 'Alican');

    expect($contacto->codigo)->toBe('000445')
        ->and($contacto->estado)->toBe(Contacto::PROSPECTO);

    expect($contacto->actividades()->first())
        ->descripcion->toBe('Se creó el prospecto')
        ->autor->toBe('Administrador');
});

test('la razón social es obligatoria', function () {
    Livewire::test(Prospectos\Form::class)
        ->set('razon_social', '')
        ->call('guardar')
        ->assertHasErrors(['razon_social' => 'required']);
});

test('el formulario de prospectos no abre un cliente', function () {
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);

    $this->get(route('prospectos.edit', $cliente))->assertNotFound();
});

test('se puede dar de alta un rubro sin salir del formulario', function () {
    Livewire::test(Prospectos\Form::class)
        ->call('abrirCatalogo', 'rubros')
        ->set('nuevoCatalogo', 'Alimenticio')
        ->call('guardarCatalogo')
        ->assertHasNoErrors()
        ->assertSet('creando', null)
        ->assertSet('rubro_id', Rubro::firstWhere('nombre', 'Alimenticio')->id);
});

test('el catálogo no admite nombres repetidos', function () {
    Tipo::create(['nombre' => 'Distribuidor/mayorista']);

    Livewire::test(Prospectos\Form::class)
        ->call('abrirCatalogo', 'tipos')
        ->set('nuevoCatalogo', 'Distribuidor/mayorista')
        ->call('guardarCatalogo')
        ->assertHasErrors(['nuevoCatalogo' => 'unique']);
});

test('editar un cliente guarda los cambios y conserva el código', function () {
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);

    Livewire::test(Clientes\Form::class, ['contacto' => $cliente])
        ->assertSet('codigo', '000445')
        ->set('razon_social', 'Arcor S.A.')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($cliente->fresh())->razon_social->toBe('Arcor S.A.')->codigo->toBe('000445');
});

test('la solapa de productos recién se habilita con el cliente creado', function () {
    Livewire::test(Clientes\Form::class)
        ->call('verSolapa', 'productos')
        ->assertSet('solapa', 'datos');
});

test('eliminar un prospecto lo borra junto con su actividad', function () {
    $prospecto = Contacto::create(['codigo' => '000445', 'estado' => Contacto::PROSPECTO, 'razon_social' => 'Alican']);
    $prospecto->actividades()->create(['fecha' => now(), 'descripcion' => 'Se creó el prospecto']);

    Livewire::test(Prospectos\Index::class)->call('eliminar', $prospecto->id);

    expect(Contacto::count())->toBe(0)
        ->and($prospecto->actividades()->count())->toBe(0);
});

test('el abm de vendedores da de alta, edita y elimina', function () {
    Livewire::test(Vendedores\Form::class)
        ->set('nombre', 'Ariel')
        ->set('comision', '2.5')
        ->set('activo', true)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('vendedores.index'));

    $vendedor = Vendedor::firstWhere('nombre', 'Ariel');

    expect((float) $vendedor->comision)->toBe(2.5);

    Livewire::test(Vendedores\Form::class, ['vendedor' => $vendedor])
        ->set('nombre', 'Ariel Gómez')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($vendedor->fresh()->nombre)->toBe('Ariel Gómez');

    Livewire::test(Vendedores\Index::class)->call('eliminar', $vendedor->id);

    expect(Vendedor::count())->toBe(0);
});

test('la comisión del vendedor va de 0 a 100', function () {
    Livewire::test(Vendedores\Form::class)
        ->set('nombre', 'Ariel')
        ->set('comision', '120')
        ->call('guardar')
        ->assertHasErrors(['comision' => 'max']);
});

test('el buscador de cotizaciones filtra la maqueta', function () {
    Livewire::test(App\Livewire\Cotizaciones\Index::class)
        ->assertSee('002014/2026')
        ->set('busqueda', '002007')
        ->assertSee('002007/2026')
        ->assertDontSee('002014/2026');
});

test('el alta de cotizaciones propone el siguiente número del año', function () {
    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->assertSet('numero', '002015/'.now()->year)
        ->assertSet('fecha', now()->format('Y-m-d'));
});

test('sin tipo de producto las solapas quedan trabadas', function () {
    cotizacionConCliente()
        ->assertSet('solapa', 'datos')
        ->call('verSolapa', 'costos')
        ->assertSet('solapa', 'datos')
        ->set('tipo_producto', 'bobinas')
        ->call('verSolapa', 'costos')
        ->assertSet('solapa', 'costos');
});

test('cambiar el tipo de producto vuelve a la solapa de datos', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->call('verSolapa', 'entrega')
        ->set('tipo_producto', 'confeccion-pouch')
        ->assertSet('solapa', 'datos');
});

test('el alta de cotizaciones lista los clientes y los vendedores', function () {
    Vendedor::create(['nombre' => 'Ariel', 'comision' => 2, 'activo' => true]);
    Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    Contacto::create(['codigo' => '000446', 'estado' => Contacto::PROSPECTO, 'razon_social' => 'Alican']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->assertSee('Arcor')
        ->assertSee('Ariel')
        ->assertDontSee('Alican');
});

test('elegir bobinas despliega sus secciones', function () {
    cotizacionConCliente()
        ->assertDontSee('Forma de entrega')
        ->set('tipo_producto', 'bobinas')
        ->assertSee('Módulos Desarrollo (cm)')
        ->assertSee('Forma de entrega')
        ->assertSee('Datos técnicos')
        ->assertSee('Condiciones de pago')
        ->assertSee('Duplicar Cotización');
});

test('la forma de entrega suma y quita filas', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.cantidad', '67000')
        ->assertCount('entregas', 2)
        ->call('agregarEntrega')
        ->assertCount('entregas', 3)
        ->assertSee('3° entrega')
        ->call('quitarEntrega', 0)
        ->assertCount('entregas', 2)
        ->call('quitarEntrega', 0)
        ->call('quitarEntrega', 0)
        ->assertCount('entregas', 1);
});

test('la solapa de costos de bobinas muestra las tablas calculadas', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->call('verSolapa', 'costos')
        ->assertSee('Proveedores')
        ->assertSee('Impresión y Reprint')
        ->assertSee('Laminación y solventes')
        ->assertSee('Refilado y Material Scrap')
        ->assertSee('Otros costos')
        ->assertSee('Rentabilidad, financiado y costo bruto')
        ->assertSee('Costo final')
        // El formulario de carga queda en su propia solapa.
        ->assertDontSee('Módulos Desarrollo (cm)');
});

test('la solapa de cotización arma el texto y sigue las entregas cargadas', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->call('verSolapa', 'cotizacion')
        ->assertSee('Cotización 1')
        ->assertSee('Anchos de bobina')
        ->assertSee('Condiciones de venta')
        ->assertSee('Entrega 1')
        ->assertSee('Entrega 2')
        ->assertDontSee('Entrega 3')
        ->call('agregarEntrega')
        ->assertSee('Entrega 3');
});

test('la solapa de orden de compra cambia el botón principal y lista lo cotizado', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->assertSee('Descargar PDF')
        ->call('verSolapa', 'orden-de-compra')
        ->assertSee('Enviar pedido')
        ->assertDontSee('Descargar PDF')
        ->assertSee('Adjuntar OC')
        ->assertSee('Precio unitario')
        ->assertSee('USD 862,50');
});

test('la orden de compra pide una fecha de entrega por cada entrega cargada', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->call('verSolapa', 'orden-de-compra')
        ->assertSeeHtml('wire:model="entregas.0.fecha_entrega"')
        ->assertSeeHtml('wire:model="entregas.1.fecha_entrega"')
        ->assertDontSeeHtml('wire:model="entregas.2.fecha_entrega"')
        ->call('agregarEntrega')
        ->assertSeeHtml('wire:model="entregas.2.fecha_entrega"');
});

test('la solapa de entrega separa lo pactado de lo entregado', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->call('verSolapa', 'entrega')
        ->assertSee('Enviar pedido')
        ->assertSee('Cantidad a entregar')
        ->assertSee('Cantidad entregada')
        ->assertSee('Código postal')
        ->assertSee('Conclusiones')
        ->assertSee('Cumplimiento de la entrega')
        // La fecha pactada de la OC y la real de la entrega son campos distintos.
        ->assertSeeHtml('wire:model="entregas.0.fecha_real"')
        ->assertDontSeeHtml('wire:model="entregas.0.fecha_entrega"');
});

test('las cuatro solapas de bobinas responden', function (string $solapa) {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->call('verSolapa', $solapa)
        ->assertSet('solapa', $solapa)
        ->assertOk();
})->with(['costos', 'cotizacion', 'orden-de-compra', 'entrega']);

test('la categoría de la cotización es A, B, C u OTRO', function () {
    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->assertSeeHtml('<option value="A">A</option>')
        ->assertSeeHtml('<option value="B">B</option>')
        ->assertSeeHtml('<option value="C">C</option>')
        ->assertSeeHtml('<option value="OTRO">OTRO</option>')
        ->assertDontSeeHtml('<option value="D">D</option>');
});

test('los ajustes se normalizan a dos decimales', function () {
    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->assertSet('ajuste_categoria', '0.00')
        ->assertSet('ajuste_vendedor', '0.00')
        ->set('ajuste_categoria', '7.5')
        ->assertSet('ajuste_categoria', '7.50')
        ->set('ajuste_vendedor', '3')
        ->assertSet('ajuste_vendedor', '3.00')
        ->set('ajuste_categoria', '')
        ->assertSet('ajuste_categoria', '0.00');
});

test('el cliente y el vendedor salen de los cargados en el sistema', function () {
    $vendedor = Vendedor::create(['nombre' => 'Ariel', 'comision' => 2, 'activo' => true]);
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->assertSeeHtml('<option value="'.$cliente->id.'">Arcor</option>')
        ->assertSeeHtml('<option value="'.$vendedor->id.'">Ariel</option>');
});

test('elegir un cliente y un vendedor guarda el id, no el nombre', function () {
    $vendedor = Vendedor::create(['nombre' => 'Ariel', 'comision' => 2, 'activo' => true]);
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->set('vendedor_id', $vendedor->id)
        ->assertSet('cliente_id', $cliente->id)
        ->assertSet('vendedor_id', $vendedor->id);
});

test('sin cliente no se puede elegir el tipo de producto', function () {
    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->assertSee('Elegí primero el cliente')
        ->set('tipo_producto', 'bobinas')
        // El tipo queda seteado pero los campos no se muestran ni se destraban las solapas.
        ->assertDontSee('Módulos Desarrollo (cm)')
        ->call('verSolapa', 'costos')
        ->assertSet('solapa', 'datos');
});

test('elegir el cliente habilita los campos del tipo de producto', function () {
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->assertDontSee('Elegí primero el cliente')
        ->set('tipo_producto', 'bobinas')
        ->assertSee('Módulos Desarrollo (cm)')
        ->call('verSolapa', 'costos')
        ->assertSet('solapa', 'costos');
});

test('cambiar de cliente vuelve a empezar', function () {
    $arcor = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    $bagley = Contacto::create(['codigo' => '000446', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Bagley']);
    $producto = App\Models\ContactoProducto::create(['contacto_id' => $arcor->id, 'nombre' => 'Flowpack x 700g']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $arcor->id)
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.producto_id', $producto->id)
        ->call('verSolapa', 'costos')
        ->set('cliente_id', $bagley->id)
        ->assertSet('tipo_producto', '')
        ->assertSet('bobinas.producto_id', null)
        ->assertSet('solapa', 'datos');
});

test('el desarrollo toma las mangas cargadas en ajustes y permite sumar una', function () {
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    App\Models\Ajuste::create(['grupo' => 'mangas', 'valor' => 35]);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->set('tipo_producto', 'bobinas')
        ->assertSeeHtml('<option value="35">35</option>')
        ->call('abrirAlta', 'mangas')
        ->set('nuevoValor', '62')
        ->call('guardarAlta')
        ->assertHasNoErrors()
        ->assertSet('creando', null)
        ->assertSet('bobinas.desarrollo', '62');

    expect(App\Models\Ajuste::opciones('mangas')->all())->toBe([35.0, 62.0]);
});

test('cargar una manga que ya existe la elige en vez de fallar', function () {
    App\Models\Ajuste::create(['grupo' => 'mangas', 'valor' => 35]);

    cotizacionConCliente()
        ->call('abrirAlta', 'mangas')
        ->set('nuevoValor', '35')
        ->call('guardarAlta')
        ->assertHasNoErrors()
        ->assertSet('creando', null)
        ->assertSet('bobinas.desarrollo', '35');

    expect(App\Models\Ajuste::delGrupo('mangas')->count())->toBe(1);
});

test('el producto se carga y se borra desde la cotización, y es solo del cliente', function () {
    $arcor = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    $bagley = Contacto::create(['codigo' => '000446', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Bagley']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $arcor->id)
        ->set('tipo_producto', 'bobinas')
        ->call('abrirAlta', 'producto')
        ->set('nuevoValor', 'Flowpack x 700g')
        ->call('guardarAlta')
        ->assertHasNoErrors()
        ->assertSee('Flowpack x 700g');

    $producto = App\Models\ContactoProducto::firstWhere('nombre', 'Flowpack x 700g');

    expect($producto->contacto_id)->toBe($arcor->id);

    // El otro cliente no ve el producto.
    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $bagley->id)
        ->set('tipo_producto', 'bobinas')
        ->assertDontSee('Flowpack x 700g');

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $arcor->id)
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.producto_id', $producto->id)
        ->call('eliminarProducto')
        ->assertSet('bobinas.producto_id', null);

    expect(App\Models\ContactoProducto::count())->toBe(0);
});

test('cargar un producto que el cliente ya tiene lo elige en vez de fallar', function () {
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    $producto = App\Models\ContactoProducto::create(['contacto_id' => $cliente->id, 'nombre' => 'Flowpack x 700g']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->call('abrirAlta', 'producto')
        ->set('nuevoValor', 'Flowpack x 700g')
        ->call('guardarAlta')
        ->assertHasNoErrors()
        ->assertSet('creando', null)
        ->assertSet('bobinas.producto_id', $producto->id);

    expect(App\Models\ContactoProducto::count())->toBe(1);
});

test('los productos ya cargados aparecen al elegir el cliente', function () {
    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    $producto = App\Models\ContactoProducto::create(['contacto_id' => $cliente->id, 'nombre' => 'Flowpack x 700g']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->set('tipo_producto', 'bobinas')
        ->assertSeeHtml('<option value="'.$producto->id.'">Flowpack x 700g</option>');
});

test('la manga recién cargada queda elegida en el select', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->call('abrirAlta', 'mangas')
        ->set('nuevoValor', '62')
        ->call('guardarAlta')
        // El value de la opción y el valor guardado tienen que coincidir.
        ->assertSeeHtml('<option value="62">62</option>')
        ->assertSet('bobinas.desarrollo', '62');
});

test('el cliente viaja al servidor apenas se elige', function () {
    // Sin .live el tipo de producto quedaba trabado hasta la siguiente acción.
    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->assertSeeHtml('wire:model.live="cliente_id"');
});

test('el buje toma los valores de ajustes y permite sumar uno', function () {
    App\Models\Ajuste::create(['grupo' => 'bujes', 'valor' => 3]);

    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        // El valor guardado es el número; el ´´ es solo la unidad que se muestra.
        ->assertSeeHtml('<option value="3">3´´</option>')
        ->call('abrirAlta', 'bujes')
        ->set('nuevoValor', '6')
        ->call('guardarAlta')
        ->assertHasNoErrors()
        ->assertSet('creando', null)
        ->assertSet('bobinas.buje', '6')
        ->assertSeeHtml('<option value="6">6´´</option>');

    expect(App\Models\Ajuste::opciones('bujes')->all())->toBe([3.0, 6.0]);
});

test('el buje que ya existe se elige en vez de fallar', function () {
    App\Models\Ajuste::create(['grupo' => 'bujes', 'valor' => 3]);

    cotizacionConCliente()
        ->call('abrirAlta', 'bujes')
        ->set('nuevoValor', '3')
        ->call('guardarAlta')
        ->assertHasNoErrors()
        ->assertSet('bobinas.buje', '3');

    expect(App\Models\Ajuste::delGrupo('bujes')->count())->toBe(1);
});

test('configuración administra mangas y bujes', function () {
    Livewire::test(App\Livewire\Configuracion\Ajustes::class)
        ->assertSee('Mangas')
        ->assertSee('Bujes')
        ->call('seleccionar', 'bujes')
        ->assertSet('grupo', 'bujes')
        ->set('valor', '8')
        ->call('agregar')
        ->assertHasNoErrors();

    expect(App\Models\Ajuste::opciones('bujes')->all())->toBe([8.0]);
});

test('solvente es si/no y laminación simple, bi. o tri.', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->assertSeeHtml('wire:model="bobinas.solvente"')
        ->assertSeeHtml('wire:model="bobinas.laminacion"')
        ->assertSeeHtml('<option value="Simple">Simple</option>')
        ->assertSeeHtml('<option value="Bi.">Bi.</option>')
        ->assertSeeHtml('<option value="Tri.">Tri.</option>')
        ->set('bobinas.solvente', 'No')
        ->set('bobinas.laminacion', 'Bi.')
        ->assertSet('bobinas.solvente', 'No')
        ->assertSet('bobinas.laminacion', 'Bi.');
});

test('a la derecha de colores va (diseños + cambios) x colores', function () {
    $formulario = cotizacionConCliente()->set('tipo_producto', 'bobinas');

    // Sin nada cargado no muestra ningún número.
    expect($formulario->instance()->coloresTotal())->toBe('');

    // Los valores de la maqueta: 1 diseño, 0 cambios, 8 colores.
    $formulario->set('bobinas.disenos', '1')
        ->set('bobinas.cambios', '0')
        ->set('bobinas.colores', '8');

    expect($formulario->instance()->coloresTotal())->toBe('8');

    $formulario->set('bobinas.cambios', '2');

    expect($formulario->instance()->coloresTotal())->toBe('24');
});

test('los campos que recalculan en el servidor usan wire:model.live', function () {
    // En Livewire 4 un wire:model sin .live no manda nada al servidor, así que
    // el total de colores y el redondeo de los ajustes nunca se actualizaban.
    $formulario = cotizacionConCliente()->set('tipo_producto', 'bobinas');

    foreach (['bobinas.disenos', 'bobinas.cambios', 'bobinas.colores', 'ajuste_categoria', 'ajuste_vendedor'] as $campo) {
        $formulario->assertSeeHtml('wire:model.live.blur="'.$campo.'"');
    }
});

test('el total de colores se ve en la pantalla', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.disenos', '10')
        ->set('bobinas.cambios', '10')
        ->set('bobinas.colores', '10')
        ->assertSeeHtml('>200</output>');
});

test('sin cantidad (mts) no se puede repartir la entrega', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->assertSee('Completá')
        ->assertDontSee('1° entrega')
        ->set('bobinas.cantidad', '67000')
        ->assertDontSee('Completá')
        ->assertSee('1° entrega');
});

test('la forma de entrega avisa cuánto falta repartir', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.cantidad', '67000')
        ->assertSee('Repartido 0 de 67.000 mts')
        ->assertSee('faltan 67.000')
        ->set('entregas.0.cantidad', '50000')
        ->set('entregas.1.cantidad', '17000')
        ->assertSee('Repartido 67.000 de 67.000 mts')
        ->assertDontSee('faltan')
        ->set('entregas.1.cantidad', '20000')
        ->assertSee('te pasaste por 3.000');
});

test('los kg / pallets salen de flete insumos', function () {
    $tramo = App\Models\FleteTramo::create(['kg' => 3500, 'pallets' => 6]);

    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.cantidad', '67000')
        ->assertSeeHtml('<option value="'.$tramo->id.'">3.500 / 6 pallets</option>');
});

test('el flete solo lista las zonas donde el cliente tiene direcciones', function () {
    $caba = App\Models\FleteZona::create(['nombre' => 'Caba']);
    $quilmes = App\Models\FleteZona::create(['nombre' => 'Quilmes']);
    // Zona del catálogo en la que el cliente no tiene ninguna dirección.
    $rosario = App\Models\FleteZona::create(['nombre' => 'Rosario']);

    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    App\Models\ContactoDireccion::create(['contacto_id' => $cliente->id, 'flete_zona_id' => $caba->id, 'direccion' => 'Av. Rivadavia 1234']);
    App\Models\ContactoDireccion::create(['contacto_id' => $cliente->id, 'flete_zona_id' => $quilmes->id, 'direccion' => 'Av. Cabildo 5785']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.cantidad', '67000')
        ->assertSeeHtml('<option value="'.$caba->id.'">Caba</option>')
        ->assertSeeHtml('<option value="'.$quilmes->id.'">Quilmes</option>')
        ->assertDontSeeHtml('<option value="'.$rosario->id.'">Rosario</option>');
});

test('la dirección se limita a la zona del flete elegido', function () {
    $caba = App\Models\FleteZona::create(['nombre' => 'Caba']);
    $quilmes = App\Models\FleteZona::create(['nombre' => 'Quilmes']);

    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    App\Models\ContactoDireccion::create(['contacto_id' => $cliente->id, 'flete_zona_id' => $caba->id, 'direccion' => 'Av. Rivadavia 1234']);
    $enQuilmes = App\Models\ContactoDireccion::create(['contacto_id' => $cliente->id, 'flete_zona_id' => $quilmes->id, 'direccion' => 'Av. Cabildo 5785']);

    $formulario = Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.cantidad', '67000')
        // Sin flete elegido no hay direcciones para elegir.
        ->assertSee('Elegí primero el flete')
        ->set('entregas.0.flete_zona_id', (string) $quilmes->id);

    $formulario->assertSeeHtml('<option value="'.$enQuilmes->id.'">Av. Cabildo 5785</option>')
        ->assertDontSee('Av. Rivadavia 1234');

    // Cambiar de zona limpia la dirección que ya no corresponde.
    $formulario->set('entregas.0.direccion_id', (string) $enQuilmes->id)
        ->set('entregas.0.flete_zona_id', (string) $caba->id)
        ->assertSet('entregas.0.direccion_id', '');
});

test('la dirección de entrega es solo la del cliente, no la de otro', function () {
    $zona = App\Models\FleteZona::create(['nombre' => 'Caba']);

    $cliente = Contacto::create(['codigo' => '000445', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Arcor']);
    $otro = Contacto::create(['codigo' => '000446', 'estado' => Contacto::CLIENTE, 'razon_social' => 'Bagley']);

    $direccion = App\Models\ContactoDireccion::create(['contacto_id' => $cliente->id, 'flete_zona_id' => $zona->id, 'direccion' => 'Av. Rivadavia 1324']);
    App\Models\ContactoDireccion::create(['contacto_id' => $otro->id, 'flete_zona_id' => $zona->id, 'direccion' => 'Av. Cabildo 4521']);

    Livewire::test(App\Livewire\Cotizaciones\Form::class)
        ->set('cliente_id', $cliente->id)
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.cantidad', '67000')
        ->set('entregas.0.flete_zona_id', (string) $zona->id)
        ->assertSeeHtml('<option value="'.$direccion->id.'">Av. Rivadavia 1324</option>')
        ->assertDontSee('Av. Cabildo 4521');
});

test('avisa cuando el cliente no tiene direcciones con zona', function () {
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.cantidad', '67000')
        ->assertSee('El cliente no tiene direcciones de entrega con zona');
});

test('el ancho refilado y el de lámina se calculan solos', function () {
    // Los valores de la maqueta: 44 x 2 = 88, y 88 + 2 = 90.
    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.ancho', '44')
        ->set('bobinas.modulos_ancho', '2')
        ->assertSet('bobinas.ancho_refilado', '88')
        ->assertSet('bobinas.ancho_lamina', '90')
        // Sin uno de los dos no hay resultado.
        ->set('bobinas.modulos_ancho', '')
        ->assertSet('bobinas.ancho_refilado', '')
        ->assertSet('bobinas.ancho_lamina', '');
});

test('el valor que se suma al ancho de lámina se puede cambiar', function () {
    $formulario = cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.ancho', '44')
        ->set('bobinas.modulos_ancho', '2')
        ->assertSet('bobinas.ancho_lamina', '90')
        ->call('abrirExtra')
        ->assertSet('editandoExtra', true)
        ->assertSet('nuevoExtra', '2')
        ->set('nuevoExtra', '3.5')
        ->call('guardarExtra')
        ->assertHasNoErrors()
        ->assertSet('editandoExtra', false)
        // El ancho de lámina se recalcula con el valor nuevo.
        ->assertSet('bobinas.ancho_lamina', '91.5');

    expect(App\Models\Parametro::valor(App\Models\Parametro::ANCHO_LAMINA_EXTRA))->toBe(3.5);

    // Es del sistema: vale para la próxima cotización también.
    cotizacionConCliente('Bagley')
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.ancho', '10')
        ->set('bobinas.modulos_ancho', '1')
        ->assertSet('bobinas.ancho_lamina', '13.5');
});

test('el valor que se suma al ancho de lámina tiene que ser un número', function () {
    cotizacionConCliente()
        ->call('abrirExtra')
        ->set('nuevoExtra', 'dos')
        ->call('guardarExtra')
        ->assertHasErrors(['nuevoExtra' => 'numeric'])
        ->assertSet('editandoExtra', true);
});

test('sin impresión no se muestran los datos técnicos', function () {
    $formulario = cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        // Vacío: todavía no se eligió nada.
        ->assertSee('Poné')
        ->assertDontSee('Ancho refilado (cm)')
        ->assertDontSee('Mangas disponibles (cm)')
        // El bloque de impresión sí se ve siempre.
        ->assertSee('Reprint')
        ->set('bobinas.impresion', 'Si');

    $formulario->assertDontSee('Poné')
        ->assertSee('Ancho refilado (cm)')
        ->assertSee('Ancho lámina (cm)')
        ->assertSee('Impresión Scrap (cm)')
        ->assertSee('Mangas disponibles (cm)');

    $formulario->set('bobinas.impresion', 'No')
        ->assertSee('Poné')
        ->assertDontSee('Ancho refilado (cm)');
});

/**
 * Un material de Configuración > Insumos con sus proveedores.
 */
function materialConProveedores(string $nombre = 'Polietileno Cristal'): App\Models\InsumoItem
{
    $insumo = App\Models\Insumo::firstOrCreate(['nombre' => App\Models\Insumo::MATERIALES], ['singular' => 'Material']);
    $familia = $insumo->familias()->firstOrCreate(['nombre' => 'Polietileno']);
    $item = $familia->items()->firstOrCreate(['nombre' => $nombre]);

    foreach (['Polifilm', 'Inepol'] as $indice => $razon) {
        $proveedor = App\Models\Proveedor::firstOrCreate(['nombre' => $razon]);

        App\Models\InsumoPrecio::firstOrCreate(
            ['insumo_item_id' => $item->id, 'proveedor_id' => $proveedor->id],
            ['costo' => 3.35 + $indice],
        );

        if ($razon === 'Inepol') {
            $item->update(['proveedor_elegido_id' => $proveedor->id]);
        }
    }

    return $item->fresh();
}

test('el material sale del catálogo de insumos', function () {
    $item = materialConProveedores();
    // Un item de otro insumo no tiene que aparecer entre los materiales.
    $tintas = App\Models\Insumo::firstOrCreate(['nombre' => 'Tintas'], ['singular' => 'Tinta']);
    $tintas->familias()->firstOrCreate(['nombre' => 'Base agua'])->items()->firstOrCreate(['nombre' => 'Tinta Cyan']);

    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->assertSeeHtml('<option value="'.$item->id.'">Polietileno Cristal</option>')
        ->assertDontSee('Tinta Cyan');
});

test('el proveedor depende del material elegido', function () {
    $item = materialConProveedores();
    // Proveedor del catálogo que no tiene cargado este material.
    App\Models\Proveedor::create(['nombre' => 'Plastiandino']);

    $formulario = cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->assertSee('Elegí primero el material')
        ->set('bobinas.materiales.0.material_id', (string) $item->id);

    $formulario->assertSeeHtml('<option value="'.$item->proveedor_elegido_id.'">Inepol</option>')
        ->assertSee('Polifilm')
        ->assertDontSee('Plastiandino')
        // Queda propuesto el proveedor con el que se trabaja.
        ->assertSet('bobinas.materiales.0.proveedor_id', (string) $item->proveedor_elegido_id);
});

test('un material sin proveedores lo avisa', function () {
    $insumo = App\Models\Insumo::firstOrCreate(['nombre' => App\Models\Insumo::MATERIALES], ['singular' => 'Material']);
    $item = $insumo->familias()->firstOrCreate(['nombre' => 'Polietileno'])
        ->items()->firstOrCreate(['nombre' => 'Polietileno EVOH ctal']);

    cotizacionConCliente()
        ->set('tipo_producto', 'bobinas')
        ->set('bobinas.materiales.0.material_id', (string) $item->id)
        ->assertSee('Este material no tiene proveedores cargados en Insumos')
        ->assertSet('bobinas.materiales.0.proveedor_id', '');
});
