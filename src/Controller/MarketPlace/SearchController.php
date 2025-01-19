<?php

declare(strict_types=1);

namespace Inno\Controller\MarketPlace;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Inno\Entity\MarketPlace\StoreCategory;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\MarketPlace\StoreProduct;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/market-place')]
class SearchController extends AbstractController
{
    /**
     * @var int
     */
    private int $offset = 0;

    /**
     * @var int
     */
    private int $limit = 30;

    /**
     * @var mixed
     */
    private static string $index;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @param RequestStack $stack
     * @param ParameterBagInterface $params
     * @throws AuthenticationException
     */
    public function __construct(
        RequestStack          $stack,
        ParameterBagInterface $params
    )
    {
        $page = is_numeric($stack->getCurrentRequest()->query->get('page')) ?: null;

        if (isset($page)) {
            $this->offset = $this->limit * ($page - 1);
        }

        self::$index = $params->get('app.elastic.index');
        $this->client = ClientBuilder::create()->setHosts([$params->get('app.elastic.dsn')])->build();
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function index(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(StoreCategory::class)->findBy(['parent' => null]);
        return $this->render('market_place/search/categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    #[Route('/search', name: 'app_market_place_search')]
    public function search(
        Request                $request,
        EntityManagerInterface $em,
    ): Response
    {
        $products = [];
        $rows = 0;
        $query = $request->query->get('query');

        $category = $request->query->get('category');
        $customer = $em->getRepository(StoreCustomer::class)->findOneBy([
            'member' => $this->getUser(),
        ]);

        if ($query) {
            $products = $em->getRepository(StoreProduct::class)->search($query, $category, $this->offset, $this->limit);
            $rows = $products['rows_count'];
            $products = $products['data'];
        }

        return $this->render('market_place/search/result.html.twig', [
            'products' => $products,
            'pages' => ceil($rows / $this->limit),
            'customer' => $customer,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    #[Route('/autocomplete', name: 'app_market_place_search_autocomplete')]
    public function autocomplete(
        Request $request
    ): JsonResponse
    {
        $term = $request->query->get('term');
        $results = [];

        if ($term) {
            $response = $this->client->search(self::params($term));
            $docs = $response['hits']['hits'];

            if (count($docs)) {
                foreach ($docs as $key => $doc) {
                    $product = $doc['_source']['product'];
                    $id = $product['id'];
                    $results[$id] = [
                        'id' => $id,
                        'name' => $doc['_source']['name'],
                        'short_name' => $product['short_name'],
                        'product_slug' => $product['slug'],
                        'store' => $product['store_name'],
                        'store_slug' => $product['store_slug'],
                    ];
                    if ($key > 10) {
                        break;
                    }
                }
            }

        }
        return $this->json([
            'template' => $this->renderView('market_place/search/autocomplete.html.twig', ['results' => $results])
        ], Response::HTTP_OK);
    }

    /**
     * @param string $query
     * @return array
     */
    private static function params(string $query): array
    {
        return [
            'index' => self::$index,
            'body' => [
                'query' => [
                    'wildcard' => [
                        'name' => sprintf('*%s*', $query),
                    ],
                ],
            ],
        ];
    }
}