<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{Store,
    StoreCarrier,
    StoreCarrierStore,
    StoreOptions,
    StorePaymentGateway,
    StorePaymentGatewayStore,
    StoreSocial};
use Inno\Form\Type\Dashboard\MarketPlace\StoreType;
use Inno\Service\FileUploader;
use Knp\Component\Pager\PaginatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/market-place')]
class StoreController extends AbstractController
{

    public final const int LIMIT = 25;

    private int $offset = 0;

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    #[Route('/store', name: 'app_dashboard_market_place_market')]
    public function index(
        Request                $request,
        PaginatorInterface     $paginator,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): Response
    {

        $page = $request->query->getInt('page', 1);

        if ($page) {
            $this->offset = (self::LIMIT - 1) * ($page - 1);
        }

        $pagination = [];

        $stores = $em->getRepository(Store::class)->backdrop_stores($user, $this->offset, self::LIMIT);

        if ($stores['result']) {
            $pagination = $paginator->paginate(
                $stores['result'],
                $page,
                self::LIMIT - 1
            );
        }

        return $this->render('dashboard/content/market_place/store/index.html.twig', [
            'stores' => $pagination,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/store/search/{query?}', name: 'app_dashboard_market_place_search_store')]
    public function search(
        Request                $request,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $repository = $em->getRepository(Store::class);

        if ($this->isGranted('ROLE_ADMIN')) {
            $stores = $repository->search($request->get('query'));
        } else {
            $stores = $repository->searchByOwner($request->get('query'), $this->getUser());
        }

        $result = [];

        foreach ($stores['data'] ?? [] as $store) {
            $result[] = [
                'id' => $store['id'],
                'name' => $store['name'],
                'url' => $this->generateUrl('app_dashboard_market_place_market_product', ['store' => $store['id']]),
            ];
        }

        return $this->json([
            'result' => $result,
        ], Response::HTTP_OK);
    }

    /**
     * @param Store $store
     * @return Response
     */
    #[Route('/store/{website}', name: 'app_dashboard_market_place_market_redirect')]
    public function redirectTo(Store $store): Response
    {
        return $this->redirect($store->getUrl());
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SluggerInterface $slugger
     * @param TranslatorInterface $translator
     * @param ParameterBagInterface $params
     * @return Response
     * @throws \Exception
     */
    #[Route('/create/{tab}', name: 'app_dashboard_market_place_create_market')]
    public function create(
        Request                $request,
        EntityManagerInterface $em,
        SluggerInterface       $slugger,
        TranslatorInterface    $translator,
        ParameterBagInterface  $params,
    ): Response
    {
        $store = new Store();
        $form = $this->createForm(StoreType::class, $store);

        $stores = $this->getUser()->getStores()->count();

        if (!$stores) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $stores = $em->getRepository(Store::class)->findBy(['name' => $form->get('name')->getData()]);

                if ($stores) {
                    $this->addFlash('danger', $translator->trans('slug.unique', [
                        '%name%' => 'Storage name',
                        '%value%' => $form->get('name')->getData(),
                    ], 'validators'));
                    return $this->redirectToRoute('app_dashboard_market_place_create_market', ['tab' => $request->get('tab')]);
                }

                $slug = $slugger->slug($form->get('name')->getData())->lower()->toString();

                $store->setOwner($this->getUser())->setSlug($slug);
                $em->persist($store);
                $em->flush();

                $file = $form->get('logo')->getData();

                if ($file) {
                    $fileUploader = new FileUploader($this->getTargetDir($params, $store->getId()), $slugger, $em);

                    try {
                        $attach = $fileUploader->upload($file)->handle($store);
                    } catch (\Exception $ex) {
                        throw new \Exception($ex->getMessage());
                    }

                    $store->setAttach($attach);
                }

                foreach (StoreSocial::NAME as $value) {
                    $social = new StoreSocial();
                    $social->setSourceName($value)
                        ->setSource("https://{$value}.com");
                    $store->addStoreSocial($social);
                    $em->persist($social);
                }

                $requestGateways = $form->get('gateway')->getData();

                $criteria = ['active' => true];

                if ($requestGateways) {
                    $criteria = ['active' => true, 'id' => $requestGateways];
                }

                $paymentGateways = $em->getRepository(StorePaymentGateway::class)->findBy($criteria);

                foreach ($paymentGateways as $gateway) {
                    $paymentGatewayStore = new StorePaymentGatewayStore();
                    $paymentGatewayStore->setStore($store)
                        ->setGateway($gateway)
                        ->setActive(count($criteria) > 1);
                    $em->persist($paymentGatewayStore);
                }
                unset($criteria);

                $requestCarriers = $form->get('carrier')->getData();
                $criteria = ['is_enabled' => true];

                if ($requestCarriers) {
                    $criteria = ['is_enabled' => true, 'id' => $requestCarriers];
                }

                $carriers = $em->getRepository(StoreCarrier::class)->findBy($criteria);

                foreach ($carriers as $carrier) {
                    $carrierStore = new StoreCarrierStore();
                    $carrierStore->setStore($store)
                        ->setCarrier($carrier)
                        ->setActive(count($criteria) > 1);
                    $em->persist($carrierStore);
                }

                $url = $form->get('website')->getData();

                if ($url) {
                    $parse = parse_url($url);
                    $store->setUrl($url)->setWebsite($parse['host']);
                }

                $store->setCc(json_encode($form->get('cc')->getData()));

                $options = new StoreOptions();
                $options->setStore($store)->setBackupSchedule(0);
                $em->persist($options);

                $em->persist($store);
                $em->flush();

                $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));

                return $this->redirectToRoute('app_dashboard_market_place_edit_market', ['id' => $store->getId(), 'tab' => $request->get('tab')]);
            }
        } else {
            $this->addFlash('danger', $translator->trans('restriction.access', ['email' => $params->get('app.notifications.email_sender')]));
        }

        return $this->render('dashboard/content/market_place/store/_form.html.twig', [
            'form' => $form,
            'store' => $store,
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param Request $request
     * @param Store $store
     * @param EntityManagerInterface $em
     * @param SluggerInterface $slugger
     * @param TranslatorInterface $translator
     * @param ParameterBagInterface $params
     * @param CacheManager $cacheManager
     * @return Response
     * @throws \Exception
     */
    #[Route('/edit/{id}/{tab}', name: 'app_dashboard_market_place_edit_market', defaults: ['tabs' => 'details'], methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        Store                  $store,
        EntityManagerInterface $em,
        SluggerInterface       $slugger,
        TranslatorInterface    $translator,
        ParameterBagInterface  $params,
        CacheManager           $cacheManager,
    ): Response
    {
        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('logo')->getData();

            $socials = $store->getStoreSocials()->toArray();
            $sources = $form->get('sourceName')->getData();

            foreach ($socials as $social) {
                $source = $form->get($social->getSourceName())->getData();
                $social->setSource($source)
                    ->setActive(in_array($social->getSourceName(), $sources));
                $em->persist($social);
            }

            if ($file) {
                $fileUploader = new FileUploader($this->getTargetDir($params, $store->getId()), $slugger, $em);

                $oldAttach = $store->getAttach();

                if ($oldAttach) {
                    $em->remove($oldAttach);
                    $store->setAttach(null);

                    $fs = new Filesystem();
                    $oldFile = $this->getTargetDir($params, $store->getId()) . '/' . $oldAttach->getName();

                    $cacheManager->remove($oldFile, 'store_bg');

                    if ($fs->exists($oldFile)) {
                        $fs->remove($oldFile);
                    }
                }

                try {
                    $attach = $fileUploader->upload($file)->handle($store);
                } catch (\Exception $ex) {
                    throw new \Exception($ex->getMessage());
                }

                $store->setAttach($attach);
            }

            $carriers = $form->get('carrier')->getData();

            if ($carriers) {
                $em = $this->resetCarriers($store, $em);
                foreach ($carriers as $carrier) {
                    $carrierStore = $em->getRepository(StoreCarrierStore::class)
                        ->findOneBy([
                            'carrier' => $carrier,
                            'store' => $store,
                        ]);
                    if (!$carrierStore) {
                        $newCarrier = new StoreCarrierStore();
                        $newCarrier->setCarrier($em->getRepository(StoreCarrier::class)->find($carrier))
                            ->setStore($store)
                            ->setActive(true);
                        $em->persist($newCarrier);
                    } else {
                        $carrierStore->setActive(true);
                        $em->persist($carrierStore);
                    }
                }
            } else {
                $em = $this->resetCarriers($store, $em);
                $em->flush();
            }

            $gateways = $form->get('gateway')->getData();

            if ($gateways) {
                $em = $this->resetGateways($store, $em);
                foreach ($gateways as $gateway) {
                    $paymentGateway = $em->getRepository(StorePaymentGatewayStore::class)
                        ->findOneBy([
                            'gateway' => $gateway,
                            'store' => $store,
                        ]);
                    if (!$paymentGateway) {
                        $newGateway = new StorePaymentGatewayStore();
                        $newGateway->setGateway($em->getRepository(StorePaymentGateway::class)->find($gateway))
                            ->setStore($store)
                            ->setActive(true);
                        $em->persist($newGateway);
                    } else {
                        $paymentGateway->setActive(true);
                        $em->persist($paymentGateway);
                    }
                }
            } else {
                $em = $this->resetGateways($store, $em);
                $em->flush();
            }

            $url = $form->get('website')->getData();

            if ($url) {
                $parse = parse_url($url);
                $store->setUrl($url)->setWebsite($parse['host']);
            }

            $store->setCc(json_encode($form->get('cc')->getData()));
            $store->getStoreOptions()->setBackupSchedule((int)$form->get('backupSchedule')->getData());

            $em->persist($store);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));

            return $this->redirectToRoute('app_dashboard_market_place_edit_market', ['id' => $store->getId(), 'tab' => $request->get('tab')]);
        }

