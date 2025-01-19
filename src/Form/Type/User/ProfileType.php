<?php

namespace Inno\Form\Type\User;

use Inno\Entity\UserDetails;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface,};
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType,
    DateType,
    FileType,
    HiddenType,
    SubmitType,
    TelType,
    TextareaType,
    TextType,};
use Symfony\Component\Intl\{Countries, Locale,};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{Length, NotBlank, Regex,};
use Symfony\Component\Validator\Constraints\Image;
use function array_flip;

class ProfileType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('first_name', TextType::class, [
            'mapped' => false,
            'data' => $options['data']?->getFirstName(),
            'attr' => [
                'min' => 3,
                'max' => 200,
                'pattern' => ".{3,200}",
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'form.first_name.not_blank',
                ]),
                new Length([
                    'min' => 3,
                    'minMessage' => 'form.first_name.min',
                    'max' => 200,
                    'maxMessage' => 'form.first_name.max',
                ]),
            ],
        ])
            ->add('last_name', TextType::class, [
                'mapped' => false,
                'data' => $options['data']?->getLastName(),
                'attr' => [
                    'min' => 2,
                    'max' => 200,
                    'pattern' => ".{3,200}",
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.last_name.not_blank',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'form.last_name.min',
                        'max' => 200,
                        'maxMessage' => 'form.last_name.max',
                    ]),
                ],
            ])
            ->add('picture', FileType::class, [
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/png, image/jpeg, image/webp, image/svg+xml',
                    'max' => 52428800
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => ini_get('post_max_size'),
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
                        'mimeTypesMessage' => 'form.picture.not_valid_type',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'attr' => [
                    'pattern' => "[+0-9]+$",
                    'min' => 10,
                    'max' => 14,
                ],
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'minMessage' => 'form.phone.min',
                        'max' => 14,
                        'maxMessage' => 'form.phone.max',
                    ]),
                    new Regex([
                        'pattern' => "/[+0-9]+$/i",
                        'message' => 'form.phone.not_valid',
                    ]),
                ],
            ])
            ->add('date_birth', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ])
            ->add('twitter_profile', TextType::class, [
                'mapped' => false,
                'data' => $options['data']?->getUserSocial()?->getTwitterProfile(),
            ])
            ->add('facebook_profile', TextType::class, [
                'mapped' => false,
                'data' => $options['data']?->getUserSocial()?->getFacebookProfile(),
            ])
            ->add('instagram_profile', TextType::class, [
                'mapped' => false,
                'data' => $options['data']?->getUserSocial()?->getInstagramProfile(),
            ])
            ->add('city', TextType::class, [])
            ->add('country', ChoiceType::class, [
                'placeholder' => 'form.country.placeholder',
                'label' => 'label.country',
                'required' => false,
                'multiple' => false,
                'expanded' => false,
                'choices' => array_flip(Countries::getNames(Locale::getDefault())),
            ])
            ->add('about', TextareaType::class, [
                'attr' => [
                    'min' => 100,
                    'max' => 65535,
                    'rows' => 6,
                ],
                'constraints' => [
                    new Length([
                        'min' => 100,
                        'minMessage' => 'form.about.min',
                        'max' => 65535,
                        'maxMessage' => 'form.about.max',
                    ]),
                ],
            ])
            ->add('attach', HiddenType::class, [
                'mapped' => false,
                'data' => $options['data']?->getUser()?->getAttach()?->getId(),
            ])
            ->add('update', SubmitType::class, [
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
            'data_class' => UserDetails::class,
        ]);
    }
}
