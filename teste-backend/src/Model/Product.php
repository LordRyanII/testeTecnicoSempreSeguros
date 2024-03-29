<?php

namespace Contatoseguro\TesteBackend\Model;

class Product
{
    public $category;

    public function __construct(
        public int $id,
        public int $companyId,
        public string $title,
        public float $price,
        public bool $active,
        public string $createdAt
    ) {
    }

    public static function hydrateByFetch($fetch): ?self
    {
        if ($fetch === false || $fetch === null) {
            return null;
        }

        return new self(
            $fetch['id'] ?? 0,
            $fetch['company_id'] ?? 0,
            $fetch['title'] ?? '',
            $fetch['price'] ?? 0.0,
            (bool)($fetch['active'] ?? false),
            $fetch['created_at'] ?? ''
        );
    }

    public function setCategory($category)
    {
        $this->category = $category;
    }
}