        return $this->render('dashboard/content/market_place/store/_form.html.twig', [
            'form' => $form,
            'store' => $store,
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param Store $store
     * @param EntityManagerInterface $em
     * @return EntityManagerInterface
     */
    private function resetGateways(Store $store, EntityManagerInterface $em): EntityManagerInterface
    {
        foreach ($store->getStorePaymentGatewayStores() as $gatewayStore) {
            $gatewayStore->setActive(false);
            $em->persist($gatewayStore);
        }
        return $em;
    }

    /**
     * @param Store $store
     * @param EntityManagerInterface $em
     * @return EntityManagerInterface
     */
    private function resetCarriers(Store $store, EntityManagerInterface $em): EntityManagerInterface
    {
        foreach ($store->getStoreCarrierStores() as $carrierStore) {
            $carrierStore->setActive(false);
            $em->persist($carrierStore);
        }
        return $em;
    }

    /**
     * @param Request $request
     * @param Store $store
     * @param EntityManagerInterface $em
     * @return Response
     * @throws \Exception
     */
    #[Route('/delete/{id}', name: 'app_dashboard_delete_market', methods: ['POST'])]
    public function delete(
        Request                $request,
        Store                  $store,
        EntityManagerInterface $em,
    ): Response
    {
        $token = $request->get('_token');

        if (!$token) {
            $content = $request->getPayload()->all();
            $token = $content['_token'];
        }

        if ($this->isCsrfTokenValid('delete', $token)) {
            $products = $store->getProducts();
            $date = new \DateTime('@' . strtotime('now'));
            foreach ($products as $product) {
                $product->setDeletedAt($date);
                $em->persist($product);
            }
            $store->setDeletedAt($date);
            $em->persist($store);
            $em->flush();
        }

        return $this->redirectToRoute('app_dashboard_market_place_market');
    }

    /**
     *
     * @param Store $store
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/restore/{id}', name: 'app_dashboard_restore_market')]
    public function restore(
        Store                  $store,
        EntityManagerInterface $em,
    ): Response
    {
        $products = $store->getProducts();
        foreach ($products as $product) {
            $product->setDeletedAt(null);
            $em->persist($product);
        }

        $store->setDeletedAt(null);
        $em->persist($store);
        $em->flush();

        return $this->redirectToRoute('app_dashboard_market_place_market');
    }

    /**
     * @param ParameterBagInterface $params
     * @param int $id
     * @return string
     */
    private function getTargetDir(ParameterBagInterface $params, int $id): string
    {
        return sprintf('%s/%d', $params->get('market_storage_logo'), $id);
    }

}
