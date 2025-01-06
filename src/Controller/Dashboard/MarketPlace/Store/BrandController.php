<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException};
use Inno\Entity\MarketPlace\StoreBrand;
use Inno\Form\Type\Dashboard\MarketPlace\BrandType;
use Inno\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface;
use Inno\Service\MarketPlace\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/market-place/brand')]
class BrandController extends AbstractController
{
    use StoreTrait;

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/{store}', name: 'app_dashboard_market_place_store_brand')]
    public function index(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $user);
        $brands = $em->getRepository(StoreBrand::class)->brands($store, $request->query->get('search'));

        $pagination = $this->paginator->paginate(
            $brands,
            $request->query->getInt('page', 1),
            self::LIMIT
        );

        return $this->render('dashboard/content/market_place/brand/index.html.twig', [
            'store' => $store,
            'brands' => $pagination,
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
    #[Route('/create/{store}', name: 'app_dashboard_market_place_create_brand', methods: ['GET', 'POST'])]
    public function create(
        Request                $request,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $brand = new StoreBrand();

        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form->get('name')->getData();

            $exists = $em->getRepository(StoreBrand::class)->exists($store, trim($name));

            if (!$exists) {
                $brand->setStore($store);
                $em->persist($brand);
                $em->flush();

                $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));
                return $this->redirectToRoute('app_dashboard_market_place_edit_brand', [
                    'store' => $request->get('store'),
                    'id' => $brand->getId(),
                ]);
            } else {
                $this->addFlash('danger', json_encode(['message' => $translator->trans('choice.invalid', ['%name%' => $name], 'validators')]));
            }
        }

        return $this->render('dashboard/content/market_place/brand/_form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/edit/{store}/{id}', name: 'app_dashboard_market_place_edit_brand', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $brand = $em->getRepository(StoreBrand::class)->find($request->get('id'));
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $brand->setStore($store);
            $em->persist($brand);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));
            return $this->redirectToRoute('app_dashboard_market_place_edit_brand', [
                'store' => $request->get('store'),
                'id' => $brand->getId(),
            ]);
        }

        return $this->render('dashboard/content/market_place/brand/_form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param StoreBrand $brand
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/delete/{store}/{id}', name: 'app_dashboard_delete_brand', methods: ['POST'])]
    public function delete(
        Request                $request,
        UserInterface          $user,
        StoreBrand             $brand,
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

        if ($this->isCsrfTokenValid('delete', $token) && !$brand->getStoreProductBrands()->count()) {
            $em->remove($brand);
            $em->flush();
        }

        return $this->json(['success' => true, 'redirect' => $this->generateUrl('app_dashboard_market_place_store_brand', ['store' => $store->getId()])]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @param TranslatorInterface $translator
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    #[Route('/xhr_create/{store}', name: 'app_dashboard_market_place_xhr_create_brand', methods: ['POST'])]
    public function xshCreate(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
        TranslatorInterface    $translator,
    ): JsonResponse
    {
        $store = $this->store($serveStore, $user);
        $requestGetPost = $request->get('brand');
        $responseJson = [];

        if ($requestGetPost && isset($requestGetPost['name'])) {
            if ($this->isCsrfTokenValid('create', $requestGetPost['_token'])) {
                $brand = $em->getRepository(StoreBrand::class)->exists($store, trim($requestGetPost['name']));

                if (!$brand) {
                    $brand = new StoreBrand();
                    $brand->setName($requestGetPost['name']);
                    $brand->setStore($store);
                    $em->persist($brand);
                    $em->flush();

                    $responseJson = [
                        'json' => [
                            'id' => "#product_brand",
                            'option' => [
                                'id' => $brand->getId(),
                                'name' => $brand->getName(),
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