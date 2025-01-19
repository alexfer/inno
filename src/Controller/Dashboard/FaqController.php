<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Inno\Entity\Faq;
use Inno\Form\Type\FaqType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/faq')]
#[IsGranted('ROLE_ADMIN', message: 'Access denied.')]
class FaqController extends AbstractController
{
    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('', name: 'app_dashboard_faq')]
    public function index(
        EntityManagerInterface $em,
    ): Response
    {
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('dashboard/content/faq/index.html.twig', [
            'entries' => $em->getRepository(Faq::class)->findBy([], ['id' => 'desc']),
        ]);
    }

    /**
     * @param Request $request
     * @param Faq $faq
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    #[Route('/delete/{id}', name: 'app_dashboard_delete_faq', methods: ['POST'])]
    public function delete(
        Request                $request,
        Faq                    $faq,
        EntityManagerInterface $em,
    ): Response
    {
        $token = $request->get('_token');

        if (!$token) {
            $content = $request->getPayload()->all();
            $token = $content['_token'];
        }

        if ($this->isCsrfTokenValid('delete', $token)) {
            $date = new DateTime('@' . strtotime('now'));
            $faq->setDeletedAt($date)->setVisible(0);
            $em->persist($faq);
            $em->flush();
        }

        return $this->json(['message' => 'success', 'redirect' => $this->generateUrl('app_dashboard_faq')]);
    }

    /**
     *
     * @param Faq $entry
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/restore/{id}', name: 'app_dashboard_restore_faq')]
    public function restore(
        Faq                    $entry,
        EntityManagerInterface $em,
    ): Response
    {
        $entry->setDeletedAt(null);
        $em->persist($entry);
        $em->flush();

        return $this->redirectToRoute('app_dashboard_faq');
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/create', name: 'app_dashboard_create_faq', methods: ['GET', 'POST'])]
    public function create(
        Request                $request,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
    ): Response
    {
        $faq = new Faq();

        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($faq);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));
            return $this->redirectToRoute('app_dashboard_edit_faq', ['id' => $faq->getId()]);
        }

        return $this->render('dashboard/content/faq/_form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param Faq $faq
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/edit/{id}', name: 'app_dashboard_edit_faq', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
    ): Response
    {
        $faq = $em->getRepository(Faq::class)->find($request->get('id'));
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($faq);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));
            return $this->redirectToRoute('app_dashboard_edit_faq', ['id' => $faq->getId()]);
        }

        return $this->render('dashboard/content/faq/_form.html.twig', [
            'form' => $form,
        ]);
    }
}
