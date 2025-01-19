<?php declare(strict_types=1);

namespace Inno\Service\Validator;

use Inno\Service\Validator\Interface\PaymentGatewayValidatorInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaymentGatewayValidator implements PaymentGatewayValidatorInterface
{

    /**
     * @param mixed $payload
     * @param ValidatorInterface $validator
     * @return ConstraintViolationListInterface
     */
    public function validate(mixed $payload, ValidatorInterface $validator): ConstraintViolationListInterface
    {
        $constraints = [
            'name' => [
                new NotBlank([
                    'message' => 'form.name.not_blank'
                ]),
                new Length([
                    'max' => 255,
                    'maxMessage' => 'form.name.too_long',
                ]),
            ],
            'summary' => [
                new NotBlank([
                    'message' => 'form.description.not_blank'
                ]),
                new Length([
                    'max' => 255,
                    'maxMessage' => 'form.description.too_long',
                ]),
            ],
            'handlerText' => [
                new NotBlank([
                    'message' => 'form.handler.not_blank'
                ]),
                new Length([
                    'max' => 255,
                    'maxMessage' => 'form.handler.too_long',
                ]),
            ],
            'active' => [],
        ];

        return $validator->validate($payload, new Collection($constraints));
    }
}