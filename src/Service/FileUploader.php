<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Attach;
use App\Entity\MarketPlace\Store;
use App\Entity\MarketPlace\StoreProductAttach;
use App\Entity\UserDetails;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{

    /**
     * @var string|File
     */
    private string|File $target;

    private string $fileName;

    /**
     *
     * @param string $targetDirectory
     * @param SluggerInterface $slugger
     * @param EntityManagerInterface $em
     */
    public function __construct(
        private readonly string                 $targetDirectory,
        private readonly SluggerInterface       $slugger,
        private readonly EntityManagerInterface $em,
    )
    {

    }

    /**
     *
     * @return int|null
     */
    private function getSize(): ?int
    {
        return $this->target->getSize();
    }

    /**
     *
     * @return string|null
     */
    private function getMimeType(): ?string
    {
        return mime_content_type($this->target->getRealPath());
    }

    /**
     *
     * @param UploadedFile $file
     * @return self
     * @throws Exception
     */
    public function upload(UploadedFile $file): self
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $this->fileName = sprintf("%s-%s.%s", $safeFilename, uniqid(), $file->guessExtension());

        try {
            $target = $file->move($this->getTargetDirectory(), $this->fileName);
        } catch (FileException $e) {
            throw new Exception($e->getMessage());
        }

        $this->target = $target;
        return $this;
    }

    /**
     *
     * @param object|null $object
     * @return Attach|null
     */
    public function handle(?object $object = null): ?Attach
    {
        $attach = new Attach();

        if ($object instanceof UserDetails) {
            $attach->setUserDetails($object);
        }

        if ($object instanceof Store) {
            $attach->setStore($object);
        }

        if ($object instanceof StoreProductAttach) {
            $attach->addStoreProductAttach($object);
        }

        $attach->setName($this->fileName)
            ->setSize($this->getSize())
            ->setMime($this->getMimeType());

        $this->em->persist($attach);
        $this->em->flush();
        return $attach;
    }

    /**
     *
     * @return string
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
