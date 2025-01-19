<?php

namespace Inno\Form\Type\Dashboard\MarketPlace;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Inno\Entity\MarketPlace\{Store, StoreCategory, StoreProduct};
use Inno\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface as StoreProductInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType,
    IntegerType,
    MoneyType,
    RangeType,
    SubmitType,
    TextareaType,
    TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{Length, NotBlank};

class ProductType extends AbstractType
{

    /**
     * @var Store|null
     */
    private null|Store $store;

    /**
     * @var array
     */
    private array $extra;

    /**
     * @var EntityRepository
     */
    private EntityRepository $categories;


    /**
     * @param Security $security
     * @param StoreProductInterface $store
     * @param EntityManagerInterface $em
     */
    public function __construct(
        Security               $security,
        StoreProductInterface  $store,
        EntityManagerInterface $em,
    )
    {
        $user = $security->getUser();
        $this->store = $store->supports($user);

        $this->categories = $em->getRepository(StoreCategory::class);
        $this->extra = $store->extra();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $extra = [];
        $marketCategory = $this->categories->findBy([], ['id' => 'asc']);

        if ($marketCategory) {
            foreach ($marketCategory as $category) {
                if ($category->getParent()) {
                    $extra['categories'][$category->getParent()->getName()][$category->getName()] = $category->getId();
                }
            }
        }

        if ($this->extra['brands']) {
            foreach ($this->extra['brands'] as $value) {
                $extra['brands'][$value['name']] = $value['id'];
            }
        }

        if ($this->extra['suppliers']) {
            foreach ($this->extra['suppliers'] as $value) {
                $extra['suppliers'][$value['name']] = $value['id'];
            }
        }

        if ($this->extra['manufacturers']) {
            foreach ($this->extra['manufacturers'] as $value) {
                $extra['manufacturers'][$value['name']] = $value['id'];
            }
        }

        $builder
            ->add('short_name', TextType::class, [
                'attr' => [
                    'min' => 3,
                    'max' => 80,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.short_name.not_blank',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'form.short_name.min',
                        'max' => 80,
                        'maxMessage' => 'form.short_name.max',
                    ]),
                ],
            ])
            ->add('sku', TextType::class, [
                'required' => false,
                'attr' => [
                    'min' => 3,
                    'max' => 80,
                ],
                'constraints' => [
                    new Length([
                        'max' => 80,
                        'maxMessage' => 'form.sku.max',
                    ]),
                ],
            ])
            ->add('name', TextareaType::class, [
                'attr' => [
                    'min' => 3,
                    'max' => 250,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.name.not_blank',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'form.name.min',
                        'max' => 250,
                        'maxMessage' => 'form.name.max',
                    ]),
                ],
            ])
            ->add('pckg_quantity', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                ],
                'constraints' => [
                    new Length([
                        'min' => 0,
                        'max' => 100,
                        'maxMessage' => 'form.quantity.max',
                    ]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 1000,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.quantity.not_blank',
                    ]),
                    new Length([
                        'min' => 1,
                        'minMessage' => 'form.quantity.min',
                        'max' => 1000,
                        'maxMessage' => 'form.quantity.max',
                    ]),
                ],
            ])
            ->add('pckg_discount', RangeType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 1,
                    'value' => (int)$options['data']?->getPckgDiscount() ?: 0,
                ],
                'constraints' => [
                    new Length([
                        'min' => 1,
                        'minMessage' => 'form.pckg_discount.min',
                        'max' => 100,
                        'maxMessage' => 'form.pckg_discount.max',
                    ]),
                ],
            ])
            ->add('fee', MoneyType::class, [
                'attr' => [
                    'min' => '0.00',
                    'step' => '1',
                ],
                'html5' => true,
                'currency' => $this->store->getCurrency() ?: 'USD',
            ])
            ->add('cost', MoneyType::class, [
                'attr' => [
                    'min' => '0.00',
                    'step' => '0.01',
                ],
                'html5' => true,
                'currency' => $this->store->getCurrency() ?: 'USD',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.cost.not_blank',
                    ]),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'mapped' => false,
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'option.category.choice_category',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.category.not_blank',
                    ]),
                ],
                'data' => $options['data']?->getStoreCategoryProducts()?->first() ? $options['data']?->getStoreCategoryProducts()?->first()?->getCategory()?->getId() : null,
                'choices' => $extra['categories'],
            ])
            ->add('brand', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'multiple' => false,
                'expanded' => false,
                'data' => $options['data']?->getStoreProductBrand()?->getBrand()?->getid(),
                'placeholder' => 'label.form.brand_name',
                'choices' => $extra['brands'] ?? [],
            ])
            ->add('supplier', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'multiple' => false,
                'expanded' => false,
                'data' => $options['data']?->getStoreProductSupplier()?->getSupplier()?->getid(),
                'placeholder' => 'label.form.supplier_name',
                'choices' => $extra['suppliers'] ?? [],
            ])
            ->add('manufacturer', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'multiple' => false,
                'expanded' => false,
                'data' => $options['data']?->getStoreProductManufacturer()?->getManufacturer()?->getid(),
                'placeholder' => 'label.form.manufacturer_name',
                'choices' => $extra['manufacturers'] ?? [],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'min' => 10,
                    'max' => 10000,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.description.not_blank',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'form.description.min',
                        'max' => 10000,
                        'maxMessage' => 'form.description.max',
                    ]),
                ],
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary rounded-1 shadow-sm',
                ],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreProduct::class,
        ]);
    }

    /**
     * @return string|null
     */
    public function getParent(): ?string
    {
        return AttributeType::class;
    }
}