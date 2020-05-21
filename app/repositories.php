<?php
declare(strict_types=1);

use Doctrine\DBAL\Types\Type;

Type::overrideType('datetime', 'Doctrine\DBAL\Types\VarDateTimeType');
Type::overrideType('datetimetz', 'Doctrine\DBAL\Types\VarDateTimeType');
Type::overrideType('time', 'Doctrine\DBAL\Types\VarDateTimeType');

Type::overrideType('datetime_immutable', 'Doctrine\DBAL\Types\VarDateTimeImmutableType');
Type::overrideType('datetimetz_immutable', 'Doctrine\DBAL\Types\VarDateTimeImmutableType');
Type::overrideType('time_immutable', 'Doctrine\DBAL\Types\VarDateTimeImmutableType');

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Domain\Person\PersonRepository;
use App\Infrastructure\Persistence\Person\SqlPersonRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\User\SqlUserRepository;
use App\Domain\Visit\VisitRepository;
use App\Infrastructure\Persistence\Visit\SqlVisitRepository;
use App\Domain\NotificationGroup\NotificationGroupRepository;
use App\Infrastructure\Persistence\NotificationGroup\SqlNotificationGroupRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        PersonRepository::class => function(LoggerInterface $logger, EntityManagerInterface $em) {
            return new SqlPersonRepository($logger, $em);
        },
        UserRepository::class => function(LoggerInterface $logger, EntityManagerInterface $em) {
            return new SqlUserRepository($logger, $em);
        },
        VisitRepository::class => function(LoggerInterface $logger, EntityManagerInterface $em) {
            return new SqlVisitRepository($logger, $em);
        },
        NotificationGroupRepository::class => function(LoggerInterface $logger, EntityManagerInterface $em) {
            return new SqlNotificationGroupRepository($logger, $em);
        },
    ]);
};