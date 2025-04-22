<?php

namespace App\Filament\Resources;

use App\Services\GeocodingService;
use Filament\Tables\Actions\BulkAction;

class Ressource extends Resource
{
    protected function getBulkActions(): array
    {
        return [
            BulkAction::make('geocoder-adresses')
                ->label('Géocoder les adresses sélectionnées')
                ->deselectAfterCompletion()
                ->action(function (Collection $records): void {
                    $geocodingService = app(GeocodingService::class);
                    
                    $records->chunk(10)->each(function ($chunk) use ($geocodingService) {
                        foreach ($chunk as $record) {
                            $resultat = $geocodingService->geocodeAddress($record->adresse);
                            
                            if ($resultat) {
                                $record->update([
                                    'latitude' => $resultat['lat'],
                                    'longitude' => $resultat['lng'],
                                    'geocode_status' => 'success'
                                ]);
                            }
                        }
                    });
                    
                    session()->flash('success', 'Les adresses ont été géocodées avec succès.');
                })
        ];
    }
}