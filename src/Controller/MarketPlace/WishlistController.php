<?php declare(strict_types=1);

namespace Inno\Controller\MarketPlace;

use Inno\Controller\Trait\ControllerTrait;
use Inno\Entity\MarketPlace\{Store, StoreProduct, StoreWishlist};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/market-place/wishlist')]
class WishlistController extends AbstractController
{
    use ControllerTrait;

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/add/{slug}', name: 'app_market_place_add_wishlist', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $product = $this->em->getRepository(StoreProduct::class)->findOneBy(['slug' => $request->get('slug')]);
        $parameters = json_decode($request->getContent(), true);

        $customer = $this->getCustomer($this->getUser());

        $responseStatus = Response::HTTP_OK;

        if (!$customer) {
            $customer = null;
            $responseStatus = Response::HTTP_UNAUTHORIZED;
        }

        $store = $this->em->getRepository(Store::class)->find($parameters['store']);

        if ($store && $customer) {
            $wishlist = new StoreWishlist();
            $wishlist->setStore($store)
                ->setCustomer($customer)
                ->setProduct($product);

            $this->em->persist($wishlist);
            $this->em->flush();
        }

        return $this->json([
            'product' => $product->getSlug(),
            'store' => $parameters['store'],
        ], $responseStatus);
    }
}