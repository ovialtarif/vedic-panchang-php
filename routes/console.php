<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('panchang:about', function (): void {
    $this->info('Vedic Panchang API — Laravel '.app()->version());
})->purpose('Display Vedic Panchang application information');
