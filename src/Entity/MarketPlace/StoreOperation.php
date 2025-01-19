<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\MarketPlace\Enum\EnumOperation;
use Inno\Repository\MarketPlace\StoreOperationRepository;

#[ORM\Entity(repositoryClass: StoreOperationRepository::class)]
#[ORM\Index(name: 'revision_idx', columns: ['revision'])]
class StoreOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(enumType: EnumOperation::class)]
    private ?EnumOperation $format = null;

    #[ORM\Column(length: 100)]
    private ?string $revision = null;

    #[ORM\ManyToOne(inversedBy: 'storeOperations')]
    private ?Store $store = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return EnumOperation|null
     */
    public function getFormat(): ?EnumOperation
    {
        return $this->format;
    }

    /**
     * @param EnumOperation $format
     * @return $this
     */
    public function setFormat(EnumOperation $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRevision(): ?string
    {
        return $this->revision;
    }

    /**
     * @param string $revision
     * @return $this
     */
    public function setRevision(string $revision): static
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * @return Store|null
     */
    public function getStore(): ?Store
    {
        return $this->store;
    }

    /**
     * @param Store|null $store
     * @return $this
     */
    public function setStore(?Store $store): static
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @param \DateTimeImmutable $created_at
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string|null $filename
     * @return $this
     */
    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }
}
