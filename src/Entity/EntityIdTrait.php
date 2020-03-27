<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

trait EntityIdTrait{
	/**
	 * @var int|null
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue()
	 */
	protected $id;

	/**
	 * @var UuidInterface
	 * @ORM\Column(type="uuid", unique=true)
	 */
	protected $uuid;

	public function getId(): ?int {
		return $this->id;
	}

	public function getUuid(): UuidInterface {
		return $this->uuid;
	}
}