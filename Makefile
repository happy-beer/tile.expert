DOCKER_COMPOSE=docker compose
APP_SERVICE=app

.PHONY: up down build logs ps bash composer install

up:
	$(DOCKER_COMPOSE) up -d --build

down:
	$(DOCKER_COMPOSE) down

build:
	$(DOCKER_COMPOSE) build

logs:
	$(DOCKER_COMPOSE) logs -f

ps:
	$(DOCKER_COMPOSE) ps

bash:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) sh

composer:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) composer $(cmd)

install:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) composer install
