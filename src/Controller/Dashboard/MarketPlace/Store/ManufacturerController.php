<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException};
use Inno\Entity\MarketPlace\StoreManufacturer;
use Inno\Form\Type\Dashboard\MarketPlace\ManufacturerType;
use Inno\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface;
use Inno\Service\MarketPlace\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/market-place/manufacturer')]
class ManufacturerController extends AbstractController
{
    use StoreTrait;

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/{store}', name: 'app_dashboard_market_place_store_manufacturer')]
    public function index(
        Request                $request,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $manufacturers = $em->getRepository(StoreManufacturer::class)->manufacturers($store, $request->query->get('search'));

        $pagination = $this->paginator->paginate(
            $manufacturers,
            $request->query->getInt('page', 1),
            self::LIMIT - 1
        );

        return $this->render('dashboard/content/market_place/manufacturer/index.html.twig', [
            'store' => $store,
            'manufacturers' => $pagination,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param ServeStoreInterface $serveStore
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route('/create/{store}', name: 'app_dashboard_market_place_create_manufacturer', methods: ['GET', 'POST'])]
    public function create(
        Request                $request,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $manufacturer = new StoreManufacturer();

        $form = $this->createForm(ManufacturerType::class, $manufacturer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form->get('name')->getData();

            $exists = $em->getRepository(StoreManufacturer::class)->exists($store, trim($name));

            if (!$exists) {
                $manufacturer->setStore($store);
                $em->persist($manufacturer);
                $em->flush();

                $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));
                return $this->redirectToRoute('app_dashboard_market_place_edit_manufacturer', [
                    'store' => $request->get('store'),
                    'id' => $manufacturer->getId(),
                ]);
            } else {
                $this->addFlash('danger', json_encode(['message' => $translator->trans('choice.invalid', ['%name%' => $name], 'validators')]));
            }
        }

        return $this->render('dashboard/content/market_place/manufacturer/_form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param StoreManufacturer $manufacturer
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/edit/{store}/{id}', name: 'app_dashboard_market_place_edit_manufacturer', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        StoreManufacturer      $manufacturer,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());

        $form = $this->createForm(ManufacturerType::class, $manufacturer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manufacturer->setStore($store);
            $em->persist($manufacturer);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));
            return $this->redirectToRoute('app_dashboard_market_place_edit_manufacturer', [
                'store' => $request->get('store'),
                'id' => $manufacturer->getId(),
            ]);
        }

        return $this->render('dashboard/content/market_place/manufacturer/_form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param StoreManufacturer $manufacturer
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/delete/{store}/{id}', name: 'app_dashboard_delete_manufacturer', methods: ['POST'])]
    public function delete(
        Request                $request,
        StoreManufacturer      $manufacturer,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $token = $request->get('_token');

        if (!$token) {
            $content = $request->getPayload()->all();
            $token = $content['_token'];
        }

        if ($this->isCsrfTokenValid('delete', $token) && !$manufacturer->getStoreProductManufacturers()->count()) {
            $em->remove($manufacturer);
            $em->flush();
        }

        return $this->redirectToRoute('app_dashboard_market_place_store_manufacturer', ['store' => $store->getId()]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    #[Route('/xhr_create/{store}', name: 'app_dashboard_market_place_xhr_create_manufacturer', methods: ['POST'])]
    public function xshCreate(
        Request                $request,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
        TranslatorInterface    $translator,
    ): JsonResponse
    {
        $store = $this->store($serveStore, $this->getUser());
        $requestGetPost = $request->get('manufacturer');
        $responseJson = [];

        if ($requestGetPost && isset($requestGetPost['name'])) {
            if ($this->isCsrfTokenValid('create', $requestGetPost['_token'])) {
                $manufacturer = $em->getRepository(StoreManufacturer::class)->findOneBy(['store' => $store, 'name' => trim($requestGetPost['name'])]);

                if (!$manufacturer) {
                    $manufacturer = new StoreManufacturer();
                    $manufacturer->setName($requestGetPost['name']);
                    $manufacturer->setStore($store);
                    $em->persist($manufacturer);
                    $em->flush();

                    $responseJson = [
                        'json' => [
                            'id' => "#product_manufacturer",
                            'option' => [
                                'id' => $manufacturer->getId(),
                                'name' => $manufacturer->getName(),
                            ],
                        ],
                    ];
                } else {
                    $responseJson = [
                        'json' => [
                            'constraints' => [
                                'invalid' => $translator->trans('choice.invalid', ['%name%' => $requestGetPost['name']], 'validators')
                            ],
                        ]
                    ];
                }
            }
        }

        $response = new JsonResponse($responseJson);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}