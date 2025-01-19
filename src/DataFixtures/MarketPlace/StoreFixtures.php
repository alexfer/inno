<?php declare(strict_types=1);

namespace Inno\DataFixtures\MarketPlace;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCarrier;
use Inno\Entity\MarketPlace\StoreCarrierStore;
use Inno\Entity\MarketPlace\StoreOptions;
use Inno\Entity\MarketPlace\StorePaymentGateway;
use Inno\Entity\MarketPlace\StorePaymentGatewayStore;
use Inno\Entity\MarketPlace\StoreSocial;
use Inno\Entity\User;
use Symfony\Component\String\Slugger\SluggerInterface;

class StoreFixtures extends Fixture
{
    public function __construct(private readonly SluggerInterface $slugger)
    {

    }

    public function load(ObjectManager $manager): void
    {
        $store = $this->createStore($manager);
        $this->createPaymentGateways($manager, $store);
        $this->createCarrier($manager, $store);
        $this->addSocial($manager, $store);
        $this->setOptions($manager, $store);
    }

    private function createStore(ObjectManager $manager): Store
    {
        $name = 'Excellent Store';
        $store = new Store();

        $store->setName($name)
            ->setOwner($manager->getRepository(User::class)->findOneBy(['email' => 'alexandershtyher@gmail.com']))
            ->setDescription('We have done everything right. We have set up the best-looking, most easily navigated ecommerce store on the web. We are selling the hottest products in the fastest-growing niche around.')
            ->setPhone('+16465553890')
            ->setSlug($this->slugger->slug($name)->lower()->toString())
            ->setEmail('alexandershtyher@gmail.com')
            ->setAddress('New York, NY, Brooklyn')
            ->setWebsite('https://inno.site')
            ->setCurrency('USD')
            ->setCountry('US')
            ->setTax(number_format(18, 2, '.', ''));

        $manager->persist($store);
        $manager->flush();

        return $store;
    }

    private function createPaymentGateways(ObjectManager $manager, Store $store): void
    {
        $paymentGateways = $manager->getRepository(StorePaymentGateway::class)->findBy(['active' => true]);

        foreach ($paymentGateways as $gateway) {
            $paymentGatewayStore = new StorePaymentGatewayStore();
            $paymentGatewayStore->setStore($store)
                ->setGateway($gateway)
                ->setActive(true);
            $manager->persist($paymentGatewayStore);
        }
        $manager->flush();
    }

    private function createCarrier(ObjectManager $manager, Store $store): void
    {
        $carriers = $manager->getRepository(StoreCarrier::class)->findBy(['is_enabled' => true]);

        foreach ($carriers as $carrier) {
            $carriersStore = new StoreCarrierStore();
            $carriersStore->setStore($store)
                ->setCarrier($carrier)
                ->setActive(true);
            $manager->persist($carriersStore);
        }
        $manager->flush();
    }

    private function addSocial(ObjectManager $manager, Store $store): void
    {
        foreach (StoreSocial::NAME as $value) {
            $social = new StoreSocial();
            $social->setSourceName($value)
                ->setSource("https://{$value}.com");
            $store->addStoreSocial($social);
            $manager->persist($social);
        }
    }

    private function setOptions(ObjectManager $manager, Store $store): void
    {
        $options = new StoreOptions();
        $options->setStore($store)->setBackupSchedule(0);
        $manager->persist($options);
        $manager->flush();
    }
}