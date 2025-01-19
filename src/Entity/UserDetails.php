<?php declare(strict_types=1);

namespace Inno\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\UserDetailsRepository;

#[ORM\Entity(repositoryClass: UserDetailsRepository::class)]
class UserDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userDetails', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Attach::class, mappedBy: 'userDetails')]
    #[ORM\OrderBy(['id' => 'desc'])]
    private Collection $attach;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $about = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $date_birth;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $updated_at;

    #[ORM\OneToOne(mappedBy: 'details', cascade: ['persist', 'remove'])]
    private ?UserSocial $userSocial = null;

    public function __construct()
    {
        $this->updated_at = new DateTime();
        $this->date_birth = new DateTime('2005-01-01');
        $this->attach = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Attach>
     */
    public function getAttach(): Collection
    {
        return $this->attach;
    }

    public function addAttach(Attach $attach): static
    {
        if (!$this->attach->contains($attach)) {
            $this->attach->add($attach);
            $attach->setUserDetails($this);
        }

        return $this;
    }

    public function removeAttach(Attach $attach): static
    {
        if ($this->attach->removeElement($attach)) {
            // set the owning side to null (unless already changed)
            if ($attach->getUserDetails() === $this) {
                $attach->setUserDetails(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): static
    {
        $this->about = $about;

        return $this;
    }

    public function getDateBirth(): ?DateTime
    {
        return $this->date_birth;
    }

    public function setDateBirth(?DateTime $date_birth): static
    {
        $this->date_birth = $date_birth;

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getUserSocial(): ?UserSocial
    {
        return $this->userSocial;
    }

    public function setUserSocial(?UserSocial $userSocial): static
    {
        // unset the owning side of the relation if necessary
        if ($userSocial === null && $this->userSocial !== null) {
            $this->userSocial->setDetails(null);
        }

        // set the owning side of the relation if necessary
        if ($userSocial !== null && $userSocial->getDetails() !== $this) {
            $userSocial->setDetails($this);
        }

        $this->userSocial = $userSocial;

        return $this;
    }
}
