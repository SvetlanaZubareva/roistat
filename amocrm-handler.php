<?php
// Настройки AmoCRM
define('AMOCRM_SECRET_KEY', 'v0722FrDfQMT3dxcP0RaDbBzMQcYfiNVA2vZumh1GiKw3yzZL6XXifdxqnjMSzaB');
define('AMOCRM_INTEGRATION_ID', '91bcbd9f-84d1-489b-ae46-f8d091f821ce');
define('AMOCRM_ACCESS_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImU5ZjFlZjBmZmMwMjgxYjY0MjQwMGVkMDgzM2Y5YWYzMzE2NDlkZjIzYzNiZjhhZDJhZmQ2ZWRhOTJiYzQwMmEwYzA4ZTk0MzViY2U2ZDU2In0.eyJhdWQiOiI5MWJjYmQ5Zi04NGQxLTQ4OWItYWU0Ni1mOGQwOTFmODIxY2UiLCJqdGkiOiJlOWYxZWYwZmZjMDI4MWI2NDI0MDBlZDA4MzNmOWFmMzMxNjQ5ZGYyM2MzYmY4YWQyYWZkNmVkYTkyYmM0MDJhMGMwOGU5NDM1YmNlNmQ1NiIsImlhdCI6MTczNzQ4NTA3MCwibmJmIjoxNzM3NDg1MDcwLCJleHAiOjE3MzgyODE2MDAsInN1YiI6IjExOTk1NzYyIiwiZ3JhbnRfdHlwZSI6IiIsImFjY291bnRfaWQiOjMyMTc0MDIyLCJiYXNlX2RvbWFpbiI6ImFtb2NybS5ydSIsInZlcnNpb24iOjIsInNjb3BlcyI6WyJjcm0iLCJmaWxlcyIsImZpbGVzX2RlbGV0ZSIsIm5vdGlmaWNhdGlvbnMiLCJwdXNoX25vdGlmaWNhdGlvbnMiXSwiaGFzaF91dWlkIjoiMDA4ZmFmZTItMmZmNi00OGQ2LWEyMzItY2VjNTBjZGU1N2E5IiwiYXBpX2RvbWFpbiI6ImFwaS1iLmFtb2NybS5ydSJ9.m1q_9IFxTc7ILGR1v6ChTRH8nTsQRrZEqunEDHmhEYmqUnJiXjRclZIrz4JIDeKMXGtnBNiW9y7i9bk9gvp-XbpAJ5he24CjUjdWxdjTctBDoL-m-4GTWCDZwUw7h0odkDhDOicnPDRtsum6RHz9GG7JbhXt2JtHg26p77_eMiRfirA5sPOacscvpuWQQfmxGHubezDMMmMl0VFMMjXpWm3WORDTpI3X_je_fHRTrzGD1v8K6tAL3i7JUYWK06yZEgXpps1CaC1b_UuaG2G0IAZydhiWWAl-Cwb8_8g1FwUx-H4bkTguR7LVrpvUzkSMd74EDislFEJHyckGZIDISw'); // Укажите ваш access_token
define('AMOCRM_BASE_URL', 'https://seberseneva.amocrm.ru'); // Укажите ваш поддомен AmoCRM

// Получение данных из формы
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$phone = $_POST['phone'] ?? null;
$price = $_POST['price'] ?? null;
$isLongVisit = $_POST['isLongVisit'] ?? 0; // Получаем значение 1 или 0 из формы

// Проверяем обязательные поля
if (!$name || !$email || !$phone || !$price) {
    http_response_code(400);
    echo json_encode(['error' => 'Все поля формы обязательны.']);
    exit;
}

// Функция для отправки запроса в AmoCRM
function amoRequest($method, $url, $data = null) {
    $headers = [
        'Authorization: Bearer ' . AMOCRM_ACCESS_TOKEN,
        'Content-Type: application/json',
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, AMOCRM_BASE_URL . $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    if ($data) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode < 200 || $httpCode >= 300) {
        echo json_encode(['error' => 'Ошибка AmoCRM: ' . $response]);
        exit;
    }

    return json_decode($response, true);
}

// 1. Создание контакта
$contactData = [
    'name' => $name,
    'custom_fields_values' => [
        [
            'field_code' => 'EMAIL',
            'values' => [['value' => $email]],
        ],
        [
            'field_code' => 'PHONE',
            'values' => [['value' => $phone]],
        ],
    ],
];

$contactResponse = amoRequest('POST', '/api/v4/contacts', [$contactData]);

if (empty($contactResponse['_embedded']['contacts'][0]['id'])) {
    echo json_encode(['error' => 'Не удалось создать контакт.']);
    exit;
}

$contactId = $contactResponse['_embedded']['contacts'][0]['id'];

// 2. Создание сделки
$dealData = [
    'name' => 'Сделка с сайта',
    'price' => (int)$price,
    'custom_fields_values' => [
        [
            'field_id' => 340761, // Укажите ваш ID дополнительного поля "Долгое пребывание"
            'values' => [['value' => (bool)$isLongVisit]], // Передаем значение 1 или 0 как boolean
        ],
    ],
    '_embedded' => [
        'contacts' => [['id' => $contactId]],
    ],
];

$dealResponse = amoRequest('POST', '/api/v4/leads', [$dealData]);

if (empty($dealResponse['_embedded']['leads'][0]['id'])) {
    echo json_encode(['error' => 'Не удалось создать сделку.']);
    exit;
}

// Успешный ответ
echo json_encode([
    'success' => true,
    'message' => 'Сделка и контакт успешно созданы.',
    'deal_id' => $dealResponse['_embedded']['leads'][0]['id'],
    'contact_id' => $contactId,
]);
