<?php

namespace Inno\Service\MarketPlace\Dashboard\Product;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{StoreBrand,
    StoreManufacturer,
    StoreProduct,
    StoreProductAttribute,
    StoreProductAttributeValue,
    StoreProductBrand,
    StoreProductManufacturer,
    StoreProductSupplier,
    StoreSupplier};
use Inno\Helper\MarketPlace\StoreAttributeValues;
use Symfony\Component\HttpFoundation\{Request, RequestStack};

abstract class Handle
{

    /**
     * @var Request|null
     */
    protected ?Request $request;

    /**
     * @var array
     */
    protected array $post;

    /**
     * @param EntityManagerInterface $em
     * @param RequestStack $requestStack
     */
    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected RequestStack                    $requestStack,
    )
    {
        $this->request = $this->requestStack->getCurrentRequest();
        $this->post = $this->request->request->all();
    }

    /**
     * @param StoreProduct $product
     * @return void
     */
    private function flushAttributes(StoreProduct $product): void
    {
        foreach ($product->getStoreProductAttributes() as $attribute) {
            $values = $attribute->getStoreProductAttributeValues();
            foreach ($values as $value) {
                $this->em->remove($value);
                $this->em->flush();
            }
        }
    }

    /**
     * @param StoreProduct $product
     * @param bool $up
     * @return void
     */
    protected function attributes(StoreProduct $product, bool $up = false): void
    {
        $attributes['colors'] = $this->form->get('color')->getData();
        $attributes['size'] = $this->form->get('size')->getData();

        if ($up) {
            $this->flushAttributes($product);
        }

        if ($attributes['colors']) {
            $attributeColors = array_flip(StoreAttributeValues::ATTRIBUTES['Color']);

            $attribute = $this->em->getRepository(StoreProductAttribute::class)->findOneBy(['product' => $product, 'name' => 'color']);

            if (!$attribute) {
                $attribute = new StoreProductAttribute();
                $attribute->setProduct($product)->setName('color')->setInFront(1);
                $this->em->persist($attribute);
            }

            foreach ($attributes['colors'] as $color) {
                $attributeValue = new StoreProductAttributeValue();
                $value = $attributeValue->setAttribute($attribute)->setValue($attributeColors[$color])->setExtra([$color]);
                $this->em->persist($value);
                $this->setInFront('color', $product, 1);
            }
        } else {
            $this->setInFront('color', $product, 0);
        }

        if ($attributes['size']) {
            $attributeSize = array_flip(StoreAttributeValues::ATTRIBUTES['Size']);

            $attribute = $this->em->getRepository(StoreProductAttribute::class)->findOneBy(['product' => $product, 'name' => 'size']);

            if (!$attribute) {
                $attribute = new StoreProductAttribute();
                $attribute->setProduct($product)->setName('size')->setInFront(1);
                $this->em->persist($attribute);
            }

            foreach ($attributes['size'] as $size) {
                $attributeValue = new StoreProductAttributeValue();
                $value = $attributeValue->setAttribute($attribute)->setValue($attributeSize[$size]);
                $this->em->persist($value);
                $this->setInFront('size', $product, 1);
            }
        } else {
            $this->setInFront('size', $product, 0);
        }
    }

    private function setInFront(string $name, StoreProduct $product, int $value): void
    {
        $attribute = $this->em->getRepository(StoreProductAttribute::class)->findOneBy(['product' => $product, 'name' => $name]);
        if ($attribute !== null) {
            $attribute->setInFront($value);
            $this->em->persist($attribute);
        }
    }

    /**
     * @param StoreProduct $product
     * @return StoreProduct
     */
    protected function sku(StoreProduct $product): StoreProduct
    {
        $sku = $this->form->get('sku')->getData();

        if (!$sku) {
            $sku = [
                'S' . $this->request->get('store'),
                'C' . $this->form->get('category')->getData(),
                'P' . $product->getId(),
                'N' . mb_substr($this->form->get('name')->getData(), 0, 4, 'utf8'),
                'C' . (int)$this->form->get('cost')->getData()
            ];
            $sku = join('-', $sku);
        }

        $product->setSku($sku);

        return $product;
    }

    /**
     * @param StoreProduct $product
     * @return void
     */
    protected function handleRelations(StoreProduct $product): void
    {
        $supplier = $this->em->getRepository(StoreSupplier::class)
            ->findOneBy(['id' => $this->form->get('supplier')->getData()]);
        $brand = $this->em->getRepository(StoreBrand::class)
            ->findOneBy(['id' => $this->form->get('brand')->getData()]);
        $manufacturer = $this->em->getRepository(StoreManufacturer::class)
            ->findOneBy(['id' => $this->form->get('manufacturer')->getData()]);

        if ($supplier) {
            $ps = $product->getStoreProductSupplier();
            if (!$ps) {
                $ps = new StoreProductSupplier();
            }
            $ps->setProduct($product)->setSupplier($supplier);
            $this->em->persist($ps);
        } else {
            $ps = $product->getStoreProductSupplier();
            if ($ps) {
                $ps->setProduct(null)->setSupplier(null);
                $this->em->persist($ps);
                $this->em->flush();
                $this->em->getRepository(StoreProductSupplier::class)->drop($ps->getId());
            }
        }

        if ($brand) {
            $pb = $product->getStoreProductBrand();
            if (!$pb) {
                $pb = new StoreProductBrand();
            }
            $pb->setProduct($product)->setBrand($brand);
            $this->em->persist($pb);
        } else {
            $pb = $product->getStoreProductBrand();
            if ($pb) {
                $pb->setProduct(null)->setBrand(null);
                $this->em->persist($pb);
                $this->em->flush();
                $this->em->getRepository(StoreProductBrand::class)->drop($pb->getId());
            }
        }

        if ($manufacturer) {
            $pm = $product->getStoreProductManufacturer();
            if (!$pm) {
                $pm = new StoreProductManufacturer();
            }
            $pm->setProduct($product)->setManufacturer($manufacturer);
            $this->em->persist($pm);
        } else {
            $pm = $product->getStoreProductManufacturer();
            if ($pm) {
                $pm->setProduct(null)->setManufacturer(null);
                $this->em->persist($pm);
                $this->em->flush();
                $this->em->getRepository(StoreProductManufacturer::class)->drop($pm->getId());
            }
        }
    }

}