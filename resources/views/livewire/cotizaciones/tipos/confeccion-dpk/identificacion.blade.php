{{-- Completa la fila del tipo de producto: medidas del envase y producto. --}}
<x-campo.texto label="Ancho (cm)" modelo="bobinas.ancho" tipo="number" paso="0.01" modificador="live.blur" requerido />
<x-campo.texto label="Alto (cm)" modelo="bobinas.alto" tipo="number" paso="0.01" modificador="live.blur" requerido />
<x-campo.texto label="Fuelle total (cm)" modelo="bobinas.fuelle" tipo="number" paso="0.01" modificador="live.blur" />

@include('livewire.cotizaciones.tipos.bobinas.producto')
