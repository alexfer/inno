<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreManufacturerRepository;

#[ORM\Entity(repositoryClass: StoreManufacturerRepository::class)]
class StoreManufacturer
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeManufacturers')]
    private ?Store $store = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: StoreProductManufacturer::class, mappedBy: 'manufacturer')]
    private Collection $storeProductManufacturers;

    public function __construct()
    {
        $this->storeProductManufacturers = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
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
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, StoreProductManufacturer>
     */
    public function getStoreProductManufacturers(): Collection
    {
        return $this->storeProductManufacturers;
    }

    /**
     * @param StoreProductManufacturer $storeProductManufacturer
     * @return $this
     */
    public function addStoreProductManufacturer(StoreProductManufacturer $storeProductManufacturer): static
    {
        if (!$this->storeProductManufacturers->contains($storeProductManufacturer)) {
            $this->storeProductManufacturers->add($storeProductManufacturer);
            $storeProductManufacturer->setManufacturer($this);
        }

        return $this;
    }

    /**
     * @param StoreProductManufacturer $storeProductManufacturer
     * @return $this
     */
    public function removeStoreProductManufacturer(StoreProductManufacturer $storeProductManufacturer): static
    {
        if ($this->storeProductManufacturers->removeElement($storeProductManufacturer)) {
            // set the owning side to null (unless already changed)
            if ($storeProductManufacturer->getManufacturer() === $this) {
                $storeProductManufacturer->setManufacturer(null);
            }
        }

        return $this;
    }
}
