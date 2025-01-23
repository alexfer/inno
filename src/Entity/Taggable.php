<?php declare(strict_types=1);

namespace Inno\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\Enum\EnumTagType;
use Inno\Repository\TaggableRepository;

#[ORM\Entity(repositoryClass: TaggableRepository::class)]
#[ORM\Index(name: 'idx_taggable_id', columns: ['taggable_id'])]
#[ORM\Index(name: 'idx_type', columns: ['type'])]
class Taggable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'taggables')]
    private ?Tag $tag = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $taggable_id = null;

    #[ORM\Column(enumType: EnumTagType::class)]
    private ?EnumTagType $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Tag|null
     */
    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    /**
     * @param Tag|null $tag
     * @return $this
     */
    public function setTag(?Tag $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTaggableId(): ?string
    {
        return $this->taggable_id;
    }

    /**
     * @param string $taggable_id
     * @return $this
     */
    public function setTaggableId(string $taggable_id): static
    {
        $this->taggable_id = $taggable_id;

        return $this;
    }

    /**
     * @return EnumTagType|null
     */
    public function getType(): ?EnumTagType
    {
        return $this->type;
    }

    /**
     * @param EnumTagType $type
     * @return $this
     */
    public function setType(EnumTagType $type): static
    {
        $this->type = $type;

        return $this;
    }
}
