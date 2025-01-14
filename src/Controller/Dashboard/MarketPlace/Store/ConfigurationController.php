<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Inno\Entity\MarketPlace\StoreCarrier;
use Inno\Entity\MarketPlace\StorePaymentGateway;
use Inno\Form\Type\Dashboard\MarketPlace\CarrierType;
use Inno\Form\Type\Dashboard\MarketPlace\PaymentGatewayType;
use Inno\Service\MarketPlace\Dashboard\Configuration\Interface\ConfigurationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/config/setup')]
#[IsGranted('ROLE_ADMIN', message: 'Access denied.')]
class ConfigurationController extends AbstractController
{
    /**
     * @param ConfigurationServiceInterface $config
     * @return Response
     */
    #[Route('/{tab?}', name: 'app_dashboard_config', methods: ['GET'])]
    public function index(ConfigurationServiceInterface $config): Response
    {
        $pgForm = $this->createForm(PaymentGatewayType::class, new StorePaymentGateway());
        $carrierForm = $this->createForm(CarrierType::class, new StoreCarrier());

        $collection = $config->collection();

        return $this->render('dashboard/content/market_place/config/index.html.twig', [
            'carriers' => $collection['carriers'],
            'pgForm' => $pgForm->createView(),
            'carrierForm' => $carrierForm->createView(),
            'paymentGateways' => $collection['payment_gateways'],
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param ConfigurationServiceInterface $configuration
     * @return Response
     */
    #[Route('/{target}/{id}', name: 'app_dashboard_config_remove', methods: ['DELETE'])]
    public function remove(
        Request                       $request,
        TranslatorInterface           $translator,
        ConfigurationServiceInterface $configuration,
    ): Response
    {
        $target = $request->get('target');
        $id = (int)$request->get('id');

        $service = $configuration->take($target);

        if (!$service->remove($id)) {
            return $this->json(['success' => false, 'error' => $translator->trans('user.entry.cant_delete')]);
        }

        return $this->json(['success' => true, 'message' => $translator->trans('user.entry.deleted')], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param ConfigurationServiceInterface $configuration
     * @return Response
     */
    #[Route('/{target}/{id}', name: 'app_dashboard_config_change', methods: ['GET', 'PUT'])]
    public function change(
        Request                       $request,
        TranslatorInterface           $translator,
        ConfigurationServiceInterface $configuration,
    ): Response
    {

        $id = (int)$request->get('id');
        $inputs = $request->getPayload()->all();
        $target = $request->get('target');

        $media = null;

        if (in_array($target, ['carrier', 'payment_gateway']) && $request->isMethod('PUT')) {
            $inputs = $inputs[$target];
            if ($inputs['media']) {
                $media = json_decode($inputs['media'], true);
                $media['attach'] = $inputs['attach'];
            }
        }

        $service = $configuration->take($target);

        if ($target === 'carrier') {
            if ($request->isMethod('PUT')) {
                $data = [
                    'name' => $inputs['name'],
                    'description' => $inputs['description'],
                    'linkUrl' => $inputs['linkUrl'],
                    'enabled' => $inputs['enabled'],
                ];

                try {
                    $service->update($id, $data, $media);
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                return $this->json([
                    'success' => true,
                    'message' => $translator->trans('user.entry.updated')]);
            }

            $carrier = $service->find($id, StoreCarrier::class);

            return $this->json([
                $carrier,
                $this->generateUrl('app_dashboard_config_change', [
                    'target' => $target,
                    'id' => $id
                ])
            ], Response::HTTP_OK);
        }

        if ($target === 'payment_gateway') {
            if ($request->isMethod('PUT')) {
                $data = [
                    'name' => $inputs['name'],
                    'summary' => $inputs['summary'],
                    'handlerText' => $inputs['handlerText'],
                    'active' => $inputs['active'],
                ];

                try {
                    $service->update($id, $data, $media);
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                return $this->json([
                    'success' => true,
                    'message' => $translator->trans('user.entry.updated')]);
            }

            $paymentGateway = $service->find($id, StorePaymentGateway::class);

            return $this->json([
                $paymentGateway,
                $this->generateUrl('app_dashboard_config_change', [
                    'target' => $target,
                    'id' => $id
                ])
            ], Response::HTTP_OK);
        }

        return $this->json([
            'success' => true,
            'message' => $translator->trans('user.entry.updated'),
        ], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param ConfigurationServiceInterface $configuration
     * @return Response
     */
    #[Route('/{target}', name: 'app_dashboard_config_save', methods: ['POST'])]
    public function save(
        Request                       $request,
        TranslatorInterface           $translator,
        ConfigurationServiceInterface $configuration,
    ): Response
    {
        $inputs = $request->getPayload()->all();
        $target = $request->get('target');

        $service = $configuration->take($target);
        $carrier = $media = $paymentGateway = null;

        if (in_array($target, ['carrier', 'payment_gateway'])) {
            $inputs = $inputs[$target];
        }

        if ($inputs['media']) {
            $media = json_decode($inputs['media'], true);
            $media['attach'] = $inputs['attach'];
        }

        if ($target === 'payment_gateway') {

            $data = [
                'name' => $inputs['name'],
                'summary' => $inputs['summary'],
                'handlerText' => $inputs['handlerText'],
                'active' => $inputs['active'],
            ];

            try {
                $paymentGateway = $service->create($data, $media);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($target === 'carrier') {

            $data = [
                'name' => $inputs['name'],
                'description' => $inputs['description'],
                'linkUrl' => $inputs['linkUrl'],
                'enabled' => $inputs['enabled'],
            ];

            try {
                $carrier = $service->create($data, $media);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

        }

        $template = sprintf('dashboard/content/market_place/config/template/%s.html.twig', $target);

        return $this->json([
            'success' => true,
            'message' => $translator->trans('user.entry.created'),
            'template' => $this->renderView($template, [
                'carrier' => $carrier,
                'pg' => $paymentGateway,
            ])
        ]);
    }
}
