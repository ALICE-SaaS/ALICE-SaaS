<?php
declare(strict_types=1);

namespace App\Domain\Person;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * @Entity
 * @Table(name="person_names", schema="public")
 */
class PersonName {
  /**
   * @Id
   * @GeneratedValue
   * @Column(name="pname_id")
   */
  public ?int $id;

  /** @Column(name="person_id") */
  public int $personId;

  /** @Column(name="name_type") */
  public int $nameType;

  /** @Column(name="given_name") */
  public string $givenName;

  /** @Column(name="middle_name") */
  public string $middleName;

  /** @Column(name="family_name") */
  public string $familyName;

  /** @Column(name="nick_name") */
  public string $nickName;

  /** @Column */
  public string $suffix;

  /** @Column */
  public string $title;

  /**
   * @ManyToOne(targetEntity="Person", inversedBy="names")
   * @JoinColumn(name="person_id", referencedColumnName="person_id")
   */
  public Person $person;
}
