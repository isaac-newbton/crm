<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LeadRepository")
 */
class Lead
{
    use EntityIdTrait;

    /**
     * @ORM\Column(type="json")
     */
    private $fields = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $dt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Organization", inversedBy="leads")
     * @ORM\JoinColumn(nullable=false)
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LeadRating", inversedBy="leads")
     */
    private $internalRating;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->dt = new \DateTime();
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function getDt(): ?\DateTimeInterface
    {
        return $this->dt;
    }

    public function setDt(\DateTimeInterface $dt): self
    {
        $this->dt = $dt;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getInternalRating(): ?LeadRating
    {
        return $this->internalRating;
    }

    public function setInternalRating(?LeadRating $internalRating): self
    {
        $this->internalRating = $internalRating;

        return $this;
    }
}
