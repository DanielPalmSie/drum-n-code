#!/bin/bash

# Running containers and log output
echo "Launching containers..."
docker-compose up -d
docker-compose logs -f &

# Replace DATABASE_URL value with container name
CONTAINER_DB=$(docker-compose ps -q mysql | xargs docker inspect -f '{{ .Name }}' | cut -c2-)
sed -i.bak "s/db_container/$CONTAINER_DB/g" ./project/.env


# Running commands in the php container and outputting logs
echo "Installing Composer Dependencies..."
docker exec -it $(docker ps -qf "name=php") sh -c "composer install"
echo "Performing DB Migrations..."
docker exec -it $(docker ps -qf "name=php") sh -c "php bin/console doctrine:migrations:migrate --no-interaction"
echo "Performing test database migrations..."
docker exec -it $(docker ps -qf "name=php") sh -c "php bin/console --env=test doctrine:schema:create --no-interaction"

# Outputs a message about a successful start
echo -e "\033[0;32mThe project went up successfully!\033[0m"