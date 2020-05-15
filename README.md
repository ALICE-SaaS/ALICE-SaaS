# Visitor Management Service

[![SonarCloud Status](https://sonarcloud.io/api/project_badges/measure?project=ALICE-SaaS_visitor-management-service&metric=alert_status&token=3138a23fd3451991e83eda8940adb84e4fc30188)](https://sonarcloud.io/dashboard?id=ALICE-SaaS_visitor-management-service)

[![CI Status](https://github.com/ALICE-SaaS/visitor-management-service/workflows/CI/badge.svg)](https://github.com/ALICE-SaaS/visitor-management-service/actions)

## Environment Setup

Place a file named `.env` in the root directory, in which you can configure the path to your PPK key to enable the SSH tunnel for the RDS connection:

```env
PPK_FILE=C:\Users\ScottCollier\.ssh\laureninnovations-us-east-2-test.ppk
```

You can also configure port mappings for the various containers in the Compose stack, in case you happen to already have running applications on the standard ports (80/8080/5432):

```env
SERVICE_PORT=8081
INGRESS_PORT=81
POSTGRES_PORT=5433
```

## Running in Docker

As long as your `.env` file is configured as documented above, just run `docker-compose up`.  The application will be available at `localhost:8080`.

## Running Tests

The easiest way to run the unit test suite is via the `Makefile`:

```bash
make test
```

Under the hood, the `Makefile` just runs the following (which you can also run from the command line):

```bash
docker-compose run --rm visitor-management sh -c ./vendor/bin/phpunit
```

Of course if you have PHP/Composer installed on your local system, you can just run the tests locally via your normal PHP/Composer/IDE workflow.

## Local DNS

If you would like to access the services locally via a friendly name, e.g., `api.navigate360.com`, just add an entry as follows to your OS's `hosts` file:

```env
127.0.0.1   api.navigate360.com
```

The Compose stack now contains an Nginx reverse proxy which acts like an ingress controller and watches the Docker API for container port mappings.

Since the Visitor Management service runs on port 8080, you can then send a request like:

```bash
curl -X GET api.navigate360.com:8080/persons/1
```

## Configuring the Debugger with Docker

The XDebug configuration is automatically created in the container.  From there, you simply need to configure your editor/IDE.

Here's an example launch configuration for VSCode:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Docker XDebug",
      "type": "php",
      "request": "launch",
      "pathMappings": {
          "/var/www": "${workspaceRoot}"
      },
      "port": 9000,
      "log": true
    }
  ]
}
```
