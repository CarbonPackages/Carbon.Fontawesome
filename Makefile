.PHONY: help install build prettier dev watch prepare

.DEFAULT_GOAL := pipeline

## Install dependencies, prettier and build
pipeline: install prettier build

## Install dependencies
install:
	pnpm install

## Prettier files
prettier:
	pnpm prettier --write --no-error-on-unmatched-pattern '{*,**/*}.{mjs,php,yaml,pcss,ts,js,jsx,json,md}'

prepare:
	@echo "  Prepare file system..."
	@rm -rf Resources/Public

## Create icon database (re-write of existing icon files) and create editor
build:
	@make prepare
	@pnpm build

## Create icon database (no re-write of existing icon files) and create editor
dev:
	@make prepare
	@pnpm dev

## Watch for editor changes and rebuild
watch:
	@make prepare
	@pnpm watch

# Define colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

# define indention for descriptions
TARGET_MAX_CHAR_NUM=10

help:
	@echo ''
	@echo '${GREEN}CLI command list:${RESET}'
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk '/^[a-zA-Z\-\_0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")-1); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "  ${YELLOW}%-$(TARGET_MAX_CHAR_NUM)s${RESET} ${GREEN}%s${RESET}\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)
	@echo ''
