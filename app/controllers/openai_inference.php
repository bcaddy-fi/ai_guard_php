<?php
$apiKey = getenv('OPENAI_API_KEY');

$headers = [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
];
function call_openai_api($payload) {
    global $apiKey;

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
