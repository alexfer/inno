<?php

namespace App\Controller\Dashboard\MarketPlace\Market;

use App\Entity\MarketPlace\Market;
use App\Form\Type\Dashboard\MarketPlace\MarketType;
use App\Repository\MarketPlace\MarketRepository;
use App\Service\Navbar;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/market-place')]
class MarketController extends AbstractController
{
    use Navbar;

    /**
     * @param UserInterface $user
     * @param MarketRepository $marketRepository
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/market', name: 'app_dashboard_market_place_market')]
    public function index(
        UserInterface    $user,
        MarketRepository $marketRepository,
    ): Response
    {

        $entries = $marketRepository->findBy(['owner' => $user], ['created_at' => 'desc']);

        return $this->render('dashboard/content/market_place/market/index.html.twig', $this->build($user) + [
                'entries' => $entries,
            ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserInterface $user
     * @param SluggerInterface $slugger
     * @param TranslatorInterface $translator
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/create', name: 'app_dashboard_market_place_create_market')]
    public function create(
        Request                $request,
        EntityManagerInterface $em,
        UserInterface          $user,
        SluggerInterface       $slugger,
        TranslatorInterface    $translator,
    ): Response
    {

        $entry = new Market();
        $form = $this->createForm(MarketType::class, $entry);

        if (!$user->getMarkets()->count()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entry->setOwner($user)->setSlug($slugger->slug($form->get('name')->getData())->lower());
                $em->persist($entry);
                $em->flush();

                $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));

                return $this->redirectToRoute('app_dashboard_market_place_edit_market', ['id' => $entry->getId()]);
            }
        }

        return $this->render('dashboard/content/market_place/market/_form.html.twig', $this->build($user) + [
                'form' => $form,
            ]);
    }

    /**
     * @param Request $request
     * @param Market $entry
     * @param EntityManagerInterface $em
     * @param UserInterface $user
     * @param TranslatorInterface $translator
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/edit/{id}', name: 'app_dashboard_market_place_edit_market', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        Market                 $entry,
        EntityManagerInterface $em,
        UserInterface          $user,
        TranslatorInterface    $translator,
    ): Response
    {
        $form = $this->createForm(MarketType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entry);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));

            return $this->redirectToRoute('app_dashboard_market_place_edit_market', ['id' => $entry->getId()]);
        }

        return $this->render('dashboard/content/market_place/market/_form.html.twig', $this->build($user) + [
                'form' => $form,
            ]);
    }


}