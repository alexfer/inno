<?php declare(strict_types=1);

namespace Inno\Form\Type\Dashboard\MarketPlace;

use Inno\Entity\MarketPlace\StoreCarrier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class CarrierType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('logo', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('linkUrl', UrlType::class, [
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Regex(
                        '/(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i',
                        'form.url.not_valid',
                    )
                ],
            ])
            ->add('description', TextareaType::class)
            ->add('enabled', CheckboxType::class, [
                'required' => false,
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
            'data_class' => StoreCarrier::class
        ]);
    }
}
