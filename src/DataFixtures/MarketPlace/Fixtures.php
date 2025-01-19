<?php declare(strict_types=1);

namespace Inno\DataFixtures\MarketPlace;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Inno\Entity\MarketPlace\StoreCarrier;
use Inno\Entity\MarketPlace\StorePaymentGateway;
use Symfony\Component\String\Slugger\SluggerInterface;

class Fixtures extends Fixture
{

    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadPaymentMethods($manager);
        $this->loadCarriers($manager);
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            StoreFixtures::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    private function loadPaymentMethods(ObjectManager $manager): void
    {
        foreach ($this->getPaymentGatewayData() as [$name, $summary, $active, $handler_text]) {
            $gateway = new StorePaymentGateway();
            $gateway->setName($name);
            $gateway->setSlug($this->slugger->slug($name)->lower()->toString());
            $gateway->setAttach(null);
            $gateway->setHandlerText($handler_text);
            $gateway->setSummary($summary);
            $gateway->setActive($active);
            $manager->persist($gateway);
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    private function loadCarriers(ObjectManager $manager): void
    {
        foreach ($this->getCarrierData() as [$name, $description, $enabled, $url]) {
            $carrier = new StoreCarrier();
            $carrier->setName($name);
            $carrier->setSlug($this->slugger->slug($name)->lower()->toString());
            $carrier->setAttach(null);
            $carrier->setLinkUrl($url);
            $carrier->setDescription($description);
            $carrier->setEnabled($enabled);
            $manager->persist($carrier);
        }
        $manager->flush();
    }

    /**
     * @return array[]
     */
    private function getCarrierData(): array
    {
        return [
            ['DHL', 'Discover shipping and logistics service options from DHL Global Forwarding.', false, 'https://www.dhl.com'],
            ['FedEx', 'FedEx offers a wide range of services to meet your shipping needs to and from over 220 countries and territories worldwide.', false, 'https://www.fedex.com'],
            ['UPS', 'Moving our world forward by delivering what matters.', false, 'https://www.fedex.com'],
            ['Meest', 'International solutions for all your shipping and delivery needs.', false, 'https://meest.com'],
            ['Free', 'Free shipping.', true, 'https://inno.local'],
        ];
    }

    /**
     * @return array[]
     */
    private function getPaymentGatewayData(): array
    {
        return [
            ['Stripe', 'Stripe is powerful payment platform designed for internet businesses', false, 'Checkout with Stripe'],
            ['PayPal', 'PatPal - the safer, easier way to pay', false, 'Pay with Paypal'],
            ['ApplePay', 'Apple Pay is a safe, secure, and private way to pay', false, 'Checkout'],
            ['Cash', 'Pay with cash when your order is delivered', true, 'Checkout'],
            ['Adyen', 'End-to-end payments, data, and financial management in a single solution', false, 'Checkout via Adyen'],
        ];
    }
}