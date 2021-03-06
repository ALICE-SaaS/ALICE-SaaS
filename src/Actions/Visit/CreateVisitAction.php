<?php

declare(strict_types=1);

namespace App\Actions\Visit;

use Psr\Http\Message\ResponseInterface as Response;
use App\Actions\Action;
use Psr\Log\LoggerInterface;
use App\Domain\Visit\Visit;
use App\Domain\Visit\VisitRepository;
use App\Domain\Person\Person;
use App\Domain\Person\PersonEmail;
use App\Domain\Person\Identification;
use App\Domain\Person\PersonAddress;
use App\Domain\Person\PersonRepository;
use App\Domain\Person\BlacklistItem;
use App\Domain\Student\StudentRepository;
use App\Domain\Building\BuildingRepository;
use App\Exceptions;
use DateTime;

class CreateVisitAction extends Action
{
    /**
     * @param LoggerInterface $logger
     * @param VisitRepository $visitRepository
     * @param StudentRepository $studentRepository
     * @param BuildingRepository $buildingRepository
     */
    public function __construct(LoggerInterface $logger, VisitRepository $visitRepository, PersonRepository $personRepository, StudentRepository $studentRepository, BuildingRepository $buildingRepository)
    {
        $this->visitRepository = $visitRepository;
        $this->personRepository = $personRepository;
        $this->studentRepository = $studentRepository;
        $this->buildingRepository = $buildingRepository;
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $formData = $this->getFormData();
        $this->logger->info('post visits', (array) $formData);

        $person = null;
        if (isset($formData->personId)) {
            $person = $this->personRepository->findPersonOfId((int) $formData->personId);
        }

        // if they scan an id, check if a person with that identification already exists
        if (isset($formData->identificationId) && is_null($person)) {
            try {
                $person = $this->personRepository->findPersonByIdentification((string) $formData->identificationId);
            } catch (\Exception $e) {
            }
        }

        // test scenarios
        if (isset($formData->testScenario)) {
            if ($formData->testScenario == 'cleanVisitor') {
                try {
                    $person = $this->personRepository->findPersonByType("demoCleanVisitor");
                }
                catch (\Exception $e) {
                    $formData->firstName = "Charles";
                    $formData->lastName = "Morgano";
                    $formData->email = "cmorgan@email.com";
                    $formData->birthDate = "12-13-1985";
                    $formData->address = "123 West Main Street Boston, MA 12345";
                    $formData->type = "demoCleanVisitor"; 
                }
            }
            
            if ($formData->testScenario == 'sexOffender') {
                try {
                    $person = $this->personRepository->findPersonByType("demoSexOffender");
                }
                catch (\Exception $e) {
                    $formData->firstName = "Mike";
                    $formData->lastName = "Tucker";
                    $formData->email = "lmtucker@email.com";
                    $formData->birthDate = "07-07-1969";
                    $formData->address = "123 North Main Street Chicago, IL 12345";
                    $formData->type = "demoSexOffender"; 
                }
            }

            if ($formData->testScenario == 'blacklist') {
                try {
                    $person = $this->personRepository->findPersonByType("demoBlackList");
                }
                catch (\Exception $e) {
                    $formData->firstName = "Allison";
                    $formData->lastName = "Hayes";
                    $formData->email = "ahayes@email.com";
                    $formData->birthDate = "03-10-1982";
                    $formData->address = "123 East Main Street Cleveland, OH 12345";
                    $formData->type = "demoBlackList";
                    $formData->blacklist = true;
                }
            }

            if ($formData->testScenario == 'sexOffenderBlacklist') {
                try {
                    $person = $this->personRepository->findPersonByType("demoSexOffenderBlacklist");
                }
                catch (\Exception $e) {
                    $formData->firstName = "Thomas";
                    $formData->lastName = "Brown";
                    $formData->email = "tbornw@email.com";
                    $formData->birthDate = "03-26-1968";
                    $formData->address = "123 Main Street Miami, FL 12345";
                    $formData->type = "demoSexOffenderBlacklist";
                    $formData->blacklist = true;
                }
            }
        }

        // create new person
        if (is_null($person)) {
            if (!isset($formData->firstName) || !isset($formData->lastName)) {
                throw new Exceptions\BadRequestException('Please provide information for creating a new visitor.');
            }

            $person = new Person();
            $person->setStatus(1);
            $person->setType($formData->type ?? "visitor");
            $name = $person->getName();
            $name->setGivenName($formData->firstName);
            $name->setFamilyName($formData->lastName);
            $person->setName($name);

            if (isset($formData->birthDate)) {
                $date = \DateTime::createFromFormat("m-d-Y", $formData->birthDate);
                $person->getDemographics()->setBirthDate($date);
            }

            if (isset($formData->identificationId)) {
                $identification = new Identification();
                $identification->setId($formData->identificationId);
                $person->addIdentification($identification);
            }

            if (isset($formData->address)) {
                $address = new PersonAddress($formData->address);
                $person->setAddress($address);
            }

            if (isset($formData->picture)) {
                $visitorSettings = $person->getVisitorSettings();
                $visitorSettings->setPicture($formData->picture);
            }

            if (isset($formData->students) && is_array($formData->students)) {
                foreach ($formData->students as $studentId) {
                    $student = $this->studentRepository->findStudentOfId((int) $studentId);
                    $person->addStudent($student, 1);
                }
            }

            $this->personRepository->save($person);

            // The reason this has to be done as a separate save call is because 
            // the person ID needs to be set to the email's source column.
            // I don't yet know what the point of the column is.
            if (isset($formData->email)) {
                $email = new PersonEmail();
                $email->setEmailAddress($formData->email);
                $email->setPerson($person);
                $email->setSource($person->getPersonId());
                $person->setEmail($email);
                $this->personRepository->save($person);
            }

            // Add blacklist for test scenarios
            if (isset($formData->blacklist)) {
                $blItem = new BlacklistItem();
                $blItem->personId = $person->getPersonId();
                $blItem->userId = (int) $this->token->id;
                $blItem->buildingId = (int) $this->token->building;
                $blItem->reason = "Reason";
                $blItem->setPerson($person);
                $person->addBlacklistItem($blItem);

                $this->personRepository->save($person);
            }
        }

        $visit = new Visit();
        $visit->setPerson($person);
        $building = $this->buildingRepository->findBuildingOfId((int) $this->token->building);
        $visit->setBuilding($building);
        $visit->setUserId((int) $this->token->id);

        if (isset($formData->notes)) {
            $visit->setNotes($formData->notes);
        }
        $this->visitRepository->save($visit);
        $newId = $visit->getId();
        $this->logger->info("Visit of id `${newId}` was created.");

        $newVisit = $this->visitRepository->findVisitOfId($newId);
        
        return $this->respondWithData(['visit' => $newVisit, 'visitHistory' => $person->getVisits(), 'students' => $this->studentRepository->findAll()]);
    }
}

