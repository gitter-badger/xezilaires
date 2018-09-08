ifndef BUILD_ENV
BUILD_ENV=php7.2
endif

QA_DOCKER_IMAGE=jakzal/phpqa:${BUILD_ENV}-alpine
QA_DOCKER_COMMAND=docker run --init --interactive --tty --rm --env "COMPOSER_HOME=/composer" --user "$(shell id -u):$(shell id -g)" --volume /tmp/tmp-phpqa-$(shell id -u):/tmp --volume "$(shell pwd):/project" --volume "${HOME}/.composer:/composer" --workdir /project ${QA_DOCKER_IMAGE}

dist: composer-validate cs phpstan psalm test benchmark
ci: check test benchmark
check: composer-validate cs-check phpstan psalm
test: phpunit-coverage infection

composer-validate: ensure
	sh -c "${QA_DOCKER_COMMAND} composer validate"

composer-install: fetch ensure
	sh -c "${QA_DOCKER_COMMAND} composer upgrade"

composer-install-lowest: fetch ensure
	sh -c "${QA_DOCKER_COMMAND} composer upgrade --prefer-lowest"

cs: ensure
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix --using-cache=false --diff -vvv"

cs-check: ensure
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix --using-cache=false --dry-run --diff -vvv"

phpstan: ensure
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse"

psalm: ensure
	sh -c "${QA_DOCKER_COMMAND} psalm --show-info=false"

infection: phpunit-coverage
	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr /usr/local/bin/infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/ --min-msi=100 --min-covered-msi=100"

phpunit-coverage: ensure
	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpunit.junit.xml --coverage-xml var/coverage-xml/"

phpunit: ensure
	sh -c "${QA_DOCKER_COMMAND} phpunit --verbose"

benchmark: ensure
	sh -c "${QA_DOCKER_COMMAND} phpbench run --store --iterations=10 --report=aggregate"

ensure:
	mkdir -p ${HOME}/.composer /tmp/tmp-phpqa-$(shell id -u)

fetch:
	docker pull "${QA_DOCKER_IMAGE}"
