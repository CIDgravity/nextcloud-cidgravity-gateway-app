app_name=cidgravitygateway
project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
sign_dir=$(build_dir)/sign
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
package_name=$(app_name)
cert_dir=$(HOME)/certs
build_tools_directory=$(CURDIR)/build/tools
composer=$(shell which composer 2> /dev/null)

all: dev-setup lint build-js-production test

# Dev env management
dev-setup: clean clean-dev composer npm-init krankerl-install


# Installs and updates the composer dependencies. 
# If composer is not installed a copy is fetched from the web
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist
	php $(build_tools_directory)/composer.phar update --prefer-dist
else
	composer install --prefer-dist
	composer update --prefer-dist
endif

npm-init:
	npm ci

npm-update:
	npm update

krankerl-install:
	cargo install --git https://github.com/ChristophWurst/krankerl

# Building
build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

# Linting
lint:
	npm run lint

lint-fix:
	npm run lint:fix

# Style linting
stylelint:
	npm run stylelint

stylelint-fix:
	npm run stylelint:fix

# Cleaning
clean:
	rm -rf js/*

clean-dev:
	rm -rf node_modules