<?php

namespace App\Controller\Dashboard\MarketPlace\Store;

use App\Entity\MarketPlace\StoreSupplier;
use App\Form\Type\Dashboard\MarketPlace\SupplerType;
use App\Service\MarketPlace\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/{store}', name: 'app_dashboard_market_place_store_supplier')]
    public function index(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): Response
    {
        $store = $this->store($request, $user, $em);
        $suppliers = $em->getRepository(StoreSupplier::class)->suppliers($store, $request->query->get('search'));

        return $this->render('dashboard/content/market_place/supplier/index.html.twig', [
            'store' => $store,
            'suppliers' => $suppliers,
            'countries' => Countries::getNames(Locale::getDefault()),
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/create/{store}', name: 'app_dashboard_market_place_create_supplier', methods: ['GET', 'POST'])]
    public function create(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
    ): Response
    {
        $store = $this->store($request, $user, $em);
        $supplier = new StoreSupplier();

        $form = $this->createForm(SupplerType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supplier->setStore($store);
            $em->persist($supplier);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));
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
     * @param TranslatorInterface $translator
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/edit/{store}/{id}', name: 'app_dashboard_market_place_edit_supplier', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        UserInterface          $user,
        StoreSupplier          $supplier,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
    ): Response
    {
        $store = $this->store($request, $user, $em);

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
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/delete/{store}/{id}', name: 'app_dashboard_delete_supplier', methods: ['POST'])]
    public function delete(
        Request                $request,
        UserInterface          $user,
        StoreSupplier          $supplier,
        EntityManagerInterface $em,
    ): Response
    {
        $store = $this->store($request, $user, $em);
        $token = $request->get('_token');

        if (!$token) {
            $content = $request->getPayload()->all();
            $token = $content['_token'];
        }

        if ($this->isCsrfTokenValid('delete', $token) && !$supplier->getStoreProductSuppliers()->count()) {
            $em->remove($supplier);
            $em->flush();
        }

        return $this->redirectToRoute('app_dashboard_market_place_market_supplier', ['store' => $store->getId()]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/xhr-create/{store}', name: 'app_dashboard_market_place_xhr_create_supplier', methods: ['POST'])]
    public function xhrCreate(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $store = $this->store($request, $user, $em);
        $requestGetPost = $request->request->all();
        $responseJson = [];

        if ($requestGetPost && isset($requestGetPost['supplier']['name'])) {
            if ($this->isCsrfTokenValid('create', $requestGetPost['supplier']['_token'])) {
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
            }
        }
        $response = new JsonResponse($responseJson);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/xhr-load-countries/{store}', name: 'app_dashboard_market_place_xhr_load_countries', methods: ['GET'])]
    public function xhrLoadCounties(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $store = $this->store($request, $user, $em);

        $countries = [
            'store' => $store->getName(),
            'countries' => Countries::getNames(Locale::getDefault())
        ];

        return new JsonResponse($countries);
    }

}