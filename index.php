<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use B24Webhook\Bitrix24DealCreator;

// ---------------------------------------------------------------------------
// Конфигурация
// ---------------------------------------------------------------------------

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$webhookUrl = $_ENV['B24_WEBHOOK_URL'];

if (empty($webhookUrl) || strpos($webhookUrl, 'your-portal') !== false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Set B24_WEBHOOK_URL in .env (or $_ENV) with a real incoming webhook URL.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$creator = new Bitrix24DealCreator($webhookUrl);

try {
    // Полный сценарий: контакт -> сделка -> строка товара
    $result = $creator->createFullDeal(
        [
            'name'      => 'Иван',
            'last_name' => 'Иванов',
        ],
        [
            'title'    => 'Тестовая сделка',
            'amount'   => 110000.00,   // контакт 55 000 * 2
            'currency' => 'RUB',
        ],
        [
            'name'      => 'Ноутбук Dell',
            'price'     => 55000.00,
            'quantity'  => 2,
            'currency'  => 'RUB',
            // 'product_id' => 12345,  // опционально: ID товара из каталога
        ]
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error'   => $e->getMessage(),
        'trace'   => $e->getTraceAsString(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
