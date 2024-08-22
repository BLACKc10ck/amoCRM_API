<?php

// Данные для интеграции
$subdomain = ''; // Ваш поддомен
$clientId = ''; // Ваш Client ID
$clientSecret = ''; // Ваш Client Secret
$redirectUri = ''; // Ваш Redirect URI
$authorizationCode = ''; // Ваш Authorization Code

// URL для получения токена доступа
$tokenUrl = "https://{$subdomain}.amocrm.ru/oauth2/access_token";

// Данные для получения токена доступа
$postData = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'grant_type' => 'authorization_code',
    'code' => $authorizationCode,
    'redirect_uri' => $redirectUri,
];

// Инициализация CURL для получения токена доступа
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $tokenUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

// Выполнение запроса для получения токена доступа
$response = curl_exec($curl);

if ($response === false) {
    $error = curl_error($curl);
    echo "CURL Error: $error";
    curl_close($curl);
    exit;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    $accessToken = $responseData['access_token'];
    $refreshToken = $responseData['refresh_token'];
} else {
    echo "Failed to obtain Access Token. HTTP Status Code: $httpCode";
    echo "Response: $response";
    exit;
}

// Данные, полученные через POST-запрос (например, из формы)
$name = $_POST['name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$price = (int)$_POST['price']; // Убедитесь, что цена представлена как целое число
$status = $_POST['status']; // Кастомное поле (checkbox)

// Создание контакта
$contactUrl = "https://{$subdomain}.amocrm.ru/api/v4/contacts";
$contactData = [
    [
        'name' => $name,
        'custom_fields_values' => [
            [
                'field_id' => 'PHONE', // Замените на фактический ID поля для телефона
                'values' => [
                    ['value' => $phone]
                ]
            ],
            [
                'field_id' => 'EMAIL', // Замените на фактический ID поля для email
                'values' => [
                    ['value' => $email]
                ]
            ]
        ]
    ]
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $contactUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$accessToken}",
    'Content-Type: application/json'
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($contactData));

$response = curl_exec($curl);

if ($response === false) {
    echo "CURL Error: " . curl_error($curl);
    curl_close($curl);
    exit;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$contactId = null;

if ($httpCode === 200 || $httpCode === 201) {
    $contactResponse = json_decode($response, true);
    $contactId = $contactResponse['_embedded']['contacts'][0]['id'];
} else {
    echo "Failed to create contact. HTTP Status Code: $httpCode";
    echo "Response: $response";
    curl_close($curl);
    exit;
}

curl_close($curl);

// Создание сделки с использованием ID контакта
$dealUrl = "https://{$subdomain}.amocrm.ru/api/v4/leads";
$dealData = [
    [
        'name' => "Сделка с {$name}",
        'price' => $price,
        'pipeline_id' => 8515442, // Замените на фактический ID воронки
        'custom_fields_values' => [
            [
                'field_id' => 625555, // Замените на фактический ID кастомного поля (checkbox)
                'values' => [
                    ['value' => $status ? 'true' : 'false'] // Формат данных для checkbox
                ]
            ]
        ],
        '_embedded' => [
            'contacts' => [
                ['id' => $contactId]
            ]
        ]
    ]
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $dealUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$accessToken}",
    'Content-Type: application/json'
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dealData));

$response = curl_exec($curl);

if ($response === false) {
    echo "CURL Error: " . curl_error($curl);
} else {
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpCode === 200 || $httpCode === 201) {
        echo "Deal created successfully!";
    } else {
        echo "Failed to create deal. HTTP Status Code: $httpCode";
        echo "Response: $response";
    }
}

curl_close($curl);
?>
