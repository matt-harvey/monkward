# monkward

An on-the-fly Markdown viewer for your current directory. Run `monkward`; it
starts a local server, opens your browser at
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
`PATH` immediately. It also initializes `~/.config/monkward/` with an editable
`config.toml` and `themes/default.css`.

## Usage

```bash
monkward                     # serve the current directory
monkward docs/               # serve docs/ recursively
monkward README.md           # serve a single markdown file

monkward --theme=yeah        # use ~/.config/monkward/themes/yeah.css
monkward --port=9000         # serve on a different port
monkward --host=0.0.0.0      # bind a different host
monkward --ignore=build      # also ignore a directory name for this run
monkward --init              # (re)create ~/.config/monkward with the defaults
monkward --no-browser        # don't open a browser
monkward --help              # full help
```

Files are re-read on every request, so edits show up on refresh. Stop the
server with `Ctrl+C`.

## Themes and configuration

Installing monkward sets up `~/.config/monkward/` for you:

```
~/.config/monkward/
├── config.toml
└── themes/
    └── default.css
```

`config.toml` starts with the prebaked defaults. You can edit it freely:

```toml
theme = "default"
port = 8800
host = "127.0.0.1"
ignore = [".git", ".svn", ".hg", ".idea", ".vscode", "vendor", "node_modules", "bower_components"]
```

`themes/default.css` is the default theme, copied there so you can see and
tweak it. Add your own stylesheets next to it (`yeah.css`, etc.) and point
`theme` at one; or pass `--theme=yeah` for a single run. Command-line flags
override the config file.

## Ignored directories

The `ignore` key in `config.toml` is the full list of directory names monkward
skips. You can edit this list as you see fit. For a one-off directory ignoring, you can pass
`--ignore=other_dir` to additionally ignore `other_dir` for that run.

```bash
monkward --ignore=build,tmp
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
