# monkward

An on-the-fly Markdown viewer for your current directory. Run `monkward` inside a
directory and it starts a local server (PHP's built-in `php -S`), opens your
browser at `http://localhost:8800`, and renders every `.md` file in that
directory tree as styled HTML. Directories that contain no markdown are never
shown.

## Requirements

- PHP **8.5** or newer
- Composer (to install/build from source)

## Install

### From source

```bash
git clone <this-repo> && cd <this-repo>
composer install --no-dev --optimize-autoloader
```

Then either run `bin/monkward` directly or build the PHAR:

```bash
composer install            # includes dev dependencies (phpunit, box)
composer build:phar         # produces build/monkward.phar
```

Install the PHAR anywhere on your `PATH`, e.g.:

```bash
cp build/monkward.phar ~/.local/bin/monkward
chmod +x ~/.local/bin/monkward
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

While the server runs, files are read on every request, so edits show up on
refresh. Stop the server with `Ctrl+C`.

## Ignored directories

By default monkward skips `.git`, `.svn`, `.hg`, `vendor`, `node_modules`,
`bower_components`, and any hidden directory. Ignored directories are neither
listed nor served when addressed directly.

Add more with `--ignore` (repeatable, or comma-separated) or the `ignore` key in
`config.toml`. Re-include a default-ignored directory with `--include` or the
`include` key:

```bash
monkward --ignore=build,tmp --include=vendor
```

## Themes

Put stylesheets in `~/.config/monkward/themes/`:

```
~/.config/monkward/themes/
└── yeah.css
```

Then run `monkward --theme=yeah`, or make it your default via
`~/.config/monkward/config.toml`:

```toml
theme = "yeah"
port = 8800
host = "127.0.0.1"
ignore = ["build", "tmp"]
include = ["vendor"]
```

Command-line flags override the config file. Without any configuration,
monkward ships with its own default theme (light and dark variants).

## Development

```bash
composer install
composer test
```

## How it works

- The CLI (`bin/monkward`) resolves the target, config and theme, generates a
  small routing tree, then spawns `php -S <host>:<port> -t <temp-docroot> <router>`.
- The HTTP layer is built on [`substancephp/http`](https://packagist.org/packages/substancephp/http)
  (PSR-15 middleware, filepath routing, `HtmlRenderer` templates) and
  [`substancephp/container`](https://packagist.org/packages/substancephp/container)
  (PSR-11 dependency injection). Markdown is rendered by
  [`league/commonmark`](https://packagist.org/packages/league/commonmark).
- The PHAR is built with [`humbug/box`](https://packagist.org/packages/humbug/box);
  when the PHAR is executed by `php -S` its stub boots the HTTP router directly.

## License

MIT
