<?php

use App\Services\DolarOficial;
use App\Support\DatosDemo;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('dolar:actualizar {--forzar : Actualiza aunque el dolar este en modo manual}', function (DolarOficial $dolar) {
    if (! $this->option('forzar') && ! DolarOficial::automatico()) {
        $this->comment('El dólar está en modo manual; no se actualiza. Usá --forzar para hacerlo igual.');

        return self::SUCCESS;
    }

    $valor = $dolar->actualizar();

    if ($valor === null) {
        $this->error('No se pudo consultar la cotización del dólar oficial.');

        return self::FAILURE;
    }

    $this->info(sprintf('Dólar oficial (BNA) actualizado a $%s.', number_format($valor, 2, ',', '.')));

    return self::SUCCESS;
})->purpose('Actualiza el dólar de Flete Insumos con la cotización oficial (BNA)');

// El BNA mueve la cotizacion durante la rueda: se consulta cada hora en horario bancario.
Schedule::command('dolar:actualizar')->weekdays()->hourly()->between('10:00', '18:00');

Artisan::command('demo:borrar {--force : No pide confirmacion}', function () {
    $contactos = DatosDemo::contactos()->count();

    if ($contactos === 0) {
        $this->info('No hay datos de demostración cargados.');

        return self::SUCCESS;
    }

    if (! $this->option('force') && ! $this->confirm("Se borran {$contactos} clientes y prospectos de demostración con sus cotizaciones. ¿Continuar?")) {
        return self::FAILURE;
    }

    $borrados = DatosDemo::borrar();

    $this->info(sprintf('Se borraron %d contactos y %d cotizaciones de demostración.', $borrados['contactos'], $borrados['cotizaciones']));

    return self::SUCCESS;
})->purpose('Borra los datos que cargó el DemoSeeder (clientes, prospectos y cotizaciones de demostración)');
