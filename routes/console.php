<?php

use App\Services\DolarOficial;
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
