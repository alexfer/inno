<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreOptionsRepository;

#[ORM\Entity(repositoryClass: StoreOptionsRepository::class)]
class StoreOptions
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'storeOptions', cascade: ['persist', 'remove'])]
    private ?Store $store = null;

    #[ORM\Column(nullable: true)]
    private ?int $backup_schedule = null;

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
     * @return int|null
     */
    public function getBackupSchedule(): ?int
    {
        return $this->backup_schedule;
    }

    /**
     * @param int|null $backup_schedule
     * @return $this
     */
    public function setBackupSchedule(?int $backup_schedule): static
    {
        $this->backup_schedule = $backup_schedule;

        return $this;
    }
}
