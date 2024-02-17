<?php

namespace App\Controller\Dashboard;

use App\Entity\MarketPlace\Market;
use App\Entity\User;
use App\Service\Dashboard;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
class IndexController extends AbstractController
{

    use Dashboard;

    /**
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('', name: 'app_dashboard')]
    public function index(
        UserInterface          $user,
        EntityManagerInterface $em,
    ): Response
    {
        if(in_array('ROLE_CUSTOMER', $user->getRoles(), true)) {
            $this->createAccessDeniedException('Access Denied.');
        }
        $markets = $em->getRepository(Market::class)->findBy($this->criteria($user, null, 'owner'));
        return $this->render('dashboard/content/index.html.twig', $this->navbar() + [
                'markets' => $markets,
            ]);
    }
}
