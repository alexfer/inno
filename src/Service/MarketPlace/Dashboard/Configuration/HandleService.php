<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Dashboard\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\Attach;
use Inno\Entity\MarketPlace\StoreCarrier;
use Inno\Entity\MarketPlace\StorePaymentGateway;
use Inno\Service\FileUploader;
use Inno\Service\Validator\Interface\CarrierValidatorInterface;
use Inno\Service\Validator\Interface\ImageValidatorInterface;
use Inno\Service\Validator\Interface\PaymentGatewayValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HandleService
{
    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * @var array|string[]
     */
    protected array $storage = [
        'carrier' => 'carrier_storage_dir',
        'payment_gateway' => 'payment_gateway_storage_dir',
    ];

    /**
     * @param EntityManagerInterface $manager
     * @param TranslatorInterface $translator
     * @param CarrierValidatorInterface $carrierValidator
     * @param PaymentGatewayValidatorInterface $paymentGatewayValidator
     * @param ValidatorInterface $validator
     * @param SluggerInterface $slugger
     * @param ParameterBagInterface $params
     * @param ImageValidatorInterface $imageValidator
     */
    public function __construct(
        protected readonly EntityManagerInterface         $manager,
        private readonly TranslatorInterface              $translator,
        private readonly CarrierValidatorInterface        $carrierValidator,
        private readonly PaymentGatewayValidatorInterface $paymentGatewayValidator,
        private readonly ValidatorInterface               $validator,
        private readonly SluggerInterface                 $slugger,
        protected readonly ParameterBagInterface          $params,
        private readonly ImageValidatorInterface          $imageValidator,
    )
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @param int $id
     * @param string $class
     * @return StoreCarrier|StorePaymentGateway
     */
    protected function get(
        int $id, string $class = StoreCarrier::class | StorePaymentGateway::class
    ): StoreCarrier|StorePaymentGateway
    {
        return $this->manager->getRepository($class)->findOneBy(['id' => $id]);
    }

    /**
     * @param StoreCarrier|StorePaymentGateway $class
     * @return void
     */
    protected function removeAttach(StoreCarrier|StorePaymentGateway $class): void
    {
        if ($class instanceof StoreCarrier) {
            $file = $this->params->get($this->storage['carrier']) . '/' . $class->getAttach()->getName();

            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);
            }
        }

        if ($class instanceof StorePaymentGateway) {
            $file = $this->params->get($this->storage['payment_gateway']) . '/' . $class->getAttach()->getName();

            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);
            }
        }
    }

    /**
     * @param array $data
     * @param array|null $media
     * @return StoreCarrier
     */
    protected function createCarrier(array $data, ?array $media = null): StoreCarrier
    {
        if ($media) {
            try {
                $attach = $this->setAttach($media['attach'], $this->storage['carrier'], $media);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage(), 400);
            }
        }

        $errors = $this->carrierValidator->validate($data, $this->validator);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                throw new \InvalidArgumentException($error->getMessage(), 400);
            }
        }

        $carrier = new StoreCarrier();

        $carrier->setName($data['name'])
            ->setDescription($data['description'])
            ->setAttach($attach ?? null)
            ->setSlug($this->slugger->slug($data['name'])->lower()->toString())
            ->setLinkUrl($data['linkUrl'])
            ->setEnabled($data['enabled']);

        $this->manager->persist($carrier);
        $this->manager->flush();
        return $carrier;
    }

    /**
     * @param array $data
     * @param array|null $media
     * @return StorePaymentGateway
     */
    protected function createPaymentGateway(array $data, ?array $media = null): StorePaymentGateway
    {
        if ($media) {
            try {
                $attach = $this->setAttach($media['attach'], $this->storage['payment_gateway'], $media);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage(), 400);
            }
        }

        $errors = $this->paymentGatewayValidator->validate($data, $this->validator);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                throw new \InvalidArgumentException($error->getMessage(), 400);
            }
        }

        $paymentGateway = new StorePaymentGateway();

        $paymentGateway
            ->setName($data['name'])
            ->setSummary($data['summary'])
            ->setSlug($this->slugger->slug($data['name'])->lower()->toString())
            ->setHandlerText($data['handlerText'])
            ->setAttach($attach ?? null)
            ->setActive($data['active']);

        $this->manager->persist($paymentGateway);
        $this->manager->flush();

        return $paymentGateway;
    }

    /**
     * @param array $data
     * @param StoreCarrier $storeCarrier
     * @param array|null $media
     * @return StoreCarrier
     */
    protected function updateCarrier(array $data, StoreCarrier $storeCarrier, ?array $media = null): StoreCarrier
    {
        if ($media) {
            try {
                $attach = $this->setAttach($media['attach'], $this->storage['carrier'], $media);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage(), 400);
            }

            if ($storeCarrier->getAttach()) {
                $file = $this->params->get($this->storage['carrier']) . '/' . $storeCarrier->getAttach()->getName();

                if ($this->filesystem->exists($file)) {
                    $this->filesystem->remove($file);
                }
            }

        }

        $errors = $this->carrierValidator->validate($data, $this->validator);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                throw new \InvalidArgumentException($error->getMessage(), 400);
            }
        }

        $storeCarrier->setName($data['name'])
            ->setDescription($data['description'])
            ->setSlug($this->slugger->slug($data['name'])->lower()->toString())
            ->setLinkUrl($data['linkUrl']);
        if ($media) {
            $storeCarrier->setAttach($attach);
        }
        $storeCarrier->setEnabled($data['enabled']);

        $this->manager->persist($storeCarrier);
        $this->manager->flush();
        return $storeCarrier;
    }

    /**
     * @param array $data
     * @param StorePaymentGateway $paymentGateway
     * @param array|null $media
     * @return StorePaymentGateway
     */
    protected function updatePaymentGateway(array $data, StorePaymentGateway $paymentGateway, ?array $media = null): StorePaymentGateway
    {

        if ($media) {
            try {
                $attach = $this->setAttach($media['attach'], $this->storage['payment_gateway'], $media);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage(), 400);
            }

            if ($paymentGateway->getAttach()) {
                $file = $this->params->get($this->storage['payment_gateway']) . '/' . $paymentGateway->getAttach()->getName();

                if ($this->filesystem->exists($file)) {
                    $this->filesystem->remove($file);
                }
            }
        }

        $errors = $this->paymentGatewayValidator->validate($data, $this->validator);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                throw new \InvalidArgumentException($error->getMessage(), 400);
            }
        }

        $paymentGateway
            ->setName($data['name'])
            ->setSummary($data['summary'])
            ->setHandlerText($data['handlerText']);
        if ($media) {
            $paymentGateway->setAttach($attach);
        }
        $paymentGateway->setActive($data['active']);

        $this->manager->persist($paymentGateway);
        $this->manager->flush();

        return $paymentGateway;
    }

    /**
     * @param string|null $attach
     * @param string $storage
     * @param array|null $media
     * @return Attach|null
     */
    protected function setAttach(?string $attach, string $storage, ?array $media = null): ?Attach
    {
        $filePath = null;

        if ($attach) {
            $file = explode(';base64,', $attach);
            $filePath = tempnam(sys_get_temp_dir(), 'UploadedFile');
            $tmpFile = base64_decode($file[1]);
            file_put_contents($filePath, $tmpFile);
        }

        if ($filePath) {
            $validate = $this->imageValidator->validate($filePath, $this->translator);

            if ($validate->has(0)) {
                throw new \InvalidArgumentException($validate->get(0)->getMessage(), 400);
            }

            $fileUploader = new FileUploader($this->params->get($storage), $this->slugger, $this->manager);

            try {
                $attach = $fileUploader->upload(new UploadedFile($filePath, $media['name'], $media['mime'], null, true))->handle();
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage(), 400);
            }

            return $attach;
        }
        return null;
    }
}