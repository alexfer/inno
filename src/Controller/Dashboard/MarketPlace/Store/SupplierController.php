<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException};
use Inno\Entity\MarketPlace\StoreSupplier;
use Inno\Form\Type\Dashboard\MarketPlace\SupplerType;
use Inno\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface;
use Inno\Service\MarketPlace\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locale;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/market-place/supplier')]
class SupplierController extends AbstractController
{
    use StoreTrait;

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/{store}', name: 'app_dashboard_market_place_store_supplier')]
    public function index(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $user);
        $suppliers = $em->getRepository(StoreSupplier::class)->suppliers($store, $request->query->get('search'));

        $pagination = $this->paginator->paginate(
            $suppliers,
            $request->query->getInt('page', 1),
            self::LIMIT
        );

        return $this->render('dashboard/content/market_place/supplier/index.html.twig', [
            'store' => $store,
            'suppliers' => $pagination,
            'countries' => Countries::getNames(Locale::getDefault()),
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param ServeStoreInterface $serveStore
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route('/create/{store}', name: 'app_dashboard_market_place_create_supplier', methods: ['GET', 'POST'])]
    public function create(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $user);
        $supplier = new StoreSupplier();

        $form = $this->createForm(SupplerType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form->get('name')->getData();

            $exists = $em->getRepository(StoreSupplier::class)->exists($store, trim($name));

            if (!$exists) {
                $supplier->setStore($store);
                $em->persist($supplier);
                $em->flush();

                $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));

                return $this->redirectToRoute('app_dashboard_market_place_edit_supplier', [
                    'store' => $request->get('store'),
                    'id' => $supplier->getId(),
                ]);
            } else {
                $this->addFlash('danger', json_encode(['message' => $translator->trans('choice.invalid', ['%name%' => $name], 'validators')]));
            }
        }

        return $this->render('dashboard/content/market_place/supplier/_form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param StoreSupplier $supplier
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/edit/{store}/{id}', name: 'app_dashboard_market_place_edit_supplier', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        UserInterface          $user,
        StoreSupplier          $supplier,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $user);

        $form = $this->createForm(SupplerType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supplier->setStore($store);
            $em->persist($supplier);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));
            return $this->redirectToRoute('app_dashboard_market_place_edit_supplier', [
                'store' => $request->get('store'),
                'id' => $supplier->getId(),
            ]);
        }

        return $this->render('dashboard/content/market_place/supplier/_form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param StoreSupplier $supplier
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/delete/{store}/{id}', name: 'app_dashboard_delete_supplier', methods: ['POST'])]
    public function delete(
        Request                $request,
        UserInterface          $user,
        StoreSupplier          $supplier,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $user);
        $token = $request->get('_token');

        if (!$token) {
            $content = $request->getPayload()->all();
            $token = $content['_token'];
        }

        if ($this->isCsrfTokenValid('delete', $token) && !$supplier->getStoreProductSuppliers()->count()) {
            $em->remove($supplier);
            $em->flush();
        }

        return $this->json(['success' => true, 'redirect' => $this->generateUrl('app_dashboard_market_place_store_supplier', ['store' => $store->getId()])]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    #[Route('/xhr-create/{store}', name: 'app_dashboard_market_place_xhr_create_supplier', methods: ['POST'])]
    public function xhrCreate(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
        TranslatorInterface    $translator,
    ): JsonResponse
    {
        $store = $this->store($serveStore, $user);
        $requestGetPost = $request->request->all();
        $responseJson = [];

        if ($requestGetPost && isset($requestGetPost['supplier']['name'])) {
            if ($this->isCsrfTokenValid('create', $requestGetPost['supplier']['_token'])) {
                $supplier = $em->getRepository(StoreSupplier::class)->findOneBy(['store' => $store, 'name' => trim($requestGetPost['supplier']['name'])]);

                if (!$supplier) {
                    $supplier = new StoreSupplier();
                    $supplier->setName($requestGetPost['supplier']['name']);
                    $supplier->setCountry($requestGetPost['supplier']['country']);
                    $supplier->setStore($store);
                    $em->persist($supplier);
                    $em->flush();

                    $responseJson = [
                        'json' => [
                            'id' => "#product_supplier",
                            'option' => [
                                'id' => $supplier->getId(),
                                'name' => $supplier->getName(),
                            ],
                        ],
                    ];
                } else {
                    $responseJson = [
                        'json' => [
                            'constraints' => [
                                'invalid' => $translator->trans('choice.invalid', ['%name%' => $requestGetPost['supplier']['name']], 'validators')
                            ]
                        ]
                    ];
                }
            }
        }

        $response = new JsonResponse($responseJson);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @param UserInterface $user
     * @param ServeStoreInterface $serveStore
     * @return JsonResponse
     */
    #[Route('/xhr-load-countries/{store}', name: 'app_dashboard_market_place_xhr_load_countries', methods: ['GET'])]
    public function xhrLoadCounties(
        UserInterface       $user,
        ServeStoreInterface $serveStore,
    ): JsonResponse
    {

        $countries = [
            'store' => $this->store($serveStore, $user)->getName(),
            'countries' => Countries::getNames(Locale::getDefault())
        ];

        return new JsonResponse($countries);
    }

}