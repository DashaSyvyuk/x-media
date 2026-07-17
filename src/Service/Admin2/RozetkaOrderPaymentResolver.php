<?php

namespace App\Service\Admin2;

/**
 * Rozetka payment is not mirrored into our DB.
 * Paid when the order is successfully completed, or the manager note contains «Оплачено».
 * Empty notes (and not completed) ⇒ unpaid / COD.
 */
final class RozetkaOrderPaymentResolver
{
    /** Successfully completed orders (API status_group). */
    private const STATUS_GROUP_SUCCESS = 2;

    /**
     * @param array<string, mixed> $apiOrder
     */
    public function isPaid(array $apiOrder): bool
    {
        if ((int) ($apiOrder['status_group'] ?? 0) === self::STATUS_GROUP_SUCCESS) {
            return true;
        }

        $notes = $this->managerNotesText($apiOrder);
        if ($notes === '') {
            return false;
        }

        return mb_stripos($notes, 'Оплачено') !== false;
    }

    /**
     * @param array<string, mixed> $apiOrder
     */
    private function managerNotesText(array $apiOrder): string
    {
        $parts = [];

        $current = trim((string) ($apiOrder['current_seller_comment'] ?? ''));
        if ($current !== '') {
            $parts[] = $current;
        }

        $history = $apiOrder['seller_comment'] ?? null;
        if (is_array($history)) {
            foreach ($history as $entry) {
                if (is_string($entry)) {
                    $text = trim($entry);
                    if ($text !== '') {
                        $parts[] = $text;
                    }
                    continue;
                }

                if (! is_array($entry)) {
                    continue;
                }

                $text = trim((string) ($entry['comment'] ?? $entry['text'] ?? ''));
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return trim(implode("\n", $parts));
    }
}
