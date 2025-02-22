<?php declare(strict_types=1);

namespace Inno\Controller\MarketPlace;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreProduct;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/market-place/comparison')]
class CompareController extends AbstractController
{
    #[Route('/add', name: 'app_market_place_add_compare', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $payload = $request->getPayload()->all();

        if (!isset($payload['product'])) {
            return $this->json(['success' => false], Response::HTTP_BAD_REQUEST);
        }

        $products = [];
        $session = $request->getSession();

        $items = $session->get('products');

        if ($items) {
            $items = unserialize($items);

            foreach ($items as $item) {
                if ($payload['product'] != $item) {
                    $products[] = $item;
                }
            }
            $products[] = $payload['product'];
            $session->set('products', serialize($products));
        } else {
            $session->set('products', serialize([$payload['product']]));
        }

        return $this->json([
            'success' => true,
            'count' => count($products) == 0 ? 1 : count($products),
        ], Response::HTTP_OK);
    }

    #[Route('/remove/{id}', name: 'app_market_place_remove_compare', methods: ['GET'])]
    public function remove(Request $request): Response
    {
        $session = $request->getSession();
        $items = $session->get('products');
        $products = [];

        if ($items) {
            $items = unserialize($items);

            foreach ($items as $item) {
                if ($request->get('id') == $item) {
                    unset($items[$item]);
                    $products[] = $item;
                }
            }
            $session->set('products', serialize($products));
        }

        return $this->redirect($this->generateUrl('app_market_place_overview_comparison'));
    }

    #[Route('', name: 'app_market_place_overview_comparison', methods: ['GET'])]
    public function getCompareProducts(Request $request, EntityManagerInterface $manager): Response
    {
        $session = $request->getSession();
        $products = $session->get('products');
        if ($products) {
            $products = unserialize($products);
            $products = $manager->getRepository(StoreProduct::class)->findBy(['id' => $products]);
        }

        return $this->render('market_place/comparison.html.twig', ['products' => $products]);
    }
}