<?php

namespace Inno\Service\MarketPlace\Dashboard\Product;

use Inno\Entity\MarketPlace\{Store,
    StoreCategory,
    StoreCategoryProduct,
    StoreCoupon,
    StoreProduct,
    StoreProductDiscount};
use Inno\Helper\MarketPlace\MarketPlaceHelper;
use Inno\Service\MarketPlace\Dashboard\Product\Interface\ServeProductInterface;
use Symfony\Component\Form\FormInterface;

class ServeProductProduct extends Handle implements ServeProductInterface
{

    /**
     * @var FormInterface
     */
    protected FormInterface $form;

    /**
     * @param FormInterface|null $form
     * @return FormInterface|null
     */
    public function supports(?FormInterface $form = null): ?FormInterface
    {
        if ($form) {
            $this->form = $form;
            return $this->form->handleRequest($this->request);
        }
        return null;
    }

    /**
     * @param StoreProduct $product
     * @return StoreProduct
     * @throws \Exception
     */
    private function setDeleted(StoreProduct $product): StoreProduct
    {
        $date = new \DateTime('@' . strtotime('now'));
        $product->setDeletedAt($date);
        return $product;
    }

    /**
     * @param Store $store
     * @param StoreProduct $product
     * @return StoreProduct
     * @throws \Exception
     */
    public function update(Store $store, StoreProduct $product): StoreProduct
    {
        $this->category($product, true);
        $this->handleRelations($product);
        $product = $this->sku($product);
        $this->attributes($product, true);
        $this->em->persist($product);
        $this->discount($product, true);

        return $product;
    }

    /**
     * @param Store $store
     * @param StoreProduct $product
     * @return StoreProduct
     * @throws \Exception
     */
    public function create(Store $store, StoreProduct $product): StoreProduct
    {
        // apply category to product
        $this->category($product);
        // bind to store product
        $product->setStore($store);
        // if store is trashed set product also trashed
        if ($store->getDeletedAt()) {
            $product = $this->setDeleted($product);
        }
        // pre save product to get ID
        $this->em->persist($product);
        $this->em->flush();
        // bind extra data to product
        $this->handleRelations($product);
        $product->setSlug(MarketPlaceHelper::slug($product->getId()));
        // generate SKU is not provided
        $product = $this->sku($product);
        // apply attributes to product if the need
        $this->attributes($product);
        // apply values to product
        $this->em->persist($product);
        // setup discount
        $this->discount($product);

        return $product;
    }

    protected function discount(StoreProduct $product, bool $up = false): void
    {
        $discount = new StoreProductDiscount();

        if ($up) {
            $discount = $this->em->getRepository(StoreProductDiscount::class)->findOneBy(['product' => $product]);
        } else {
            $discount->setProduct($product);
        }

        $discount->setValue($this->post['product']['value'] ?: '0.00')
            ->setUnit($this->post['product']['unit']);

        $this->em->persist($discount);
        $this->em->flush();
    }

    /**
     * @param StoreProduct $product
     * @param bool $up
     * @return void
     */
    protected function category(StoreProduct $product, bool $up = false): void
    {
        $category = $this->form->get('category')->getData();

        if ($up) {
            $this->em->getRepository(StoreCategoryProduct::class)->removeCategoryProduct($product);
        }

        if ($category) {
            $productCategory = new StoreCategoryProduct();
            $productCategory->setProduct($product)
                ->setCategory($this->findCategory($category));
            $this->em->persist($productCategory);
        }
    }

    /**
     * @param int $id
     * @return StoreCategory|null
     */
    private function findCategory(int $id): ?StoreCategory
    {
        return $this->em->getRepository(StoreCategory::class)->find($id);
    }

    /**
     * @param Store $store
     * @param string|null $search
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function index(Store $store, ?string $search = null, int $offset = 0, int $limit = 20): array
    {
        return $this->em->getRepository(StoreProduct::class)
            ->products($store, $search, $offset, $limit) ?? [];
    }

    /**
     * @param Store $store
     * @param string $type
     * @return array|null
     */
    public function coupon(Store $store, string $type): ?array
    {
        return $this->em->getRepository(StoreCoupon::class)
            ->fetchActive($store, $type);
    }
}