<?php declare(strict_types=1);

namespace Inno\Controller\Security;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Inno\Entity\Attach;
use Inno\Entity\User;
use Inno\Form\Type\User\ProfileType;
use Inno\Repository\AttachRepository;
use Inno\Repository\UserDetailsRepository;
use Inno\Repository\UserRepository;
use Inno\Service\FileUploader;
use Inno\Service\Validator\Interface\ImageValidatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\{Request, Response,};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param CacheManager $cacheManager
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $params
     * @return Response
     */
    #[Route('/profile/attach/remove', name: 'app_profile_attach_remove', methods: ['POST'])]
    public function remove(
        Request                $request,
        TranslatorInterface    $translator,
        CacheManager           $cacheManager,
        EntityManagerInterface $em,
        ParameterBagInterface  $params
    ): Response
    {
        $id = $request->getPayload()->get('id');
        $user = $em->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $attach = $em->getRepository(Attach::class)->findOneBy(['id' => $id, 'userDetails' => $user->getUserDetails()]);

        $fs = new Filesystem();
        $oldFile = $this->getTargetDir($params, $attach->getUserDetails()->getId()) . '/' . $attach->getName();

        if ($cacheManager->isStored($oldFile, 'user_profile')) {
            $cacheManager->remove($oldFile, 'user_profile');
        }

        if ($cacheManager->isStored($oldFile, 'user_preview')) {
            $cacheManager->remove($oldFile, 'user_preview');
        }

        if ($cacheManager->isStored($oldFile, 'user_thumb')) {
            $cacheManager->remove($oldFile, 'user_thumb');
        }

        if ($fs->exists($oldFile)) {
            $fs->remove($oldFile);
        }

        $attach->setUserDetails(null);
        $em->persist($attach);
        $em->remove($attach);
        $em->flush();

        return $this->json(['message' => $translator->trans('user.picture.delete'), 'file' => $oldFile]);
    }

    /**
     * @param Request $request
     * @param UserRepository $repository
     * @param TranslatorInterface $translator
     * @param AttachRepository $attach
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Route('/profile/attach/default', name: 'app_profile_attach_default', methods: ['POST'])]
    public function default(
        Request                $request,
        UserRepository         $repository,
        TranslatorInterface    $translator,
        AttachRepository       $attach,
        EntityManagerInterface $em,
    ): Response
    {
        try {
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
        } catch (NotFoundExceptionInterface $ex) {
            throw new Exception($ex);
        }

        $id = $request->getPayload()->get('id');

        $attachment = $attach->find($id);
        $owner = $repository->find($user->getId());
        $owner->setAttach($attachment);

        $em->persist($owner);
        $em->flush();

        return $this->json(['message' => $translator->trans('user.picture.default')]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $em
     * @param UserDetailsRepository $repository
     * @param UserInterface $user
     * @param SluggerInterface $slugger
     * @param CacheManager $cacheManager
     * @param ParameterBagInterface $params
     * @param ImageValidatorInterface $imageValidator
     * @return Response
     * @throws Exception
     */
    #[Route('/profile/attach', name: 'app_profile_attach', methods: ['POST'])]
    public function attach(
        Request                 $request,
        TranslatorInterface     $translator,
        EntityManagerInterface  $em,
        UserDetailsRepository   $repository,
        UserInterface           $user,
        SluggerInterface        $slugger,
        CacheManager            $cacheManager,
        ParameterBagInterface   $params,
        ImageValidatorInterface $imageValidator,
    ): Response
    {
        $details = $repository->find($user->getId());
        $file = $request->files->get('file');

        if ($file) {

            $validate = $imageValidator->validate($file, $translator);

            if ($validate->has(0)) {
                return $this->json([
                        'success' => false,
                        'message' => $validate->get(0)->getMessage(),
                        'picture' => null,
                        'attachments' => [],
                    ]
                );
            }

            $fileUploader = new FileUploader($this->getTargetDir($params, $user->getId()), $slugger, $em);

            try {
                $attach = $fileUploader->upload($file)->handle($details);
            } catch (Exception $ex) {
                throw new Exception($ex->getMessage());
            }

            $details->getUser()->setAttach($attach);
            $details->addAttach($attach);
        }

        $em->persist($details);
        $em->flush();

        $url = $this->getTargetDir($params, $user->getId());
        $picture = $cacheManager->getBrowserPath(parse_url($url . '/' . $attach->getName(), PHP_URL_PATH), 'user_thumb', [], null);

        return $this->json([
            'success' => true,
            'id' => $attach->getId(),
            'path' => $this->generateUrl('app_profile_attach_remove'),
            'message' => $translator->trans('user.picture.changed'),
            'picture' => $picture,
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param SluggerInterface $slugger
     * @param EntityManagerInterface $em
     * @param UserDetailsRepository $repository
     * @param ParameterBagInterface $params
     * @param TranslatorInterface $translator
     * @return Response
     * @throws Exception
     */
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        Request                $request,
        UserInterface          $user,
        SluggerInterface       $slugger,
        EntityManagerInterface $em,
        UserDetailsRepository  $repository,
        ParameterBagInterface  $params,
        TranslatorInterface    $translator,
    ): Response
    {
        $details = $repository->findOneBy(['user' => $user]);

        $form = $this->createForm(ProfileType::class, $details);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('picture')->getData();

            if ($file) {
                $fileUploader = new FileUploader($this->getTargetDir($params, $user->getId()), $slugger, $em);

                try {
                    $attach = $fileUploader->upload($file)->handle($details);
                } catch (Exception $ex) {
                    throw new Exception($ex->getMessage());
                }

                $details->getUser()->setAttach($attach);
                $details->addAttach($attach);
            }

            $details->setFirstName($form->get('first_name')->getData())
                ->setLastName($form->get('last_name')->getData());

            $details->getUserSocial()
                ->setFacebookProfile($form->get('facebook_profile')->getData())
                ->setInstagramProfile($form->get('instagram_profile')->getData())
                ->setTwitterProfile($form->get('twitter_profile')->getData());

            $em->persist($details);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.profile.updated')]));

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/profile.html.twig', [
            'form' => $form,
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param ParameterBagInterface $params
     * @param int $id
     * @return string
     */
    private function getTargetDir(ParameterBagInterface $params, int $id): string
    {
        return sprintf('%s/%d', $params->get('user_storage_picture'), $id);
    }
}
