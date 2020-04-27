<?php
declare(strict_types=1);

//TODO: http://php-di.org/doc/lazy-injection.html - interesting concept of lazy injecting other container services into an object
// http://php-di.org/doc/php-definitions.html#autowired-objects

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;


use App\Classes\DatabaseConnection;
use App\Classes\RedisConnector;
use App\Classes\TokenProcessor;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        'Logger' => function (ContainerInterface $c) {
            $settings = $c->get('settings');

            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        LoggerInterface::class => DI\get('Logger'),

        'CoreDB' => function (ContainerInterface $c, LoggerInterface $logger) {
            $config = $c->get('settings')['database'];
            $db = new DatabaseConnection($config, $logger);
            return $db;
        },
        DatabaseConnection::class => DI\get('CoreDB'),

        'DistrictDB' => function (ContainerInterface $c, LoggerInterface $logger) {
            $config = $c->get('settings')['database'];
            $secureId = $c->get('SecureId');
            $db = new DistrictDatabaseConnection($secureId, $config, $logger);
            return $db;
        },
        DistrictDatabaseConnection::class => DI\get('CoreDB'),

        RedisConnector::class => function (ContainerInterface $c) {
            $config = $c->get('settings')['redis'];
            $redis = new RedisConnector(null, $config, $logger);
            return $redis;
        },

        TokenProcessor::class => function (ContainerInterface $c, RedisConnector $redis) {
            return new TokenProcessor($redis);
        },

    ]);
};
