<?php declare(strict_types=1);

namespace Inno\Command\Elastic;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCategory;
use Inno\Entity\MarketPlace\StoreCategoryProduct;
use Symfony\Component\Console\Style\SymfonyStyle;

class ElasticService implements ElasticServiceInterface
{
    /**
     * @param Client $client
     * @param string $index
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function reset(Client $client, string $index): void
    {
        $client->indices()->delete(['index' => $index]);
    }

    /**
     * @param Client $client
     * @param string $index
     * @return string[]
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function create(Client $client, string $index): array
    {
        $options = [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            'shingle' => [
                                'type' => 'shingle'
                            ]
                        ],
                        'analyzer' => [
                            'reuters' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'stop', 'kstem']
                            ]
                        ]
                    ],
                ],
                'mappings' => [
                    '_source' => [
                        'enabled' => true
                    ],
                    'properties' => [
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'reuters',
                        ],
                    ],
                ],
            ],
        ];

        $client->indices()->create($options);
        return ['message' => 'CREATED'];
    }

    /**
     * @param QueryBuilder $builder
     * @param SymfonyStyle $io
     * @param Client $client
     * @param string $index
     * @return void
     */
    public function build(QueryBuilder $builder, SymfonyStyle $io, Client $client, string $index): void
    {
        $products = $builder->select([
            'p.id',
            'p.slug',
            'p.name',
            'p.short_name',
            's.name as store_name',
            's.slug as store_slug',
            'cc.name as category_name',
            'cc.slug as category_slug',
        ])
            ->join(
                Store::class,
                's',
                Join::WITH,
                's.id = p.store')
            ->join(
                StoreCategoryProduct::class,
                'cp',
                Join::WITH,
                'cp.product = p.id')
            ->join(
                StoreCategory::class,
                'c',
                Join::WITH,
                'c.id = cp.category')
            ->join(
                StoreCategory::class,
                'cc',
                Join::WITH,
                'c.parent = cc.id')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.quantity > 0')
            ->getQuery()
            ->getArrayResult();

        foreach ($products as $product) {
            try {
                $client->create([
                    'id' => $product['id'],
                    'index' => $index,
                    'client' => [
                        'future' => 'lazy'
                    ],
                    'body' => [
                        'name' => $product['name'],
                        'product' => [
                            'id' => $product['id'],
                            'category_name' => $product['category_name'],
                            'category_slug' => $product['category_slug'],
                            'short_name' => $product['short_name'],
                            'slug' => $product['slug'],
                            'store_name' => $product['store_name'],
                            'store_slug' => $product['store_slug'],
                        ]
                    ]
                ]);
                $io->writeln(sprintf('Added [%s] to elasticsearch]', $product['slug']));
            } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
                $io->writeln($e->getMessage());
            }
        }
    }
}