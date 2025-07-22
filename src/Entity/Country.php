<?php
declare(strict_types=1);

namespace App\Entity;
use App\Repository\CountryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: Types::GUID, unique: true)]
    #[Assert\Unique]
    private ?string $uuid = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Unique]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $region = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $subRegion = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $demonym = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    private ?int $population = null;

    #[ORM\Column]
    private ?bool $independant = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $flag = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Currency $currency = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getSubRegion(): ?string
    {
        return $this->subRegion;
    }

    public function setSubRegion(string $subRegion): static
    {
        $this->subRegion = $subRegion;

        return $this;
    }

    public function getDemonym(): ?string
    {
        return $this->demonym;
    }

    public function setDemonym(string $demonym): static
    {
        $this->demonym = $demonym;

        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(int $population): static
    {
        $this->population = $population;

        return $this;
    }

    public function isIndependant(): ?bool
    {
        return $this->independant;
    }

    public function setIndependant(bool $independant): static
    {
        $this->independant = $independant;

        return $this;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function setFlag(string $flag): static
    {
        $this->flag = $flag;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

}