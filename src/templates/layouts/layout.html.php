<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $title */
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $this->h($title) ?> · monkward</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <script>
            (function () {
                let saved = null;
                try {
                    saved = localStorage.getItem('monkward-theme');
                } catch (error) {
                    // storage unavailable; keep the server default
                }

                const href = saved
                    ? '/monkward-theme.css?theme=' + encodeURIComponent(saved)
                    : '/monkward-theme.css';

                document.write('<link rel="stylesheet" id="theme-style" href="' + href + '">');
            })();
        </script>

        <noscript><link rel="stylesheet" href="/monkward-theme.css"></noscript>
    </head>

    <body>
        <header class="site-header">
            <div class="site-header-inner">
                <a class="brand" href="/">monkward</a>
                <nav class="site-nav"><?= $this->fetch('site-nav') ?></nav>
                <div class="theme-picker" id="theme-picker">
                    <span class="theme-picker-label">theme</span>
                    <button
                        type="button"
                        class="theme-select"
                        id="theme-toggle"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span id="theme-current">…</span>
                        <span class="theme-caret" aria-hidden="true">▾</span>
                    </button>
                    <ul class="theme-menu" id="theme-menu" role="listbox" aria-label="Theme" hidden></ul>
                </div>
            </div>
        </header>

        <main class="site-main">
            <?= $this->content() ?>
        </main>

        <footer class="site-footer">
            <span>rendered on the fly · monkward</span>
        </footer>

        <script>
            (function () {
                const KEY = 'monkward-theme';
                const picker = document.getElementById('theme-picker');
                const toggle = document.getElementById('theme-toggle');
                const currentLabel = document.getElementById('theme-current');
                const menu = document.getElementById('theme-menu');
                const link = document.getElementById('theme-style');

                function readSaved() {
                    try {
                        return localStorage.getItem(KEY);
                    } catch (error) {
                        return null;
                    }
                }

                function apply(name) {
                    link.href = '/monkward-theme.css?theme=' + encodeURIComponent(name);
                }

                function close() {
                    menu.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                }

                function select(name) {
                    currentLabel.textContent = name;
                    apply(name);

                    try {
                        localStorage.setItem(KEY, name);
                    } catch (error) {
                        // storage unavailable; keep the choice for this page only
                    }

                    menu.querySelectorAll('.theme-option').forEach((option) => {
                        option.setAttribute(
                            'aria-selected',
                            option.getAttribute('data-value') === name ? 'true' : 'false',
                        );
                    });

                    close();
                }

                fetch('/monkward-themes.json')
                    .then((response) => response.json())
                    .then((data) => {
                        const saved = readSaved();
                        const current = saved && data.themes.includes(saved) ? saved : data.default;

                        data.themes.forEach((name) => {
                            const option = document.createElement('button');
                            option.type = 'button';
                            option.className = 'theme-option';
                            option.setAttribute('role', 'option');
                            option.setAttribute('data-value', name);
                            option.textContent = name;
                            option.addEventListener('click', () => select(name));
                            menu.appendChild(option);
                        });

                        select(current);

                        toggle.addEventListener('click', () => {
                            if (menu.hidden) {
                                menu.hidden = false;
                                toggle.setAttribute('aria-expanded', 'true');
                            } else {
                                close();
                            }
                        });

                        document.addEventListener('click', (event) => {
                            if (!picker.contains(event.target)) {
                                close();
                            }
                        });

                        document.addEventListener('keydown', (event) => {
                            if (event.key === 'Escape') {
                                close();
                            }
                        });
                    })
                    .catch(() => {
                        currentLabel.textContent = 'n/a';
                    });
            })();
        </script>
    </body>
</html>
