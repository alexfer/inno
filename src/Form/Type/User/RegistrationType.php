<?php

namespace Inno\Form\Type\User;

use Inno\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, EmailType, PasswordType, SubmitType,};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{Email, IsTrue, Length, NotBlank};

class RegistrationType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'attr' => [
                'min' => 5,
                'max' => 180,
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'form.email.not_blank',
                ]),
                new Email(
                    message: 'form.email.not_valid'
                ),
            ],
        ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'form.message.agree_terms',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'min' => 8,
                    'max' => 180,
                    'pattern' => ".{8,180}",
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.password.not_blank',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'form.password.min',
                        'max' => 180,
                        'maxMessage' => 'form.password.max',
                    ]),
                ],
            ])
            ->add('send', SubmitType::class, []);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
