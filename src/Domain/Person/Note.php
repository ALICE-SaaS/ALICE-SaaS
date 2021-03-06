<?php
declare(strict_types=1);

namespace App\Domain\Person;

use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * @Entity
 * @Table(name="visitor_notes", schema="visitor_management")
 */
class Note {
  /**
   * @Id
   * @GeneratedValue
   * @Column
   */
  public ?int $id;

  /** @Column(name="person_id") */
  public int $personId;

  /** @Column */
  public ?string $note;

  /** @Column(name="user_id") */
  public int $userId;

  /** @Column(name="date_created", nullable=true, type="datetime") */
  public ?DateTime $createdAt;

  /** @Column(name="date_updated", nullable=true, type="datetime") */
  public ?DateTime $updatedAt;

  public function getUserName(): string {
    return $this->person->getDisplayName();
  }

  /**
   * @ManyToOne(targetEntity="Person", inversedBy="notes")
   * @JoinColumn(name="person_id", referencedColumnName="person_id")
   */
  protected Person $person;

  public function setPerson(Person $person): void {
    $this->person = $person;
  }

  public function __construct() {
    $this->createdAt = new DateTime();
    $this->updatedAt = new DateTime();
  }
}
