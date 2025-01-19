<?php declare(strict_types=1);

namespace Inno\Entity;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\UserSocialRepository;

#[ORM\Entity(repositoryClass: UserSocialRepository::class)]
class UserSocial
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userSocial', cascade: ['persist', 'remove'])]
    private ?UserDetails $details = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $facebook_profile = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $twitter_profile = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $instagram_profile = null;

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
     * @return UserDetails|null
     */
    public function getDetails(): ?UserDetails
    {
        return $this->details;
    }

    /**
     *
     * @param UserDetails|null $details
     * @return static
     */
    public function setDetails(?UserDetails $details): static
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFacebookProfile(): ?string
    {
        return $this->facebook_profile;
    }

    /**
     *
     * @param string|null $facebook_profile
     * @return static
     */
    public function setFacebookProfile(?string $facebook_profile): static
    {
        $this->facebook_profile = $facebook_profile;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getTwitterProfile(): ?string
    {
        return $this->twitter_profile;
    }

    /**
     *
     * @param string|null $twitter_profile
     * @return static
     */
    public function setTwitterProfile(?string $twitter_profile): static
    {
        $this->twitter_profile = $twitter_profile;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getInstagramProfile(): ?string
    {
        return $this->instagram_profile;
    }

    /**
     *
     * @param string|null $instagram_profile
     * @return static
     */
    public function setInstagramProfile(?string $instagram_profile): static
    {
        $this->instagram_profile = $instagram_profile;

        return $this;
    }
}
