<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\{Attach, User};
use Inno\Entity\MarketPlace\{StoreCoupon, StoreProduct, StoreProductAttach};
use Inno\Form\Type\Dashboard\MarketPlace\ProductType;
use Inno\Security\Voter\ProductVoter;
use Inno\Service\FileUploader;
use Inno\Service\MarketPlace\Dashboard\Product\Interface\CopyServiceInterface;
use Inno\Service\MarketPlace\Dashboard\Product\Interface\ServeProductInterface;
use Inno\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface;
use Inno\Service\MarketPlace\StoreTrait;
use Inno\Service\Validator\Interface\ImageValidatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/market-place/product')]
class ProductController extends AbstractController
{
    use StoreTrait;

    /**
     * @param Request $request
     * @param ServeProductInterface $product
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/{store}/{search}', name: 'app_dashboard_market_place_market_product', defaults: ['search' => null])]
    public function index(
        Request               $request,
        ServeProductInterface $product,
        ServeStoreInterface   $serveStore,
    ): Response
    {
        $page = $request->get('page');
        $page = is_numeric($page) ? (int)$page : 1;

        if ($page) {
            $this->offset = (self::LIMIT - 1) * ($page - 1);
        }

        $store = $this->store($serveStore, $this->getUser());
        $products = $product->index($store, $request->query->get('search'), $this->offset, self::LIMIT - 1);

        return $this->render('dashboard/content/market_place/product/index.html.twig', [
            'store' => $store,
            'currency' => $serveStore->currency(),
            'rows' => $products['rows'],
            'pages' => ceil($products['rows'] / (self::LIMIT - 1)),
            'products' => $products['result'],
            'coupons' => $product->coupon($store, StoreCoupon::COUPON_PRODUCT),
        ]);
    }

    /**
     * @param Request $request
     * @param StoreProduct $product
     * @param CopyServiceInterface $service
     * @return Response
     */
    #[Route('/copy/{store}/{id}', name: 'app_dashboard_market_place_product_copy')]
    #[IsGranted(ProductVoter::EDIT, subject: 'product', statusCode: Response::HTTP_FORBIDDEN)]
    public function copy(
        Request              $request,
        StoreProduct         $product,
        CopyServiceInterface $service
    ): Response
    {
        $ready = false;

        if (!$ready) {
            throw $this->createAccessDeniedException();
        }
        $service->copyProduct($product->getId());
        return $this->redirectToRoute('app_dashboard_market_place_market_product', ['store' => $request->get('store')]);
    }

