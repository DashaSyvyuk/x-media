<?php

namespace App\Service\Admin2;

final class XKomProductInfo
{
    public function __construct(
        public readonly ?int $price,
        public readonly bool $available,
        public readonly ?string $title = null,
    ) {
    }
}
