<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\Attach;
use Inno\Repository\MarketPlace\StoreProductAttachRepository;

#[ORM\Entity(repositoryClass: StoreProductAttachRepository::class)]
class StoreProductAttach
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeProductAttaches')]
    private ?StoreProduct $product = null;

    #[ORM\ManyToOne(inversedBy: 'storeProductAttaches')]
    private ?Attach $attach = null;

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
     * @return StoreProduct|null
     */
    public function getProduct(): ?StoreProduct
    {
        return $this->product;
    }

    /**
     *
     * @param StoreProduct|null $product
     * @return static
     */
    public function setProduct(?StoreProduct $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     *
     * @return Attach|null
     */
    public function getAttach(): ?Attach
    {
        return $this->attach;
    }

    /**
     *
     * @param Attach|null $attach
     * @return static
     */
    public function setAttach(?Attach $attach): static
    {
        $this->attach = $attach;

        return $this;
    }
}
