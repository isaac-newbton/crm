<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrganizationRepository")
 */
class Organization
{
    use EntityIdTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrganizationContact", mappedBy="organization", orphanRemoval=true)
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $contacts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrganizationApi", mappedBy="organization", orphanRemoval=true)
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $organizationApis;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Lead", mappedBy="organization", orphanRemoval=true)
     * @ORM\OrderBy({"dt" = "DESC"})
     */
    private $leads;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->contacts = new ArrayCollection();
        $this->organizationApis = new ArrayCollection();
        $this->leads = new ArrayCollection();
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

    /**
     * @return Collection|Organizationcontact[]
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Organizationcontact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setOrganization($this);
        }

        return $this;
    }

    public function removeContact(Organizationcontact $contact): self
    {
        if ($this->contacts->contains($contact)) {
            $this->contacts->removeElement($contact);
            // set the owning side to null (unless already changed)
            if ($contact->getOrganization() === $this) {
                $contact->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrganizationApi[]
     */
    public function getOrganizationApis(): Collection
    {
        return $this->organizationApis;
    }

    public function addOrganizationApi(OrganizationApi $organizationApi): self
    {
        if (!$this->organizationApis->contains($organizationApi)) {
            $this->organizationApis[] = $organizationApi;
            $organizationApi->setOrganization($this);
        }

        return $this;
    }

    public function removeOrganizationApi(OrganizationApi $organizationApi): self
    {
        if ($this->organizationApis->contains($organizationApi)) {
            $this->organizationApis->removeElement($organizationApi);
            // set the owning side to null (unless already changed)
            if ($organizationApi->getOrganization() === $this) {
                $organizationApi->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Lead[]
     */
    public function getLeads(): Collection
    {
        return $this->leads;
    }

    public function addLead(Lead $lead): self
    {
        if (!$this->leads->contains($lead)) {
            $this->leads[] = $lead;
            $lead->setOrganization($this);
        }

        return $this;
    }

    public function removeLead(Lead $lead): self
    {
        if ($this->leads->contains($lead)) {
            $this->leads->removeElement($lead);
            // set the owning side to null (unless already changed)
            if ($lead->getOrganization() === $this) {
                $lead->setOrganization(null);
            }
        }

        return $this;
    }
}
