COMPOSER ?= composer
PHP ?= php
PREFIX ?= $(HOME)/.local
BINDIR ?= $(PREFIX)/bin
PHAR := build/monkward.phar

.DEFAULT_GOAL := help

.PHONY: help deps test build install uninstall clean

help:
	@echo "monkward — available targets:"
	@echo "  make deps       Install PHP dependencies (composer install)"
	@echo "  make test       Run the test suite (composer test)"
	@echo "  make build      Build $(PHAR) (composer build:phar)"
	@echo "  make install    Build and install the phar to $(BINDIR)"
	@echo "  make uninstall  Remove the installed phar"
	@echo "  make clean      Remove build artifacts"
	@echo ""
	@echo "Variables: PREFIX (default $(HOME)/.local), BINDIR (default \$$(PREFIX)/bin), COMPOSER, PHP"

deps:
	$(COMPOSER) install

test: deps
	$(COMPOSER) test

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
	@rm -f "$(BINDIR)/monkward"
	@echo "Removed: $(BINDIR)/monkward"

clean:
	@rm -rf build .phpunit.cache
