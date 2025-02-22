<?php declare(strict_types=1);

namespace Inno\Controller\MarketPlace;

use Inno\Controller\Trait\ControllerTrait;
use Inno\Entity\MarketPlace\StoreProduct;
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

        if ($product['product']['store']['website'] !== null) {
            $product['product']['store']['website'] = parse_url($product['product']['store']['website'], PHP_URL_HOST);
        }

        return $this->render('market_place/product/index.html.twig', [
            'product' => $product['product'],
            'customer' => $this->getCustomer($this->getUser()),
        ]);
    }
}
