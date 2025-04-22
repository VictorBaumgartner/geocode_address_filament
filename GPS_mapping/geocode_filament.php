<?php

namespace App\Filament\Resources;

use App\Services\GeocodingService;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

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
                    $failed = 0;

                    $records->chunk(10)->each(function ($chunk) use ($geocodingService, &$failed) {
                        foreach ($chunk as $record) {
                            // Combine Address1, Postcode, and City into a single address string
                            $addressParts = array_filter([
                                $record->Address1,
                                $record->Postcode,
                                $record->City
                            ], fn($part) => !empty(trim($part ?? '')));
                            $fullAddress = implode(', ', $addressParts);

                            if (empty($fullAddress)) {
                                $record->update(['geocode_status' => 'skipped']);
                                $failed++;
                                continue;
                            }

                            $resultat = $geocodingService->geocodeAddress($fullAddress);

                            if ($resultat) {
                                $record->update([
                                    'Lat' => $resultat['latitude'],
                                    'Lng' => $resultat['longitude'],
                                    'geocode_status' => 'success'
                                ]);
                            } else {
                                $record->update(['geocode_status' => 'failed']);
                                $failed++;
                            }
                            sleep(0.2); // 200ms delay to respect Google Maps API rate limits
                        }
                    });

                    $message = $failed > 0
                        ? "Géocodage terminé avec $failed échec(s)."
                        : 'Les adresses ont été géocodées avec succès.';
                    session()->flash('success', $message);
                })
        ];
    }
}

namespace App\Services;

use GoogleMaps;
use Illuminate\Support\Facades\Logger;

class GeocodingService
{
    public function geocodeAddress(string $address): ?array
    {
        try {
            // Configuration des paramètres de géocodage
            $response = GoogleMaps::load('geocoding')
                ->setParamByKey('address', $address)
                ->setParamByKey('language', 'fr') // Pour les résultats en français
                ->get();

            $result = json_decode($response, true);

            // Vérification du statut de la réponse
            if ($result['status'] !== 'OK') {
                Logger::warning('Échec du géocodage', [
                    'adresse' => $address,
                    'statut' => $result['status']
                ]);
                return null;
            }

            // Extraction des données de géocodage
            $resultat = $result['results'][0];
            
            return [
                'latitude' => $resultat['geometry']['location']['lat'],
                'longitude' => $resultat['geometry']['location']['lng'],
                'adresse_formatee' => $resultat['formatted_address'],
                'precision' => $resultat['geometry']['location_type'],
                'composants' => $this->traiterComposants($resultat['address_components'])
            ];

        } catch (\Exception $e) {
            Logger::error('Erreur technique lors du géocodage', [
                'erreur' => $e->getMessage(),
                'adresse' => $address
            ]);
            return null;
        }
    }

    private function traiterComposants(array $composants): array
    {
        $resultat = [];
        foreach ($composants as $composant) {
            $type = $composant['types'][0];
            $resultat[$type] = [
                'nom_long' => $composant['long_name'],
                'nom_court' => $composant['short_name']
            ];
        }
        return $resultat;
    }
}