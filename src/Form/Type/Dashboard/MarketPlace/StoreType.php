<?php

namespace Inno\Form\Type\Dashboard\MarketPlace;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{Store, StoreCarrier, StorePaymentGateway};
use Inno\Service\MarketPlace\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType,
    EmailType,
    FileType,
    NumberType,
    SubmitType,
    TelType,
    TextareaType,
    TextType,
    UrlType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locale;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{Email, Image, Length, NotBlank, Regex};

class StoreType extends AbstractType
{
    /**
     * @var array
     */
    private array $gateways = [];

    /**
     * @var array
     */
    private array $carriers = [];

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $gateways = $em->getRepository(StorePaymentGateway::class)->findBy(['active' => true]);

        foreach ($gateways as $gateway) {
            $this->gateways[$gateway->getName()] = $gateway->getId();
        }

        $carriers = $em->getRepository(StoreCarrier::class)->findBy(['is_enabled' => true]);

        foreach ($carriers as $carrier) {
            $this->carriers[$carrier->getName()] = $carrier->getId();
        }
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
            ->add('address', TextType::class, [
                'attr' => [
                    'min' => 3,
                    'max' => 250,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.address.not_blank',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'form.address.min',
                        'max' => 250,
                        'maxMessage' => 'form.address.max',
                    ]),
                ],
            ])
            ->add('gateway', ChoiceType::class, [
                'mapped' => false,
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->gateways,
            ])
            ->add('tax', NumberType::class, [
                'attr' => [
                    'min' => '0.00',
                    'step' => '0.01',
                    'max' => '100.00',
                ],
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'form.discount.max',
                    ]),
                ],
                'html5' => true,
            ])
            ->add('country', ChoiceType::class, [
                'placeholder' => 'form.country.placeholder',
                'label' => 'label.country',
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.country.not_blank',
                    ]),
                ],
                'choices' => array_flip(Countries::getNames(Locale::getDefault())),
            ])
            ->add('currency', ChoiceType::class, [
                'placeholder' => 'label.form.store_currency.placeholder',
                'label' => 'label.currency',
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.currency.not_blank',
                    ]),
                ],
                'choices' => [
                    Currency::USD => Currency::USD,
                    Currency::EUR => Currency::EUR,
                    Currency::UAH => Currency::UAH,

                ],
            ])
            ->add('phone', TelType::class, [
                'attr' => [
                    'pattern' => "[+0-9]+$",
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.phone.not_blank',
                    ]),
                    new Regex([
                        'pattern' => "/[+0-9]+$/i",
                        'message' => 'form.phone.not_valid',
                    ]),
                ],
            ])
            ->add('website', UrlType::class, [
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://',
                ],
                'constraints' => [
                    new Regex(
                        '/(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i',
                        'form.url.not_valid',
                    )
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'min' => 5,
                    'max' => 180,
                ],
                'constraints' => [
                    new Email(
                        message: 'form.email.not_valid'
                    ),
                ],
            ])
            ->add('cc', ChoiceType::class, [
                'mapped' => false,
                'label' => 'label.form.cc',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'data' => json_decode($options['data']->getCc(), true),
                'choices' => [
                    'mastercard' => 'mastercard',
                    'visa' => 'visa',
                    'amex' => 'amex',
                    'discover' => 'discover',
                    'jcb' => 'jcb',
                    'maestro' => 'maestro',
                    'paypal' => 'paypal',
                ],
            ])
            ->add('logo', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label_attr' => [
                    'name' => 'label.form.market_logo'
                ],
                'attr' => [
                    'accept' => 'image/png, image/jpeg, image/webp',
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => ini_get('post_max_size'),
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'form.picture.not_valid_type',
                    ]),
                ],
            ])
            ->add('carrier', ChoiceType::class, [
                'mapped' => false,
                'label' => 'label.form.carrier',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->carriers,
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'min' => 100,
                    'max' => 65535,
                ],
                'constraints' => [
                    new Length([
                        'min' => 100,
                        'minMessage' => 'form.description.min',
                        'max' => 65535,
                        'maxMessage' => 'form.description.max',
                    ]),
                ],
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }

    /**
     * @return string|null
     */
    public function getParent(): ?string
    {
        return SocialType::class;
    }
}