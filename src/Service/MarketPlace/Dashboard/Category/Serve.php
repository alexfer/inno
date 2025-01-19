<?php

namespace Inno\Service\MarketPlace\Dashboard\Category;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Inno\Entity\MarketPlace\StoreCategory;
use Inno\Entity\MarketPlace\StoreCategoryProduct;
use Inno\Repository\MarketPlace\StoreCategoryRepository;
use Inno\Service\MarketPlace\Dashboard\Category\Interface\ServeInterface;
use Symfony\Component\Form\FormInterface;

readonly class Serve implements ServeInterface
{

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(private EntityManagerInterface $em)
    {

    }

    /**
     * @param array $criteria
     * @param array $orderBy
     * @return array
     */
    public function handle(array $criteria = [], array $orderBy = []): array
    {
        return $this->repository()->findBy($criteria, $orderBy);
    }

    /**
     * @param FormInterface $form
     * @return mixed
     */
    public function request(FormInterface $form): mixed
    {
        return $form->get('category')->getData();
    }

    /**
     * @return StoreCategoryRepository|EntityRepository
     */
    public function repository(): StoreCategoryRepository|EntityRepository
    {
        return $this->em->getRepository(StoreCategory::class);
    }

    /**
     * @return StoreCategoryProduct|EntityRepository
     */
    public function categoryProductRepository(): StoreCategoryProduct|EntityRepository
    {
        return $this->em->getRepository(StoreCategoryProduct::class);
    }
}