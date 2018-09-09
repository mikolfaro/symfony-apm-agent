all: style tests
tests: test-unit test-integration
style: phpcs phpstan

# Make configuration
.PHONY: phpstan phpcs test-unit test-integration test-acceptance ci-acceptance-environment ci-dependencies
SHELL = /bin/sh

# Style
phpstan:
	./vendor/bin/phpstan analyse -l 7 src
	./vendor/bin/phpstan analyse -l 4 tests
phpcs:
	./vendor/bin/phpcs

# Tests
test-unit:
	mkdir -p build/unit && \
	./vendor/bin/phpunit --testsuite=unit --log-junit build/unit/results.xml
test-integration:
	mkdir -p build/integration && \
	./vendor/bin/phpunit --testsuite=integration --log-junit build/integration/results.xml

# CircleCI specific settings
ci-acceptance-environment:
	sudo sysctl -w vm.max_map_count=262144
ci-dependencies:
	sudo composer self-update && \
    composer config -g github-oauth.github.com $$GITHUB_TOKEN && \
	composer install -n -o;
