<?php

namespace App\Entity;

use App\Enum\TitleType;
use App\Repository\TitleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TitleRepository::class)]
class Title implements \JsonSerializable
{
    // Stays null until the first flush(), because the database generates the id.
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // DBAL 4 needs an explicit length for string columns on most databases.
    #[ORM\Column(length: 255)]
    private string $name;

    // Doctrine sees the enum type and stores its string value ('film' or 'series').
    #[ORM\Column(length: 10)]
    private TitleType $type;

    #[ORM\Column]
    private int $year;

    #[ORM\Column]
    private bool $watched;

    // Required fields are constructor arguments, so a Title can never exist half-filled.
    public function __construct(string $name, TitleType $type, int $year, bool $watched = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->year = $year;
        $this->watched = $watched;
    }

    // The only setter: a bool can never hold an invalid value, so the entity stays always-valid.
    public function setWatched(bool $watched): void
    {
        $this->watched = $watched;
    }

    // json_encode() calls this, so the JSON shape of a title is defined only here.
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'year' => $this->year,
            'watched' => $this->watched,
        ];
    }
}
