<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreSocialRepository;

#[ORM\Entity(repositoryClass: StoreSocialRepository::class)]
class StoreSocial
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeSocials')]
    private ?Store $store = null;

    #[ORM\Column(length: 4096, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source_name = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_active = null;

    public const array NAME = [
        'Facebook' => 'facebook',
        'Instagram' => 'instagram',
        'Twitter' => 'twitter',
        'Youtube' => 'youtube',
    ];

    public function __construct()
    {
        $this->is_active = false;
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
    public function getSourceName(): ?string
    {
        return $this->source_name;
    }

    /**
     * @param string|null $source_name
     * @return $this
     */
    public function setSourceName(?string $source_name): static
    {
        $this->source_name = $source_name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @param string|null $source
     * @return $this
     */
    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    /**
     * @param bool|null $is_active
     * @return $this
     */
    public function setActive(?bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }
}
