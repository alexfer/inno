<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Dashboard;
use App\Entity\{
    Entry,
    EntryDetails,
};
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EntryRepository;
use App\Form\Type\Dashboard\EntryDetailsType;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/dashboard/blog')]
class BlogController extends AbstractController
{

    use Dashboard;

    const CHILDRENS = [
        'blog' => [
            'menu.dashboard.overview.blogs' => 'app_dashboard_blog',
            'menu.dashboard.create.blog' => 'app_dashboard_create_blog',
        ],
    ];

    #[Route('', name: self::CHILDRENS['blog']['menu.dashboard.overview.blogs'])]
    public function index(
            EntryRepository $reposiroty,
            UserInterface $user,
    ): Response
    {
        return $this->render('dashboard/content/blog/index.html.twig', $this->build($user) + [
                    'entries' => $reposiroty->findBy($this->criteria($user, ['type' => 'blog']), ['id' => 'desc']),
        ]);
    }

    #[Route('/create', name: self::CHILDRENS['blog']['menu.dashboard.create.blog'])]
    public function create(
            Request $request,
            UserInterface $user,
            EntityManagerInterface $em,
    ): Response
    {
        $entry = new Entry();
        $details = new EntryDetails();

        $form = $this->createForm(EntryDetailsType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entry->setType('blog')
                    ->setUser($user);

            $em->persist($entry);
            $em->flush();

            $details->setTitle($form->get('title')->getData())
                    ->setContent($form->get('content')->getData())
                    ->setEntry($entry);

            $em->persist($details);
            $em->flush();

            return $this->redirectToRoute('app_dashboard_blog');
        }

        return $this->render('dashboard/content/blog/_form.html.twig', $this->build($user) + [
                    'form' => $form,
        ]);
    }
}