    /**
     * @param Request $request
     * @param StoreProduct $product
     * @param TranslatorInterface $translator
     * @param ServeProductInterface $serveProduct
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/edit/{store}/{id}/{tab}', name: 'app_dashboard_market_place_edit_product', methods: ['GET', 'POST'])]
    #[IsGranted(ProductVoter::EDIT, subject: 'product', statusCode: Response::HTTP_FORBIDDEN)]
    public function edit(
        Request               $request,
        StoreProduct          $product,
        TranslatorInterface   $translator,
        ServeProductInterface $serveProduct,
        ServeStoreInterface   $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $form = $this->createForm(ProductType::class, $product);
        $handle = $serveProduct->supports($form);

        if ($handle->isSubmitted() && $handle->isValid()) {
            $product = $serveProduct->update($store, $product);

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));

            return $this->redirectToRoute('app_dashboard_market_place_edit_product', [
                'store' => $request->get('store'),
                'id' => $product->getId(),
                'tab' => $request->get('tab'),
            ]);
        }

        return $this->render('dashboard/content/market_place/product/_form.html.twig', [
            'form' => $form,
            'errors' => $form->getErrors(true),
            'product' => $product,
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param ServeProductInterface $serveProduct
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/create/{store}/{tab}', name: 'app_dashboard_market_place_create_product', methods: ['GET', 'POST'])]
    public function create(
        Request               $request,
        TranslatorInterface   $translator,
        ServeProductInterface $serveProduct,
        ServeStoreInterface   $serveStore,
    ): Response
    {
        $user = $this->getUser();
        $store = $this->store($serveStore, $user);
        $product = new StoreProduct();
        $requestStore = $request->get('store');

        $form = $this->createForm(ProductType::class, $product);
        $handle = $serveProduct->supports($form);

        if (in_array(User::ROLE_ADMIN, $user->getRoles())) {
            //$store = $this->container->get(EntityManagerInterface::class)->getRepository(Store::class)->find($requestStore)    ;
        }

        if ($handle->isSubmitted() && $handle->isValid()) {
            $product = $serveProduct->create($store, $product);
            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));
            return $this->redirectToRoute('app_dashboard_market_place_edit_product', [
                'store' => $requestStore,
                'id' => $product->getId(),
                'tab' => $request->get('tab'),
            ]);
        }

        return $this->render('dashboard/content/market_place/product/_form.html.twig', [
            'form' => $form,
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param Request $request
     * @param StoreProduct $product
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     * @throws \Exception
     */
    #[Route('/delete/{store}/{id}', name: 'app_dashboard_delete_product', methods: ['POST'])]
    public function delete(
        Request                $request,
        StoreProduct           $product,
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

        if ($this->isCsrfTokenValid('delete', $token)) {
            $date = new \DateTime('@' . strtotime('now'));
            $product->setDeletedAt($date);
            $em->persist($product);
            $em->flush();
        }
        return $this->json(['message' => 'success', 'redirect' => $request->headers->get('referer')]);
        //return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @param Request $request
     * @param StoreProduct $product
     * @param EntityManagerInterface $em
     * @param ServeStoreInterface $serveStore
     * @return Response
     */
    #[Route('/restore/{store}/{id}', name: 'app_dashboard_restore_product')]
    public function restore(
        Request                $request,
        StoreProduct           $product,
        EntityManagerInterface $em,
        ServeStoreInterface    $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $product->setDeletedAt(null);
        $em->persist($product);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $em
     * @param SluggerInterface $slugger
     * @param CacheManager $cacheManager
     * @param ParameterBagInterface $params
     * @param ImageValidatorInterface $imageValidator
     * @return Response
     */
    #[Route('/attach/{store}/{id}', name: 'app_dashboard_product_attach')]
    public function attach(
        Request                 $request,
        TranslatorInterface     $translator,
        EntityManagerInterface  $em,
        SluggerInterface        $slugger,
        CacheManager            $cacheManager,
        ParameterBagInterface   $params,
        ImageValidatorInterface $imageValidator,
    ): Response
    {
        $file = $request->files->get('file');
        $id = $request->get('id');
        $product = $em->getRepository(StoreProduct::class)->findOneBy(['id' => $id]);

        $attach = null;

        if ($file) {
            $validate = $imageValidator->validate($file, $translator);

            if ($validate->has(0)) {
                return $this->json([
                    'message' => $validate->get(0)->getMessage(),
                    'picture' => null,
                ]);
            }

            $fileUploader = new FileUploader($this->getTargetDir($product->getId(), $params), $slugger, $em);

            try {
                $attach = $fileUploader->upload($file)->handle();
            } catch (\Exception $ex) {
                return $this->json([
                    'success' => false,
                    'message' => $ex->getMessage(),
                    'picture' => null,
                ]);
            }

            $productAttachment = new StoreProductAttach();
            $productAttachment->setProduct($product)
                ->setAttach($attach);

            $em->persist($productAttachment);
            $em->flush();
        }

        $url = $this->getTargetDir($product->getId(), $params);
        $picture = $cacheManager->getBrowserPath(parse_url($url . '/' . $attach->getName(), PHP_URL_PATH), 'product_preview', [], null);

        return $this->json([
            'success' => true,
            'message' => $translator->trans('entry.picture.default'),
            'picture' => $picture,
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param CacheManager $cacheManager
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $params
     * @return Response
     */
    #[Route('/attach/remove/{store}/{id}', name: 'app_dashboard_product_attach_remove', methods: ['POST'])]
    public function remove(
        Request                $request,
        TranslatorInterface    $translator,
        CacheManager           $cacheManager,
        EntityManagerInterface $em,
        ParameterBagInterface  $params
    ): Response
    {
        $id = $request->getPayload()->get('id');
        $attach = $em->getRepository(Attach::class)->find($id);
        $product = $em->getRepository(StoreProduct::class)->find($request->get('id'));
        $productAttach = $em->getRepository(StoreProductAttach::class)->findOneBy(['attach' => $attach, 'product' => $product]);

        $fs = new Filesystem();
        $oldFile = $this->getTargetDir($product->getId(), $params) . '/' . $attach->getName();

        foreach (['product_preview', 'product_view'] as $filter) {
            if ($cacheManager->isStored($oldFile, $filter)) {
                $cacheManager->remove($oldFile, $filter);
            }
        }

        if ($fs->exists($oldFile)) {
            $fs->remove($oldFile);
        }

        $productAttach->setAttach(null)->setProduct(null);

        $em->remove($productAttach);
        $em->remove($attach);
        $em->flush();

        return $this->json(['message' => $translator->trans('user.picture.delete'), 'file' => $attach->getName()]);
    }

    /**
     * @param int|null $id
     * @param ParameterBagInterface $params
     * @return string
     */
    private function getTargetDir(?int $id, ParameterBagInterface $params): string
    {
        return sprintf('%s/%d', $params->get('product_storage_dir'), $id);
    }
}
