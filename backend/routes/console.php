<?php

use App\Console\Commands\ProcessMonthlyChargesCommand;
use App\Console\Commands\ProcesarSuscripcionesVencidasCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ProcessMonthlyChargesCommand::class)->daily();
Schedule::command(ProcesarSuscripcionesVencidasCommand::class)->daily();
