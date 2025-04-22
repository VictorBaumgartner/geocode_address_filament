<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class GeocodingService
{
    public function geocodeAddress(string $address): ?array
    {
        try {
            $process = Process::run([
                'python',
                base_path('path/to/votre/script.py'),
                $address
            ], null, $output);

            if ($process->isSuccessful()) {
                return json_decode($process->getOutput(), true);
            }
            
            logger()->warning('Échec du géocodage', [
                'adresse' => $address,
                'erreur' => $process->getErrorOutput()
            ]);
            
            return null;
        } catch (\Exception $e) {
            logger()->error('Erreur lors du géocodage', [
                'adresse' => $address,
                'erreur' => $e->getMessage()
            ]);
            
            return null;
        }
    }
}