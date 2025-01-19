<?php

namespace Inno\Service\MarketPlace\Dashboard\Category\Interface;

use Doctrine\ORM\EntityRepository;
use Inno\Entity\MarketPlace\StoreCategoryProduct;
use Inno\Repository\MarketPlace\StoreCategoryRepository;
use Symfony\Component\Form\FormInterface;

interface ServeInterface
{
    /**
     * @param array $criteria
     * @param array $orderBy
     * @return array
     */
    public function handle(array $criteria = [], array $orderBy = []): array;

    /**
     * @param FormInterface $form
     * @return mixed
     */
    public function request(FormInterface $form): mixed;

    /**
     * @return StoreCategoryRepository|EntityRepository
     */
    public function repository(): StoreCategoryRepository|EntityRepository;

    /**
     * @return StoreCategoryProduct|EntityRepository
     */
    public function categoryProductRepository(): StoreCategoryProduct|EntityRepository;
}