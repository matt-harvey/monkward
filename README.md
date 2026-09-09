# monkward

An on-the-fly Markdown viewer for your current directory. Run `monkward`, it
starts a local server (PHP's built-in `php -S`), opens your browser at
`http://localhost:8800`, and renders every `.md` file in that directory tree as
styled HTML. Directories with no markdown are never shown.

## Quick start

Prereqs:
* PHP 8.5+
* `composer`.

Install:

```bash
git clone <this-repo> && cd <this-repo>
make install
```

Run:

```
monkward
```

`make install` builds a PHAR and drops it in `~/.local/bin`
(override with `make install PREFIX=/somewhere`), so `monkward` is on your
`PATH` immediately. Requires PHP 8.5+, Composer, and `make`.

No `make`? Build the PHAR by hand and put it anywhere on your `PATH`:

```bash
composer install
composer build:phar
cp build/monkward.phar ~/.local/bin/monkward
```

## Usage

```bash
monkward                     # serve the current directory
monkward docs/               # serve docs/ recursively
monkward README.md           # serve a single markdown file

monkward --theme=yeah        # use ~/.config/monkward/themes/yeah.css
monkward --port=9000         # serve on a different port
monkward --host=0.0.0.0      # bind a different host
monkward --ignore=build      # also ignore a directory name (repeatable)
monkward --include=vendor    # re-include a default-ignored directory
monkward --no-browser        # don't open a browser
monkward --help              # full help
```

Files are re-read on every request, so edits show up on refresh. Stop the
server with `Ctrl+C`.

## Themes and configuration

Drop stylesheets in `~/.config/monkward/themes/`:

```
~/.config/monkward/themes/
└── yeah.css
```

Use one with `--theme=yeah`, or make it your default in
`~/.config/monkward/config.toml`:

```toml
theme = "yeah"
port = 8800
host = "127.0.0.1"
ignore = ["build", "tmp"]
include = ["vendor"]
```

Command-line flags override the config file. With no configuration at all,
monkward ships with its own default theme (light and dark variants).

## Ignored directories

By default monkward skips `.git`, `.svn`, `.hg`, `vendor`, `node_modules`,
`bower_components`, and any hidden directory. Ignored directories are neither
listed nor served when addressed directly. Add more with `--ignore`
(repeatable or comma-separated); re-include one with `--include`:

```bash
monkward --ignore=build,tmp --include=vendor
```

## Development

```bash
make test       # run the test suite
make build      # build build/monkward.phar
make install    # build + install to ~/.local/bin
make uninstall  # remove the installed phar
make clean      # remove build artifacts
```

## How it works

- The CLI resolves the target, config and theme, then spawns
  `php -S <host>:<port> -t <temp-docroot> <router>` and opens your browser.
- The HTTP layer is built on [`substancephp/http`](https://packagist.org/packages/substancephp/http)
  (PSR-15 middleware, `HtmlRenderer` templates, exception handling) and
  [`substancephp/container`](https://packagist.org/packages/substancephp/container)
  (PSR-11 DI), with a small custom matcher/actor substituted in place of the
  library's filepath routing. Markdown is rendered by
  [`league/commonmark`](https://packagist.org/packages/league/commonmark).
- The PHAR is built with [`humbug/box`](https://packagist.org/packages/humbug/box);
  when `php -S` executes the PHAR its stub boots the HTTP router directly.

## License

MIT
