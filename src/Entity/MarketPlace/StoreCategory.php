<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreCategoryRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: StoreCategoryRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'slug.unique')]
class StoreCategory
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 512)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 512, unique: true, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?int $level = null;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: StoreCategory::class, mappedBy: 'parent')]
    private Collection $children;


    /**
     * @var StoreCategory|null
     */
    #[ORM\ManyToOne(targetEntity: StoreCategory::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?StoreCategory $parent = null;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column]
    private ?DateTimeImmutable $created_at;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: StoreCategoryProduct::class, mappedBy: 'category')]
    private Collection $storeCategoryProducts;

    /**
     * @var DateTimeInterface|null
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deleted_at = null;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
        $this->storeCategoryProducts = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     *
     * @param string $slug
     * @return static
     */
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     *
     * @return int|null
     */
    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     *
     * @param int $level
     * @return static
     */
    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return Collection<int, StoreCategory>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param StoreCategory $children
     * @return $this
     */
    public function addChildren(StoreCategory $children): static
    {
        $this->children[] = $children;
        return $this;
    }

    /**
     * @return StoreCategory|null
     */
    public function getParent(): ?StoreCategory
    {
        return $this->parent;
    }

    /**
     * @param StoreCategory|null $parent
     * @return $this
     */
    public function setParent(?StoreCategory $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     *
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     *
     * @param DateTimeImmutable $created_at
     * @return static
     */
    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     *
     * @return DateTimeInterface|null
     */
    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    /**
     *
     * @param DateTimeInterface|null $deleted_at
     * @return static
     */
    public function setDeletedAt(?DateTimeInterface $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    /**
     * @return Collection<int, StoreCategoryProduct>
     */
    public function getStoreCategoryProducts(): Collection
    {
        return $this->storeCategoryProducts;
    }

    /**
     *
     * @param StoreCategoryProduct $storeCategoryProduct
     * @return static
     */
    public function addStoreCategoryProduct(StoreCategoryProduct $storeCategoryProduct): static
    {
        if (!$this->storeCategoryProducts->contains($storeCategoryProduct)) {
            $this->storeCategoryProducts->add($storeCategoryProduct);
            $storeCategoryProduct->setCategory($this);
        }

        return $this;
    }

    /**
     *
     * @param StoreCategoryProduct $storeCategoryProduct
     * @return static
     */
    public function removeStoreCategoryProduct(StoreCategoryProduct $storeCategoryProduct): static
    {
        if ($this->storeCategoryProducts->removeElement($storeCategoryProduct)) {
            // set the owning side to null (unless already changed)
            if ($storeCategoryProduct->getCategory() === $this) {
                $storeCategoryProduct->setCategory(null);
            }
        }

        return $this;
    }
}
