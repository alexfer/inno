<?php declare(strict_types=1);

namespace Inno\Form\Type\Dashboard\MarketPlace;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCoupon;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType,
    DateTimeType,
    HiddenType,
    NumberType,
    RangeType,
    SubmitType,
    TextareaType,
    TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class CouponType extends AbstractType
{
    /**
     * @var Store|null
     */
    private null|Store $store;

    /**
     * @param Security $security
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     */
    public function __construct(
        Security               $security,
        RequestStack           $requestStack,
        EntityManagerInterface $em,
    )
    {
        $user = $security->getUser();
        $store = $requestStack->getCurrentRequest()->get('store');
        $this->store = $em->getRepository(Store::class)
            ->findOneBy(['id' => $store]);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.form.name',
                'attr' => [
                    'maxlength' => 255,
                    'minlength' => 6,
                    'pattern' => ".{6,255}",
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.name.not_blank',
                    ]),
                ],
            ])
            ->add('promotionText', TextareaType::class, [
                'label' => 'label.form.promotion_text',
                'required' => false,
                'attr' => [
                    'maxlength' => 255,
                    'rows' => 3,
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'form.promotion_text.max',
                    ])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'label.form.type',
                'choices' => [
                    'coupon.choices.product' => StoreCoupon::COUPON_PRODUCT,
                    'coupon.choices.order' => StoreCoupon::COUPON_ORDER,
                    'coupon.choices.shipment' => StoreCoupon::COUPON_SHIPMENT,
                ],
            ])
            ->add('orderLimit', HiddenType::class, [
                /*
                'label' => 'label.form.order_limit',
                'html5' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.limit.not_blank',
                    ]),
                    new Positive([
                        'message' => 'form.limit.positive',
                    ]),
                ],
                */
                'data' => 100,
            ])
            ->add('maxUsage', HiddenType::class, [
                /*
                'label' => 'label.form.max_usage',
                'html5' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.max_usage.not_blank',
                    ]),
                    new Positive([
                        'message' => 'form.max_usage.positive',
                    ]),
                ],
                */
                'data' => 100,
            ])
            ->add('available', NumberType::class, [
                'label' => 'label.form.available',
                'html5' => true,
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                    'step' => 1,
                    'readonly' => (bool)$options['data']->getAvailable(),
                    'pattern' => ".{1,100}",
                    'placeholder' => "1-100",
                ],
                //'data' => $options['data']->getAvailable(),
            ])
            ->add('startedAt', DateTimeType::class, [
                'date_label' => 'label.form.startedAt',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.date.not_blank',
                    ]),
                ],
            ])
            ->add('expiredAt', DateTimeType::class, [
                'date_label' => 'label.form.expiredAt',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.date.not_blank',
                    ]),
                ],
            ])
            ->add('discount', RangeType::class, [
                'required' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 1,
                    'pattern' => "[1-9]{100}",
                    'value' => $options['data']?->getDiscount() ?: 0,
                ],
                'constraints' => [
                    new Positive(),
                    new Length([
                        'min' => 1,
                        'minMessage' => 'form.discount.min',
                        'max' => 100,
                        'maxMessage' => 'form.discount.max',
                    ]),
                ],
            ])
            ->add('price', NumberType::class, [
                'attr' => [
                    'min' => 1,
                    'step' => '0.01',
                ],
                'html5' => true,
            ])
            ->add('event', ChoiceType::class, [
                'label' => 'label.form.event',
                'choices' => [
                    'label.form.event.start' => 1,
                    'label.form.event.end' => 0,
                ],
                'expanded' => true,
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
            'data_class' => StoreCoupon::class,
        ]);
    }
}