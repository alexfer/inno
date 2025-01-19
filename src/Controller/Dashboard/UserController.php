<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\User;
use Inno\Form\Type\User\ChangePasswordProfileType;
use Inno\Service\MarketPlace\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response,};
use Symfony\Component\Intl\{Countries, Locale,};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/user/overview')]
#[IsGranted('ROLE_ADMIN', message: 'Access denied.')]
class UserController extends AbstractController
{

    use StoreTrait;

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('', name: 'app_dashboard_user', methods: ['GET'])]
    public function index(
        Request                $request,
        EntityManagerInterface $em,
    ): Response
    {
        $query = $request->query->get('search');
        $users = $em->getRepository(User::class)->fetch($query, self::LIMIT, $this->offset);
        return $this->render('dashboard/content/user/index.html.twig', [
            'users' => $users['results'],
            'rows' => reset($users['rows'][0]),
            'pages' => ceil(reset($users['rows'][0]) / self::LIMIT),

        ]);
    }

    /**
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param EntityManagerInterface $em
     * @param User $user
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/details/{id}/{tab}', name: 'app_dashboard_details_user', methods: ['GET', 'POST'])]
    public function details(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $em,
        User                        $user,
        TranslatorInterface         $translator,
    ): Response
    {
        $country = $user->getUserDetails()->getCountry();

        $form = $this->createForm(ChangePasswordProfileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($encodedPassword);
            $em->flush();
            $this->addFlash('success', json_encode(['message' => $translator->trans('user.password.changed')]));
            return $this->redirectToRoute('app_dashboard_details_user', ['id' => $user->getId(), 'tab' => $request->get('tab')]);
        }

        return $this->render('dashboard/content/user/details.html.twig', [
            'user' => $user,
            'form' => $form,
            'country' => $country ? Countries::getNames(Locale::getDefault())[$country] : null,
        ]);
    }

    /**
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param User $user
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/customer/{id}/{tab}', name: 'app_dashboard_customer_user', methods: ['GET', 'POST'])]
    public function customer(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        User                        $user,
        EntityManagerInterface      $em,
        TranslatorInterface         $translator,
    ): Response
    {
        $customer = $em->getRepository(StoreCustomer::class)->findOneBy(['member' => $request->get('id')]);
        $country = $customer->getCountry();
        $form = $this->createForm(ChangePasswordProfileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($encodedPassword);
            $em->flush();
            $this->addFlash('success', json_encode(['message' => $translator->trans('user.password.changed')]));
            return $this->redirectToRoute('app_dashboard_customer_user', ['id' => $user->getId(), 'tab' => $request->get('tab')]);
        }

        return $this->render('dashboard/content/user/customer.html.twig', [
            'customer' => $customer,
            'user' => $user,
            'form' => $form,
            'country' => $country ? Countries::getNames(Locale::getDefault())[$country] : null,
        ]);
    }

    #[Route('/secure/{id}/{tab}/{part}/{action}', name: 'app_dashboard_secure_user', methods: ['GET'])]
    public function detail(
        Request                $request,
        TranslatorInterface    $translator,
        User                   $user,
        EntityManagerInterface $em,
    ): Response
    {
        if ($request->get('action') == 'lock') {
            $user->setDeletedAt(new \DateTime());
        } else {
            $user->setDeletedAt(null);
        }
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', json_encode(['message' => $translator->trans(sprintf('user.%s.changed', $request->get('action')))]));

        $route = 'app_dashboard_customer_user';
        if ($request->get('part') == 'details') {
            $route = 'app_dashboard_details_user';
        }
        return $this->redirectToRoute($route, ['id' => $user->getId(), 'tab' => $request->get('tab')]);
    }
}
