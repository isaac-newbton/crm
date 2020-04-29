<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FacebookLeadgenRepository")
 */
class FacebookLeadgen
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
    private $leadgenId;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $result = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $completed;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dt;

    /**
     * @ORM\Column(type="integer")
     */
    private $attempts;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Organization", inversedBy="facebookLeadgens")
     */
    private $organization;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $facebookPage;

    public function __construct(){
        $this->dt = new \DateTime();
        $this->result = null;
        $this->completed = null;
        $this->attempts = 0;
        $this->facebookPage = null;
        $this->organization = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLeadgenId(): ?int
    {
        return $this->leadgenId;
    }

    public function setLeadgenId(int $leadgenId): self
    {
        $this->leadgenId = $leadgenId;

        return $this;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function setResult(?array $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getCompleted(): ?\DateTimeInterface
    {
        return $this->completed;
    }

    public function setCompleted(?\DateTimeInterface $completed): self
    {
        $this->completed = $completed;

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

    public function getAttempts(): ?int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;

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

    public function getFacebookPage(): ?string
    {
        return $this->facebookPageId;
    }

    public function setFacebookPage(?string $facebookPageId): self
    {
        $this->facebookPageId = $facebookPageId;

        return $this;
    }
}
