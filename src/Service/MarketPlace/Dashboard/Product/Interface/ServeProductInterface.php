<?php

namespace Inno\Service\MarketPlace\Dashboard\Product\Interface;

use Inno\Entity\MarketPlace\{Store, StoreProduct};
use Symfony\Component\Form\FormInterface;

interface ServeProductInterface
{
    /**
     * @param FormInterface|null $form
     * @return FormInterface|null
     */
    public function supports(?FormInterface $form = null): ?FormInterface;

    /**
     * @param Store $store
     * @param string|null $search
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function index(Store $store, ?string $search = null, int $offset = 0, int $limit = 20): array;

    /**
     * @param Store $store
     * @param string $type
     * @return array|null
     */
    public function coupon(Store $store, string $type): ?array;

    /**
     * @param Store $store
     * @param StoreProduct $product
     * @return StoreProduct
     */
    public function create(Store $store, StoreProduct $product): StoreProduct;

    /**
     * @param Store $store
     * @param StoreProduct $product
     * @return StoreProduct
     */
    public function update(Store $store, StoreProduct $product): StoreProduct;
}