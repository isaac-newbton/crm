<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrganizationApiRepository")
 */
class OrganizationApi
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=23)
     */
    private $apiKey;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\organization", inversedBy="organizationApis")
     * @ORM\JoinColumn(nullable=false)
     */
    private $organization;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getOrganization(): ?organization
    {
        return $this->organization;
    }

    public function setOrganization(?organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }
}
