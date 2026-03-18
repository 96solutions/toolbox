PHP_IMAGE := toolbox-php84
DOCKER_BUILD_ARGS := --build-arg UID=$(shell id -u) --build-arg GID=$(shell id -g)

ifdef DOCKER_ENV
    RUN :=
else
    RUN := docker run --rm -v $(PWD):/app $(PHP_IMAGE)
endif

build:
	docker build --pull=false $(DOCKER_BUILD_ARGS) -t $(PHP_IMAGE) .docker/php8.4/

test:
	$(RUN) vendor/bin/phpunit

stan:
	$(RUN) vendor/bin/phpstan analyse --configuration=phpstan.neon

ecs:
	$(RUN) vendor/bin/ecs check --config=ecs.php

ecs-fix:
	$(RUN) vendor/bin/ecs check --config=ecs.php --fix

check: test stan ecs

.PHONY: build test stan ecs ecs-fix check
