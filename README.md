# b24-php-webhook-test-task

Проект реализован на двух версиях php b24 SDK
- 1.7
- 3.7

Нужно переключиться на соотвествующую ветку. 

PHP-скрипт для входящего вебхука Bitrix24 (без приложения OAuth).

## Что он делает

1. Создаёт **контакт** с именем и фамилией.
2. Создаёт **сделку**, привязанную к контакту, с заданной суммой.
3. Добавляет **строку товара** в сделку (`Ноутбук Dell`, 55 000 RUB, количество 2).
4. Возвращает идентификаторы созданных сущностей в формате JSON.

## Требования

- PHP 7.4 или 8.x
- Composer
- Официальный **PHP SDK Bitrix24** `bitrix24/php-sdk: ^1.7.*`
- Портал Bitrix24 с активным **входящим вебхуком**

> ⚠️ Примечание: PHP и Composer не установлены в этой среде, и нет доступа в интернет, поэтому `composer install` / `vendor/` здесь выполнены быть не могут.
> Выполните шаги настройки на машине с PHP + Composer и доступом в интернет.

## Настройка

```bash
# 1. Установите зависимости (загружается bitrix24/php-sdk ^1.7)
composer install

# 2. Настройте URL вебхука
cp .env.example .env
# отредактируйте .env и укажите B24_WEBHOOK_URL=https://your-portal.bitrix24.ru/rest/1/xxxxxxxxxxxxxxxx/

# 3. Запустите
php index.php
```

## Структура проекта

| Файл | Назначение |
|------|------------|
| `composer.json` | Манифест Composer; требует `bitrix24/php-sdk: ^1.7`, автозагрузка PSR-4 `B24Webhook\` → `src/` |
| `.env.example` | Пример файла окружения с `B24_WEBHOOK_URL` |
| `index.php` | Точка входа: создаёт `Bitrix24DealCreator`, запускает сценарий, выводит JSON |
| `src/Bitrix24DealCreator.php` | ООП-обёртка над SDK (контакт → сделка → строка товара) |
| `todo.md` | Оригинальное описание задания |

## Пример вывода

```json
{
  "contact_id": 1234,
  "deal_id": 5678,
  "product_row_id": 91011
}
```

При ошибке отвечает с HTTP 500 и телом JSON, содержащим `error` и `trace`.

## Справочник по SDK

Реализация следует официальному примеру PHP SDK Bitrix24:

```php
$serviceBuilder = ServiceBuilderFactory::createServiceBuilderFromWebhook($webhookUrl);
$crmService = $serviceBuilder->getCRMScope();

$contactId = $crmService->contact()->add(['NAME' => '...', 'LAST_NAME' => '...'])->contact()->getId();
$dealId    = $crmService->deal()->add(['TITLE' => '...', 'CONTACT_ID' => $contactId, 'OPPORTUNITY' => 110000, 'CURRENCY_ID' => 'RUB'])->deal()->getId();
$rowId     = $crmService->item()->productrow()->add([
    'ownerType' => 'D',   // D = Сделка
    'ownerId'   => $dealId,
    'productName' => 'Ноутбук Dell',
    'price'     => 55000,
    'quantity'  => 2,
    'currency'  => 'RUB',
])->productRow()->getId();
```

Все запросы выполняются через входящий вебхук; приложение OAuth не задействуется.
