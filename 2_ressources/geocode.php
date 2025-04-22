<?php

// Configuration
$apiKey = env('AIzaSyAztN32dm25Xx2e88LEny9VZGB4SmUyxjs'); // Utilisez votre clé API
$baseUrl = 'https://maps.googleapis.com/maps/api/geocode/json';

// Fonction pour géocoder une adresse
function geocoderAdresse(string $adresse): ?array
{
    try {
        // Préparation de l'URL
        $encodedAddress = urlencode($adresse);
        $url = "{$baseUrl}?address={$encodedAddress}&key={$apiKey}&language=fr";

        // Configuration de la requête
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Exécution de la requête
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Vérification du code HTTP
        if ($httpCode !== 200) {
            logger()->warning('Erreur HTTP lors du géocodage', [
                'code' => $httpCode,
                'adresse' => $adresse
            ]);
            return null;
        }

        // Traitement de la réponse
        $data = json_decode($response, true);

        // Vérification du statut
        if ($data['status'] !== 'OK') {
            logger()->warning('Erreur de géocodage', [
                'statut' => $data['status'],
                'adresse' => $adresse
            ]);
            return null;
        }

        // Extraction des coordonnées
        $resultat = $data['results'][0];
        return [
            'latitude' => $resultat['geometry']['location']['lat'],
            'longitude' => $resultat['geometry']['location']['lng'],
            'adresse_formatee' => $resultat['formatted_address'],
            'precision' => $resultat['geometry']['location_type']
        ];

    } catch (\Exception $e) {
        logger()->error('Erreur technique lors du géocodage', [
            'erreur' => $e->getMessage(),
            'adresse' => $adresse
        ]);
        return null;
    }
}


if ($resultat) {
    echo "Coordonnées trouvées:\n";
    echo "Latitude: {$resultat['latitude']}\n";
    echo "Longitude: {$resultat['longitude']}\n";
    echo "Adresse formatée: {$resultat['adresse_formatee']}\n";
    echo "Précision: {$resultat['precision']}\n";
} else {
    echo "Échec du géocodage\n";
}