<?php declare(strict_types=1);

namespace Inno\Controller\MarketPlace;

use Inno\Controller\Trait\ControllerTrait;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCoupon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/market-place/store')]
class StoreController extends AbstractController
{
    use ControllerTrait;


    /**
     * @return Response
     */
    #[Route('', name: 'app_market_place_stores')]
    public function index(): Response
    {
        $random = $this->em->getRepository(Store::class)->random(4);

        return $this->render('market_place/store/index.html.twig', [
            'store' => $random['store'],
            'products' => $random['products'],
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/redirect/{slug}', name: 'app_market_place_market_redirect', methods: ['GET'])]
    public function redirectTo(Request $request): Response
    {
        $store = $this->em->getRepository(Store::class)->findOneBy(['slug' => $request->get('slug')]);
        return $this->redirect($store->getUrl(), 301);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/{slug}', name: 'app_market_place_market')]
    public function market(Request $request): Response
    {
        $customer = $this->getCustomer($this->getUser());

        $offset = $request->query->getInt('offset', 0);
        $limit = $request->query->getInt('limit', 10);
        $store = $this->em->getRepository(Store::class)
            ->fetch($request->get('slug'), $customer, $offset, $limit);

        if ($store['result'] === null) {
            throw $this->createNotFoundException();
        }

        $coupon = $this->em->getRepository(StoreCoupon::class)
            ->getSingleActive($store['result']['id'], 'order');

        $result = array_merge($store['result'], (is_array($coupon) ? $coupon : ['coupon' => null]));

        return $this->render('market_place/store/store.html.twig', [
            'store' => $result,
            'customer' => $customer,
        ]);
    }

}
