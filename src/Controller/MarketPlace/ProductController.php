<?php

declare(strict_types=1);

namespace App\Controller\MarketPlace;

use App\Controller\Trait\ControllerTrait;
use App\Entity\MarketPlace\StoreProduct;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/market-place/product')]
class ProductController extends AbstractController
{
    use ControllerTrait;

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/{slug}/{tab}', name: 'app_market_place_product')]
    public function index(Request $request): Response
    {

        $product = $this->em->getRepository(StoreProduct::class)
            ->fetchProduct($request->get('slug'));

        if ($product['product'] === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('market_place/product/index.html.twig', [
            'product' => $product['product'],
            'customer' => $this->getCustomer($this->getUser()),
        ]);
    }
}
