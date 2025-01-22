<?php

namespace Inno\Controller\Dashboard;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\{Attach, Category, Entry, EntryAttachment, EntryCategory, EntryDetails, FileManager};
use Inno\Form\Type\Dashboard\EntryDetailsType;
use Inno\Service\FileUploader;
use Inno\Service\Validator\Interface\ImageValidatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/blog')]
class BlogController extends AbstractController
{

    const array CHILDREN = [
        'blog' => [
            'menu.dashboard.overview.blog' => 'app_dashboard_blog',
            'menu.dashboard.create.blog' => 'app_dashboard_create_blog',
        ],
    ];

    /**
     * @param int|null $id
     * @param ParameterBagInterface $params
     * @return string
     */
    private function getTargetDir(?int $id, ParameterBagInterface $params): string
    {
        $storage = sprintf('%s/picture/', $params->get('entry_storage_dir'));
        return $storage . $id;
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('', name: self::CHILDREN['blog']['menu.dashboard.overview.blog'])]
    public function index(EntityManagerInterface $em): Response
    {
        $entries = $em->getRepository(Entry::class)
            ->findBy(['user' => $this->getUser(), 'type' => Entry::TYPE['Blog']], ['id' => 'desc']);

        return $this->render('dashboard/content/blog/index.html.twig', [
            'entries' => $entries,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SluggerInterface $slugger
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/create', name: self::CHILDREN['blog']['menu.dashboard.create.blog'])]
    public function create(
        Request                $request,
        EntityManagerInterface $em,
        SluggerInterface       $slugger,
        TranslatorInterface    $translator,
    ): Response
    {
        $entry = new Entry();

        $form = $this->createForm(EntryDetailsType::class, $entry);
        $form->handleRequest($request);

        $error = $slug = null;
        $title = $form->get('title')->getData();

        if ($title) {
            $slug = $slugger->slug($title)->lower();
            $exists = $em->getRepository(Entry::class)->findOneBy(['slug' => $slug]);

            if ($exists) {
                $error = $translator->trans('slug.unique', [
                    '%name%' => $translator->trans('label.form.title'),
                    '%value%' => $title,
                ], 'validators');
            }
        }

        if ($form->isSubmitted() && $form->isValid() && !$error) {

            $details = new EntryDetails();
            $details->setTitle($title)
                ->setShortContent($form->get('short_content')->getData())
                ->setContent($form->get('content')->getData());

            $entry->setType(Entry::TYPE['Blog'])
                ->setSlug($slug)
                ->setUser($this->getUser())
                ->setEntryDetails($details);

            $em->persist($entry);

            $requestCategory = $request->get('category');

            if ($requestCategory) {
                foreach ($requestCategory as $key => $value) {
                    $entryCategory = new EntryCategory();
                    $entryCategory->setEntry($entry)
                        ->setCategory($em->getRepository(Category::class)->findOneBy(['id' => $key]));
                    $em->persist($entryCategory);
                }
            }

            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));

            return $this->redirectToRoute('app_dashboard_edit_blog', ['id' => $entry->getId()]);
        }

        return $this->render('dashboard/content/blog/_form.html.twig', [
            'form' => $form,
            'error' => $error,
            'entry' => $entry,
            'categories' => $em->getRepository(Category::class)->findBy([], ['position' => 'asc']),
        ]);
    }

    /**
     * @param Request $request
     * @param Entry $entry
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param SluggerInterface $slugger
     * @return Response
     */
    #[Route('/edit/{id}', name: 'app_dashboard_edit_blog', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'entry')]
    public function edit(
        Request                $request,
        Entry                  $entry,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        SluggerInterface       $slugger,
    ): Response
    {
        $form = $this->createForm(EntryDetailsType::class, $entry);
        $form->handleRequest($request);

        $error = null;
        $title = $form->get('title')->getData();

        if ($title) {
            try {
                $entry->setType('blog')->setSlug($slugger->slug($title)->lower());
                $em->persist($entry);
                $em->flush();
            } catch (\Exception $e) {
                $error = $translator->trans('slug.unique', [
                    '%name%' => $translator->trans('label.form.title'),
                    '%value%' => $title,
                ], 'validators');
            }
        }

        if ($form->isSubmitted() && $form->isValid() && !$error) {

            $requestCategory = $request->get('category');

            if ($requestCategory) {
                $em->getRepository(EntryCategory::class)->removeEntryCategory($entry->getId());
                foreach ($requestCategory as $key => $value) {
                    $entryCategory = new EntryCategory();
                    $entryCategory->setEntry($entry)->setCategory($em->getRepository(Category::class)->findOneBy(['id' => $key]));
                    $em->persist($entryCategory);
                }
            }

            $entry->setStatus($form->get('status')->getData())
                ->setSlug($slugger->slug($title)->lower())
                ->setUpdatedAt(new DateTime())
                ->setDeletedAt($form->get('status')->getData() == 'trashed' ? new DateTime() : null);

            $em->persist($entry);

            $details = $entry->getEntryDetails()
                ->setTitle($title)
                ->setShortContent($form->get('short_content')->getData())
                ->setContent($form->get('content')->getData())
                ->setEntry($entry);

            $em->persist($details);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));

            return $this->redirectToRoute('app_dashboard_edit_blog', ['id' => $entry->getId()]);
        }

