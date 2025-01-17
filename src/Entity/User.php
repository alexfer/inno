<?php declare(strict_types=1);

namespace Inno\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreMessage;
use Inno\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "`user`")]
#[UniqueEntity(fields: ['email'], message: 'email.unique')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [self::ROLE_USER, self::ROLE_ADMIN, self::ROLE_CUSTOMER];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $created_at;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $last_login_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $deleted_at = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserDetails $userDetails = null;

    #[ORM\OneToOne(inversedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Attach $attach = null;

    #[ORM\OneToMany(targetEntity: Store::class, mappedBy: 'owner')]
    private Collection $stores;

    /**
     * @var Collection<int, StoreMessage>
     */
    #[ORM\OneToMany(targetEntity: StoreMessage::class, mappedBy: 'owner')]
    private Collection $storeMessages;

    /**
     * @var Collection<int, FileManager>
     */
    #[ORM\OneToMany(targetEntity: FileManager::class, mappedBy: 'owner')]
    private Collection $fileManagers;

    final public const string ROLE_USER = 'ROLE_USER';
    final public const string ROLE_ADMIN = 'ROLE_ADMIN';
    final public const string ROLE_CUSTOMER = 'ROLE_CUSTOMER';

    public function __construct()
    {
        $this->created_at = new DateTime();
        $this->stores = new ArrayCollection();
        $this->storeMessages = new ArrayCollection();
        $this->fileManagers = new ArrayCollection();
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
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     *
     * @param string $email
     * @return static
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @return array
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_CUSTOMER
        $roles[] = self::ROLE_CUSTOMER;

        return array_unique($roles);
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     *
     * @param array $roles
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return string
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     *
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPasswordSafe(): bool
    {
        return $this->email !== $this->password;
    }

    /**
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    /**
     *
     * @param DateTime $created_at
     * @return self
     */
    public function setCreatedAt(DateTime $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     *
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deleted_at;
    }

    /**
     *
     * @param DateTime|null $deleted_at
     * @return self
     */
    public function setDeletedAt(?DateTime $deleted_at): self
    {
        $this->deleted_at = $deleted_at;

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

    /**
     * @return void
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     *
     * @return UserDetails|null
     */
    public function getUserDetails(): ?UserDetails
    {
        return $this->userDetails;
    }

    /**
     *
     * @param UserDetails|null $userDetails
     * @return static
     */
    public function setUserDetails(?UserDetails $userDetails): static
    {
        // unset the owning side of the relation if necessary
        if ($userDetails === null && $this->userDetails !== null) {
            $this->userDetails->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($userDetails !== null && $userDetails->getUser() !== $this) {
            $userDetails->setUser($this);
        }

        $this->userDetails = $userDetails;

        return $this;
    }

    /**
     * @return Collection<int, Store>
     */
    public function getStores(): Collection
    {
        return $this->stores;
    }

    /**
     * @param Store $store
     * @return $this
     */
    public function addStore(Store $store): static
    {
        if (!$this->stores->contains($store)) {
            $this->stores->add($store);
            $store->setOwner($this);
        }

        return $this;
    }

    /**
     * @param Store $store
     * @return $this
     */
    public function removeStore(Store $store): static
    {
        if ($this->stores->removeElement($store)) {
            // set the owning side to null (unless already changed)
            if ($store->getOwner() === $this) {
                $store->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->last_login_at;
    }

    /**
     * @param \DateTimeImmutable|null $last_login_at
     * @return $this
     */
    public function setLastLoginAt(?\DateTimeImmutable $last_login_at): static
    {
        $this->last_login_at = $last_login_at;

        return $this;
    }

    /**
     * @return Collection<int, StoreMessage>
     */
    public function getStoreMessages(): Collection
    {
        return $this->storeMessages;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function addStoreMessage(StoreMessage $storeMessage): static
    {
        if (!$this->storeMessages->contains($storeMessage)) {
            $this->storeMessages->add($storeMessage);
            $storeMessage->setOwner($this);
        }

        return $this;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function removeStoreMessage(StoreMessage $storeMessage): static
    {
        if ($this->storeMessages->removeElement($storeMessage)) {
            // set the owning side to null (unless already changed)
            if ($storeMessage->getOwner() === $this) {
                $storeMessage->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FileManager>
     */
    public function getFileManagers(): Collection
    {
        return $this->fileManagers;
    }

    /**
     * @param FileManager $fileManager
     * @return $this
     */
    public function addFileManager(FileManager $fileManager): static
    {
        if (!$this->fileManagers->contains($fileManager)) {
            $this->fileManagers->add($fileManager);
            $fileManager->setOwner($this);
        }

        return $this;
    }

    /**
     * @param FileManager $fileManager
     * @return $this
     */
    public function removeFileManager(FileManager $fileManager): static
    {
        if ($this->fileManagers->removeElement($fileManager)) {
            // set the owning side to null (unless already changed)
            if ($fileManager->getOwner() === $this) {
                $fileManager->setOwner(null);
            }
        }

        return $this;
    }
}
