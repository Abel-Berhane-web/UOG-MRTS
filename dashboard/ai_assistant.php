<?php
session_start();

// Your Groq API key
$API_KEY = "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";

// Get message from POST
$input = $_POST;
$message = trim($input['message'] ?? '');

header('Content-Type: application/json');

if (!$message) {
    echo json_encode(["ok" => false, "error" => "Missing 'message'"]);
    exit;
}

// Prepare request payload
$payload = [
    "model" => "openai/gpt-oss-20b",
    "messages" => [
        ["role" => "system", "content" => "You are a helpful AI assistant for University of Gondar Maintenance Request Tracking System (UoG MRTS)."],
        ["role" => "user", "content" => $message]
    ]
];

// cURL request to Groq
$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $API_KEY",
        "Content-Type: application/json"
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle errors
if (!$response) {
    echo json_encode(["ok"=>false,"error"=>"cURL error: ".$curlError]);
    exit;
}
if ($httpCode !== 200) {
    echo json_encode(["ok"=>false,"error"=>"Groq API returned HTTP code $httpCode","raw"=>$response]);
    exit;
}

// Parse response
$data = json_decode($response, true);
$reply = $data['choices'][0]['message']['content'] ?? "No reply from AI";

echo json_encode(["ok"=>true,"reply"=>$reply]);
