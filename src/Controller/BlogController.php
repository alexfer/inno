<?php

namespace Inno\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Inno\Controller\Trait\ControllerTrait;
use Inno\Entity\{Category, Entry};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/blog')]
class BlogController extends AbstractController
{
    use ControllerTrait;

    /**
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route('', name: 'app_blog')]
    public function index(): Response
    {
        $primary = $this->em->getRepository(Entry::class)->primary(Entry::TYPE['Blog'], 1);
        $timeline = $this->em->getRepository(Entry::class)->timeline('blog');
        $authors = $this->em->getRepository(Entry::class)->findEntriesByAuthors('blog', 4);
        shuffle($authors);

        return $this->render('blog/primary.html.twig', [
            'primary' => $primary,
            'timeline' => $timeline,
            'authors' => $authors,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route('/category', name: 'app_blog_category_index')]
    #[Route('/category/{slug}', name: 'app_blog_category')]
    #[Route('/date/{date}', name: 'app_blog_date')]
    public function mixed(Request $request): Response
    {
        $slug = $request->get('slug');
        $date = $request->get('date');
        $categoryRepository = $this->em->getRepository(Category::class);
        $entries = $this->em->getRepository(Entry::class)->findEntriesByCondition($slug, $date, 'blog');
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        $categories = $categoryRepository->findBy([], ['slug' => 'asc']);

        return $this->render('blog/index.html.twig', [
            'entries' => $entries,
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/{slug}', name: 'app_blog_view')]
    public function view(
        Request             $request,
        TranslatorInterface $translator,
    ): Response
    {
        $entry = $this->em->getRepository(Entry::class)->findOneBy(['slug' => $request->get('slug')]);

        if (is_null($entry)) {
            throw $this->createNotFoundException($translator->trans('http_error_404.description'));
        }

        return $this->render('blog/view.html.twig', [
            'entry' => $entry,
        ]);
    }
}
