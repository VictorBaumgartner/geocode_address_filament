<?php

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