<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\Type\User\DetailsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{
    Request,
    Response,
};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Form;

class RegistrationController extends AbstractController
{

    /**
     * 
     * @param Request $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
            Request $request,
            UserPasswordHasherInterface $userPasswordHasher,
            EntityManagerInterface $em,
    ): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('app_index');
        }

        $user = new User();
        $details = new \App\Entity\UserDetails();

        $form = $this->createForm(DetailsType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                    $userPasswordHasher->hashPassword(
                            $user,
                            $form->get('plainPassword')->getData()
                    )
            );

            $em->persist($user);

            $details->setFirstName($form->get('first_name')->getData())
                    ->setLastName($form->get('last_name')->getData())
                    ->setUser($user);

            $em->persist($details);
            $em->flush();

            return $this->redirectToRoute('app_register_success');
        }

        return $this->render('registration/register.html.twig', [
                    'errors' => $this->getErrorMessages($form),
                    'form' => $form->createView(),
        ]);
    }

    /**
     * 
     * @return Response
     */
    #[Route('/register/success', name: 'app_register_success')]
    public function success(): Response
    {
        return $this->render('registration/success.html.twig', []);
    }

    /**
     * 
     * @param Form $form
     * @return array
     */
    public function getErrorMessages(Form $form): array
    {

        $errors = [];

        foreach ($form->all() as $child) {
            foreach ($child->getErrors() as $error) {
                $name = $child->getName();
                $errors[$name]['message'] = $error->getMessage();
            }
        }

        return $errors;
    }
}