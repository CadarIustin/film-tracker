<?php

namespace App\Entity;

use App\Enum\TitleType;
use App\Repository\TitleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TitleRepository::class)]
class Title
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 10)]
    private TitleType $type;

    #[ORM\Column]
    private int $year;

    #[ORM\Column]
    private bool $watched;

    public function __construct(string $name, TitleType $type, int $year, bool $watched = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->year = $year;
        $this->watched = $watched;
    }
}
