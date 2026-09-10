# monkward

[![CI](https://github.com/matt-harvey/monkward/actions/workflows/ci.yml/badge.svg)](https://github.com/matt-harvey/monkward/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/matt-harvey/monkward/branch/main/graph/badge.svg)](https://codecov.io/gh/matt-harvey/monkward)

It's 2026, and you&#8217;re drowning in Markdown files. You need a Markdown browser. One that:
* Starts-and-opens itself in your browser with a single terminal command
* Renders nice web pages on the fly
* Comes with light and dark mode out of the box
* Lets you add or override those themes with your own stylesheets
* Lets you browse either a whole directory, or just one file
* Comes with sensible, overridable defaults. (Another `monkward` session hogging `:8800`? Just pass `--port=8801`.)

## Quick start

## Prereqs
* PHP 8.5+
* `composer`

## Install

```bash
git clone <this-repo> && cd <this-repo>
make install
```

`make install` builds a PHAR and puts it in `~/.local/bin`
(override with `make install PREFIX=/somewhere`). (You need to add that directory to your
`PATH` if it isn&#8217;t already.) It also initializes `~/.config/monkward/` with an editable
`config.toml` and the built-in `light.css` / `dark.css` themes.

## Usage

Start the monkward server in your current directory, serving to `localhost:8080`:

```
monkward
```

Tell it to open in your default browser at the same time:

```
monkward --open
```

All the options:

```bash
monkward                     # serve the current directory
monkward docs/               # browse docs/ (click into subdirectories)
monkward README.md           # serve a single markdown file
monkward --open              # also open the default browser at the URL

monkward --theme=yeah        # use ~/.config/monkward/themes/yeah.css
monkward --port=9000         # serve on a different port
monkward --host=0.0.0.0      # bind a different host
monkward --init              # (re)create ~/.config/monkward with the defaults
monkward --help              # full help
```

Files are re-read on every request, so edits show up on refresh. Stop the
server with `Ctrl+C`.

When opened in a directory, monkward renders a one-level file browser:
directories first (click to descend), then files. Markdown files are
highlighted and clickable; other file names are shown but not linked.

## Themes and configuration

Installing monkward sets up `~/.config/monkward/` for you:

```
~/.config/monkward/
├── config.toml
└── themes/
    ├── light.css
    └── dark.css
```

`config.toml` starts with the prebaked defaults. You can edit it freely:

```toml
theme = "light"
port = 8800
host = "127.0.0.1"
heading_ids = true
```

`light.css` and `dark.css` are copied there so you can see and tweak them.
Drop any other stylesheet next to them (`yeah.css`, etc.) and it shows up in
the theme dropdown at the top of every page. The dropdown choice is remembered
in your browser; `theme` in `config.toml` (or `--theme=yeah` for a single run)
just picks the default when nothing has been chosen yet. Command-line flags
override the config file.

`heading_ids = true` adds `id="..."` attributes to rendered headings so
`[links](#anchors)` work. Set it to `false` to leave headings untouched.

## How it works

monkward starts the built-in PHP web server, listening at localhost. It intercepts each
request, using `league/commonmark` to convert any markdown files it encounters into HTML
on the fly. `substancephp/http` and `substancephp/container` are used for some of the
plumbing in between. Some JavaScript is inlined to manage the theme selector.

## Development

```bash
make test       # run the test suite
make build      # build build/monkward.phar
make install    # build + install to ~/.local/bin
make uninstall  # remove the installed phar and ~/.config/monkward
make clean      # remove build artifacts
```

Pull requests and Issues are welcome.

## License

MIT
