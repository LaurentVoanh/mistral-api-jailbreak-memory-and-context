<?php
session_start();

$apiUrl = 'https://api.mistral.ai/v1/chat/completions';
$apiKey = 'YOUR API KEY HERE';
$model = 'pixtral-large-latest';

// Récupérer l'identifiant de l'utilisateur à partir du cookie
$userId = $_COOKIE['user_id'];
$contextFilePath = "user/$userId/context.txt";

// Lire le contexte
if (!file_exists($contextFilePath)) {
    file_put_contents($contextFilePath, "");
}
$context = file_get_contents($contextFilePath);

// Récupérer le message de l'utilisateur
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$userMessage = $data['message'];

// Formater la requête
$prePrompt = "Réponds à la [question principale] et stocke dans des balises les informations importantes que tu trouveras. Par exemple, si l'utilisateur donne une information personnelle, alors tu encapsuleras ceci [prénom]Laurent[/prénom]. Tu dois toi-même détecter tout ce qui peut te constituer une mémoire pour les prochaines réponses et inventer des balises pertinentes en fonction de ce que dit l'utilisateur. Par exemple, le style que l'utilisateur demande d'utiliser. Tu es une IA capable de savoir comment te constituer une mémoire en prenant les bonnes informations. Voici le contexte actuel : $context.";
$prompt = $prePrompt . "\nUtilisateur: " . $userMessage . "\nRéponse publique: ";

$data = [
    'model' => $model,
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $prompt
                ]
            ]
        ]
    ],
    'max_tokens' => 15000
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

// Décoder la réponse JSON
$responseData = json_decode($response, true);

// Vérifier si la réponse contient les données attendues
if (isset($responseData['choices'][0]['message']['content'])) {
    $publicResponse = $responseData['choices'][0]['message']['content'];

    // Extraire le contexte caché de la réponse
    $hiddenResponse = "";
    $lines = explode("\n", $publicResponse);
    foreach ($lines as $line) {
        if (preg_match('/\[([^\]]+)\](.*?)\[\/\1\]/', $line, $matches)) {
            $hiddenResponse .= $matches[1] . ": " . trim($matches[2]) . "\n"; //trim pour enlever les espaces autour des valeurs
        }
    }

    echo json_encode(['response' => $publicResponse, 'hidden' => $hiddenResponse]);
} else {
    echo json_encode(['response' => 'Erreur: Réponse inattendue de l\'API', 'hidden' => '']);
}
?>