/**
 * @OA\Post(
 *     path="/visits",
 *     tags={"visits"},
 *     @OA\Response(
 *         response=200,
 *         description="New Visit",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             example={"statusCode": 200, 
 *                      "data": {
 *                          "id": 14,
 *                          "buildingId": 5240,
 *                          "userId": 200000127,
 *                          "notes": "hello",
 *                          "requiresApproval": false,
 *                          "approved": false,
 *                          "securityAlerted": false,
 *                          "dateCreated": {
 *                              "date": "2020-05-27 17:42:39.725931",
 *                              "timezone_type": 3,
 *                              "timezone": "UTC"
 *                          },
 *                          "checkIn": null,
 *                          "checkOut": null,
 *                          "estimatedCheckIn": null,
 *                          "estimatedCheckOut": null,
 *                          "visitor": {
 *                              "personId": 3434,
 *                              "firstName": "Ewan",
 *                              "lastName": "McGregor",
 *                              "emailAddress": "ewan@onlyhope.com",
 *                              "blacklist": null
 *                          }
 *                       }}
 *         )
 *     ),
 *     @OA\RequestBody(
 *         description="Create new Visit",
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                  @OA\Property(
 *                     property="personId",
 *                     description="Id of person to use as Visitor (optional)",
 *                     type="integer"
 *                 ),
 *                  @OA\Property(
 *                     property="identificationId",
 *                     description="Unique ID from Scan. Will create new person or use existing person. (optional)",
 *                     type="string"
 *                 ),
 *                  @OA\Property(
 *                     property="firstName",
 *                     description="Used if no person/identification ID is provided.",
 *                     type="string"
 *                 ),
 *                 @OA\Property(
 *                     property="lastName",
 *                     description="Used if no person/identification ID is provided.",
 *                     type="string"
 *                 ),
 *                  @OA\Property(
 *                     property="email",
 *                     description="Used if no person/identification ID is provided.",
 *                     type="string"
 *                 ),
 *                  @OA\Property(
 *                     property="notes",
 *                     type="string"
 *                 ),
 *                  @OA\Property(
 *                     property="testScenario",
 *                     description="Pass in to demo different scenarios. Possible values: ['demoCleanVisitor', 'demoSexOffender', 'demoBlackList', 'demoSexOffenderBlacklist']",
 *                     type="string"
 *                 ),
 *              )
 *         ),
 *     )
 * )
 */
