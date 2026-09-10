COMPOSER ?= composer
PHP ?= php
PREFIX ?= $(HOME)/.local
BINDIR ?= $(PREFIX)/bin
CONFIG_DIR ?= $(if $(XDG_CONFIG_HOME),$(XDG_CONFIG_HOME)/monkward,$(HOME)/.config/monkward)
PHAR := build/monkward.phar

.DEFAULT_GOAL := help

.PHONY: help deps test test-coverage build install uninstall clean

help:
	@echo "monkward — available targets:"
	@echo "  make deps           Install PHP dependencies (composer install)"
	@echo "  make test           Run the test suite (composer test)"
	@echo "  make test-coverage  Run the test suite and print line coverage"
	@echo "  make build          Build $(PHAR) (composer build:phar)"
	@echo "  make install        Build and install the phar to $(BINDIR)"
	@echo "  make uninstall      Remove the installed phar and $(CONFIG_DIR)"
	@echo "  make clean          Remove build artifacts"
	@echo ""
	@echo "Variables: PREFIX (default $(HOME)/.local), BINDIR (default \$$(PREFIX)/bin),"
	@echo "          CONFIG_DIR (default \$$(XDG_CONFIG_HOME) or \$$(HOME)/.config, plus /monkward), COMPOSER, PHP"

deps:
	$(COMPOSER) install

test: deps
	$(COMPOSER) test

test-coverage: deps
	XDEBUG_MODE=coverage $(COMPOSER) test:coverage

build: deps
	$(COMPOSER) build:phar

install: build
	@test -n "$(BINDIR)" || { echo "Error: BINDIR is empty (is HOME set?)"; exit 1; }
	@mkdir -p "$(BINDIR)"
	@install -m 0755 "$(PHAR)" "$(BINDIR)/monkward"
	@echo "Installed: $(BINDIR)/monkward"
	@$(PHP) "$(BINDIR)/monkward" --version
	@$(PHP) "$(BINDIR)/monkward" --init
	@case ":$${PATH}:" in \
		*":$(BINDIR):"*) echo "Ready — run: monkward";; \
		*) echo "Note: $(BINDIR) is not on your PATH";; \
	esac

uninstall:
	@test -n "$(BINDIR)" || { echo "Error: BINDIR is empty (is HOME set?)"; exit 1; }
	@test -n "$(CONFIG_DIR)" || { echo "Error: CONFIG_DIR is empty (is HOME set?)"; exit 1; }
	@rm -f "$(BINDIR)/monkward"
	@rm -rf "$(CONFIG_DIR)"
	@echo "Removed: $(BINDIR)/monkward"
	@echo "Removed: $(CONFIG_DIR)"

clean:
	@rm -rf build .phpunit.cache
