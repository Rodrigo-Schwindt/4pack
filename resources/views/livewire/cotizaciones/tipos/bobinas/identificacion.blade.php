{{-- Completa las filas del tipo de producto: las medidas de la bobina. --}}
<x-campo.texto label="Ancho (cm)" modelo="bobinas.ancho" tipo="number" paso="0.01" modificador="live.blur" />
<x-campo.texto label="Módulos Ancho (cm)" modelo="bobinas.modulos_ancho" tipo="number" paso="0.01" modificador="live.blur" />
<x-campo.texto label="Paso (cm)" modelo="bobinas.paso" tipo="number" paso="0.01" modificador="live.blur" />
<x-campo.texto label="Módulos Desarrollo (cm)" modelo="bobinas.modulos_desarrollo" tipo="number" paso="1" modificador="live.blur" />
<div class="hidden xl:block"></div>

{{-- El desarrollo sale de la cuenta; al lado, las mangas cargadas para poder sumar la que falte. --}}
<x-campo.texto label="Desarrollo (cm)" modelo="bobinas.desarrollo" calculado :ayuda="$this->ayudaDesarrollo() ?? 'Paso × módulos de desarrollo'" />
<x-campo.ajuste label="Mangas disponibles (cm)" grupo="mangas" modelo="bobinas.mangas" :opciones="$mangas" :creando="$creando" :campo-alta="$campoAlta" />

{{-- Los metros salen del peso: se carga el peso y la cantidad se calcula sola. --}}
<x-campo.texto
    label="Peso (kg)"
    modelo="bobinas.peso"
    tipo="number"
    paso="0.01"
    modificador="live.blur"
    requerido
/>
<x-campo.texto label="Cantidad (mts)" modelo="bobinas.cantidad" calculado :ayuda="$this->ayudaCantidad() ?? 'Peso × 1000 / Σ Kgrs x 1000 Mts'" />

{{-- Datos de la bobina terminada. --}}
<x-campo.select label="Peso neto" modelo="bobinas.peso_neto" :opciones="$opciones['si_no']" />
<x-campo.texto label="x bobina (kg)" modelo="bobinas.por_bobina" />
<x-campo.ajuste label="Buje" grupo="bujes" modelo="bobinas.buje" :opciones="$bujes" :creando="$creando" :campo-alta="$campoAlta" sufijo="´´" />
