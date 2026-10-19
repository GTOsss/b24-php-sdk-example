<?php

declare(strict_types=1);

namespace B24Webhook;

use Bitrix24\SDK\Services\CRM\CRMServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;

use Throwable;

/**
 * Bitrix24DealCreator
 * Типы объектов CRM (таблица по числовому значению): https://apidocs.bitrix24.ru/api-reference/crm/data-types.html#object_type
 *
 * Выполняет последовательность, требуемую заданием:
 *   1. Создаёт контакт (имя + фамилия).
 *   2. Создаёт сделку, привязанную к контакту, с заданной суммой.
 *   3. Добавляет строку товара в сделку.
 *   4. Возвращает идентификаторы всех созданных сущностей.
 */
class Bitrix24DealCreator
{
    private CRMServiceBuilder $crmService;

    /**
     * @param string $webhookUrl URL входящего вебхука, например:
     *                           https://your-portal.bitrix24.ru/rest/1/xxxxxxxxxxxxxxxx/
     */
    public function __construct(private string $webhookUrl)
    {
        $serviceBuilder = ServiceBuilderFactory::createServiceBuilderFromWebhook($webhookUrl);
        $this->crmService = $serviceBuilder->getCRMScope();
    }

    /**
     * Создаёт контакт в CRM.
     *
     * @param string $name      Имя контакта (NAME)
     * @param string $lastName  Фамилия контакта (LAST_NAME)
     * @return int
     * @throws Throwable при ошибке SDK/API
     */
    public function createContact(string $name, string $lastName): int
    {
        $result = $this->crmService->item()->add(3, [
            'NAME' => $name,
            'LAST_NAME' => $lastName
        ]);

        return (int) $result->item()->id;
    }

    /**
     * Создаёт сделку в CRM, привязанную к указанному контакту.
     *
     * @param int    $contactId ID контакта, к которому привязывается сделка
     * @param string $title     Название сделки
     * @param float  $amount    Сумма сделки (OPPORTUNITY)
     * @param string $currency  Валюта сделки, по умолчанию 'RUB'
     * @return int ID созданной сделки
     * @throws Throwable при ошибке SDK/API
     */
    public function createDeal(int $contactId, string $title, float $amount, string $currency = 'RUB'): int
    {
        $result = $this->crmService->item()->add(2, [
            'TITLE'        => $title,
            'CONTACT_ID'   => $contactId,
            'OPPORTUNITY'  => $amount,
            'CURRENCY_ID'  => $currency,
        ]);

        return (int) $result->item()->id;
    }

    /**
     * Добавляет строку товара в сделку CRM через crm.item.productrow.add.
     *
     * @param int    $dealId     ID сделки (ownerType = 'D')
     * @param string $productName Название товара, например 'Ноутбук Dell'
     * @param float  $price      Цена за единицу
     * @param int    $quantity   Количество
     * @param string $currency   Валюта строки товара, по умолчанию 'RUB'
     * @param int|null $productId Необязательный ID товара из каталога
     * @return int ID созданной строки товара
     * @throws Throwable при ошибке SDK/API
     */
    public function addProductRow(
        int $dealId,
        string $productName,
        float $price,
        int $quantity,
        string $currency = 'RUB',
        ?int $productId = null
    ): int {
        $fields = [
            'ownerType'  => 'D',          // D — Сделка
            'ownerId'    => $dealId,
            'productName'=> $productName,
            'price'      => $price,
            'quantity'   => $quantity,
            'currency'   => $currency,
        ];

        if ($productId !== null) {
            $fields['productId'] = $productId;
        }

        $result = $this->crmService->itemProductrow()->add($fields);

        return (int) $result->getId();
    }

    /**
     * Запускает полный сценарий: контакт -> сделка -> строка товара.
     *
     * @param array $contact  ['name' => ..., 'last_name' => ...]
     * @param array $deal     ['title' => ..., 'amount' => ..., 'currency' => ...]
     * @param array $product  ['name' => ..., 'price' => ..., 'quantity' => ..., 'currency' => ..., 'product_id' => ...]
     * @return array ['contact_id' => ..., 'deal_id' => ..., 'product_row_id' => ...]
     * @throws Throwable при ошибке SDK/API
     */
    public function createFullDeal(array $contact, array $deal, array $product): array
    {
        $contactId = $this->createContact(
            $contact['name'] ?? '',
            $contact['last_name'] ?? ''
        );

        $dealId = $this->createDeal(
            $contactId,
            $deal['title'] ?? 'Без названия',
            (float) ($deal['amount'] ?? 0),
            $deal['currency'] ?? 'RUB'
        );

        $productRowId = $this->addProductRow(
            $dealId,
            $product['name'] ?? '',
            (float) ($product['price'] ?? 0),
            (int) ($product['quantity'] ?? 1),
            $product['currency'] ?? 'RUB',
            $product['product_id'] ?? null
        );

        return [
            'contact_id'    => $contactId,
            'deal_id'       => $dealId,
            'product_row_id'=> $productRowId,
        ];
    }
}
