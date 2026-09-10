# monkward

It's 2026 and you're drowning in Markdown files. You need a Markdown browser. One that:
* Renders on the fly
* Is invoked with one command
* Blasts nicely rendered pages into your web browser
* Lets you browse either a whole directory, or just one file
* Has sensible but easily overridable defaults (theming, URL, port...)

## Quick start

## Prereqs
* PHP 8.5+
* `composer`

## Install

```bash
git clone <this-repo> && cd <this-repo>
make install
```

`make install` builds a PHAR and drops it in `~/.local/bin`
(override with `make install PREFIX=/somewhere`), so `monkward` is on your
`PATH` immediately. It also initializes `~/.config/monkward/` with an editable
`config.toml` and `themes/default.css`.

## Usage

Starts the monkward server in your current directory, serving to `localhost:8080`:

```
monkward
```

Options:

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
    └── default.css
```

`config.toml` starts with the prebaked defaults. You can edit it freely:

```toml
theme = "default"
port = 8800
host = "127.0.0.1"
heading_ids = true
```

`themes/default.css` is the default theme, copied there so you can see and
tweak it. Add your own stylesheets next to it (`yeah.css`, etc.) and point
`theme` at one; or pass `--theme=yeah` for a single run. Command-line flags
override the config file.

`heading_ids = true` adds `id="..."` attributes to rendered headings so
`[links](#anchors)` work. Set it to `false` to leave headings untouched.

## Development

```bash
make test       # run the test suite
make build      # build build/monkward.phar
make install    # build + install to ~/.local/bin
make uninstall  # remove the installed phar
make clean      # remove build artifacts
```

## License

MIT
