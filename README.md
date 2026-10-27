# School OS

Laravel-based School OS application with a PostgreSQL database.

## Requirements

- Git
- Docker Engine
- Docker Compose plugin

## Setup on a new computer

Clone the featured branch:

    git clone -b featured https://github.com/Toptraits/Account.git
    cd Account

Create a local environment file:

    cp .env.example .env
    chmod 600 .env

Edit `.env` and set:
- APP_KEY to a unique Laravel application key
- APP_URL to http://localhost:8001
- DB_PASSWORD to a strong local database password
- DB_DATABASE to school_os_restore
- DB_USERNAME to school_os_user
- DB_HOST to db
- DB_PORT to 5432

Use the same DB_PASSWORD in the application environment and PostgreSQL configuration. Never commit `.env`.

Build and start the application:

    docker compose up --build -d

Run database migrations:

    docker compose exec school-os php artisan migrate --force

Open the interface:

    http://localhost:8001/school-os.html

Check service status:

    docker compose ps

View application logs:

    docker compose logs --tail=100 school-os

Stop the application without deleting the database:

    docker compose stop

Start it again:

    docker compose up -d

## Database persistence

PostgreSQL data is stored in a Docker named volume. Do not run
`docker compose down -v` unless you intentionally want to delete that volume.

Each developer has their own local environment file and database.