        return $this->render('dashboard/content/blog/_form.html.twig', [
            'form' => $form,
            'entry' => $entry,
            'error' => $error,
            'categories' => $em->getRepository(Category::class)->findBy([], ['position' => 'asc']),
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param Entry $entry
     * @param EntityManagerInterface $em
     * @param SluggerInterface $slugger
     * @param CacheManager $cacheManager
     * @param ParameterBagInterface $params
     * @param ImageValidatorInterface $imageValidator
     * @return JsonResponse
     */
    #[Route('/attach/{id}', name: 'app_dashboard_blog_attach', methods: ['POST'])]
    public function attach(
        Request                 $request,
        TranslatorInterface     $translator,
        Entry                   $entry,
        EntityManagerInterface  $em,
        SluggerInterface        $slugger,
        CacheManager            $cacheManager,
        ParameterBagInterface   $params,
        ImageValidatorInterface $imageValidator,
    ): JsonResponse
    {
        $file = $request->files->get('file');

        $entryId = $entry->getId();

        if ($file) {
            $validate = $imageValidator->validate($file, $translator);

            if ($validate->has(0)) {
                return $this->json([
                    'message' => $validate->get(0)->getMessage(),
                    'picture' => null,
                ]);
            }

            $fileUploader = new FileUploader($this->getTargetDir($entryId, $params), $slugger, $em);

            try {
                $attach = $fileUploader->upload($file)->handle();
            } catch (\Exception $ex) {
                return $this->json([
                    'success' => false,
                    'message' => $ex->getMessage(),
                    'picture' => null,
                ]);
            }

            $attachments = $entry->getEntryAttachments();
            foreach ($attachments as $attachment) {
                $attachment->setInUse(0);
                $em->persist($attachment);
            }
            unset($attachment);

            $em->getRepository(EntryAttachment::class)->resetStatus($entry);

            $entryAttachment = new EntryAttachment();
            $entryAttachment->setEntry($entry)
                ->setAttach($attach)
                ->setInUse(1);

            $em->persist($entryAttachment);
            $em->flush();
        }

        $storage = $params->get('entry_storage_picture');

        $url = "{$storage}/{$entryId}/{$attach->getName()}";
        $picture = $cacheManager->getBrowserPath(parse_url($url, PHP_URL_PATH), 'entry_preview');

        return $this->json([
            'success' => true,
            'message' => $translator->trans('entry.picture.appended'),
            'picture' => $picture,
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $em
     * @param CacheManager $cacheManager
     * @param ParameterBagInterface $params
     * @return JsonResponse
     */
    #[Route('/attach-remove/{entry}', name: 'app_dashboard_blog_attach_remove', methods: ['POST'])]
    public function remove(
        Request                $request,
        TranslatorInterface    $translator,
        EntityManagerInterface $em,
        CacheManager           $cacheManager,
        ParameterBagInterface  $params
    ): JsonResponse
    {

        $parameters = json_decode($request->getContent(), true);

        $entry = $em->getRepository(Entry::class)->find($request->get('entry'));
        $attach = $em->getRepository(Attach::class)->find($parameters['id']);

        $attachment = $em->getRepository(EntryAttachment::class)->findOneBy([
            'attach' => $attach,
            'entry' => $entry,
        ]);

        $fs = new Filesystem();
        $attach = $attachment->getAttach();

        $exists = $em->getRepository(FileManager::class)->findOneBy(['file' => $attach, 'owner' => $this->getUser()]);

        if ($exists === null) {
            $oldFile = $this->getTargetDir($entry->getId(), $params) . '/' . $attach->getName();

            $em->remove($attach);

            if ($cacheManager->isStored($oldFile, 'entry_preview')) {
                $cacheManager->remove($oldFile, 'entry_preview');
            }

            if ($cacheManager->isStored($oldFile, 'entry_view')) {
                $cacheManager->remove($oldFile, 'entry_view');
            }

            if ($fs->exists($oldFile)) {
                $fs->remove($oldFile);
            }
        }

        $em->remove($attachment);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => $translator->trans('entry.picture.remove'),
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param Entry $entry
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/attach-set-use/{entry}', name: 'app_dashboard_blog_attach_set_use', methods: ['POST'])]
    public function setInUse(
        Request                $request,
        TranslatorInterface    $translator,
        Entry                  $entry,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $details = $em->getRepository(EntryDetails::class)->findOneBy(['entry' => $entry]);

        $attachments = $details->getEntry()->getEntryAttachments();
        foreach ($attachments as $attachment) {
            $attachment->setInUse(0);
            $em->persist($attachment);
        }

        $parameters = json_decode($request->getContent(), true);
        $attach = $em->getRepository(Attach::class)->find($parameters['id']);

        $entryAttachment = $em->getRepository(EntryAttachment::class)->findOneBy(['details' => $details->getEntry(), 'attach' => $attach]);

        $entryAttachment->setInUse(1);
        $em->persist($entryAttachment);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => $translator->trans('entry.picture.default'),
        ]);
    }
}
