<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrganizationContactRepository")
 */
class OrganizationContact
{
    use EntityIdTrait;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobTitle;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPrimary;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mobilePhone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $workPhone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $homePhone;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Organization", inversedBy="contacts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $organization;

    /**
     * @ORM\Column(type="boolean")
     */
    private $notifyViaEmail;

    /**
     * @ORM\Column(type="boolean")
     */
    private $notifyViaMobile;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->isPrimary = false;
        $this->notifyViaEmail = true;
        $this->notifyViaMobile = true;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getIsPrimary(): ?bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?string $mobilePhone): self
    {
        $this->mobilePhone = $mobilePhone;

        return $this;
    }

    public function getWorkPhone(): ?string
    {
        return $this->workPhone;
    }

    public function setWorkPhone(?string $workPhone): self
    {
        $this->workPhone = $workPhone;

        return $this;
    }

    public function getHomePhone(): ?string
    {
        return $this->homePhone;
    }

    public function setHomePhone(?string $homePhone): self
    {
        $this->homePhone = $homePhone;

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

    public function getNotifyViaEmail(): ?bool
    {
        return $this->notifyViaEmail;
    }

    public function setNotifyViaEmail(bool $notifyViaEmail): self
    {
        $this->notifyViaEmail = $notifyViaEmail;

        return $this;
    }

    public function getNotifyViaMobile(): ?bool
    {
        return $this->notifyViaMobile;
    }

    public function setNotifyViaMobile(bool $notifyViaMobile): self
    {
        $this->notifyViaMobile = $notifyViaMobile;

        return $this;
    }
}
