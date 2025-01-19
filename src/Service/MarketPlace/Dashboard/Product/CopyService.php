<?php

namespace Inno\Service\MarketPlace\Dashboard\Product;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Inno\Entity\MarketPlace\StoreProduct;
use Inno\Helper\MarketPlace\MarketPlaceHelper;
use Inno\Service\MarketPlace\Dashboard\Product\Interface\CopyServiceInterface;

class CopyService implements CopyServiceInterface
{

    /**
     * @param EntityManagerInterface $manager
     */
    public function __construct(private readonly EntityManagerInterface $manager)
    {

    }

    /**
     * @param int $id
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function copyProduct(int $id): void
    {
        $product = $this->manager->find(StoreProduct::class, $id);
        $clone = $this->cloneProduct($product);

    }

    private function cloneProduct(StoreProduct $product): StoreProduct
    {
        return clone $product;
    }

    private function fill(StoreProduct $product, StoreProduct $clone): void
    {
        $clone->setName(sprintf("[Copy: %s]", $product->getName()));
        $clone->setDescription(sprintf("[Copy: %s]", $product->getDescription()));
        $clone->setSlug(MarketPlaceHelper::slug($product->getId() + 1));
        $this->manager->persist($clone);
        $this->manager->flush();
    }

}