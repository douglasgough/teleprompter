# Prompter

A simple, self-hosted teleprompter for recording videos. Write your script in Notion (or any Markdown editor), export it as a `.md` file, drop it into Prompter, and read it to camera.

It runs in the browser on your own machine. There are no accounts and no cloud service.

## Features

- **Markdown import.** Drag `.md` files onto the page or use the Import button. The first `# Heading` becomes the script's title and isn't shown on the prompter. If you re-import a file with the same title, it replaces the old version, so you can keep editing in Notion.
- **Smooth scrolling.** Adjustable speed that stays smooth even when very slow.
- **Reading guide.** A highlighted band about a third of the way down the screen, near where a camera usually sits, so your eyes stay close to the lens.
- **Adjustable text.** Font size and column width, so you can keep the text narrow and close to the camera.
- **Mirror mode** for beam-splitter glass teleprompter rigs.
- **3-2-1 countdown** before starting from the top.
- **Progress bar and time remaining.**
- **Inline editing.** Pause, press <kbd>E</kbd>, click a paragraph and fix a typo in place.
- **Remembers your settings.** Speed, size, width, mirror, guide and countdown are saved in your browser.
- **Keeps the screen awake** while scrolling, in browsers that support it.
- **Works with a presentation clicker.** Most clickers send Page Up / Page Down, which move the script back and forward.

## Keyboard shortcuts

| Key | Action |
| --- | --- |
| <kbd>Space</kbd> | Play / pause (clicking the text does the same) |
| <kbd>↑</kbd> <kbd>↓</kbd> | Faster / slower (hold <kbd>Shift</kbd> for fine steps) |
| <kbd>←</kbd> <kbd>→</kbd> | Back / forward one line |
| <kbd>Page Up</kbd> <kbd>Page Down</kbd> | Jump back / forward a third of a screen |
| <kbd>+</kbd> <kbd>−</kbd> | Larger / smaller text |
| <kbd>[</kbd> <kbd>]</kbd> | Narrower / wider column |
| <kbd>M</kbd> | Mirror text |
| <kbd>G</kbd> | Show / hide reading guide |
| <kbd>C</kbd> | Countdown on / off |
| <kbd>E</kbd> | Edit mode (<kbd>Esc</kbd> to leave) |
| <kbd>F</kbd> | Fullscreen |
| <kbd>R</kbd> or <kbd>Home</kbd> | Back to the start |

The mouse wheel or trackpad also scrolls the script, whether it's playing or paused.

## Requirements

- PHP 8.3 or newer, with Composer
- Node.js and npm (to build the front-end assets)
- SQLite (the default) or MySQL

## Installation

```sh
git clone https://github.com/douglasgough/teleprompter.git prompter
cd prompter
touch database/database.sqlite
composer run setup
```

`composer run setup` installs the PHP and JavaScript dependencies, creates `.env`, generates an app key, runs the database migrations and builds the assets.

Then start the app:

```sh
composer run dev
```

and open the URL it prints, usually <http://localhost:8000>.

### Using Laravel Herd, Valet or Lerd

If you use a local PHP environment that serves sites from a folder, link the project as a site and open it at its local domain (for example `https://prompter.test`). Set `APP_URL` in `.env` to match. After changing front-end code, run `npm run build`, or keep `npm run dev` running.

### Using MySQL instead of SQLite

Update the `DB_*` values in `.env`, create the database, and run:

```sh
php artisan migrate
```

## Workflow

1. Write the script in Notion. Use `# Title` for the title and `## Headings` for section markers; headings show on the prompter as small amber labels, not as lines to read.
2. In Notion, choose **••• → Export → Markdown & CSV**. Notion downloads a `.zip`, so unzip it to get the `.md` file.
3. Drag the `.md` file onto Prompter's scripts page.
4. Click **Prompt**, press <kbd>F</kbd> for fullscreen, adjust speed and size, and press <kbd>Space</kbd>.

Formatting carries through: **bold** text shows in amber and *italics* in blue, which is handy for marking emphasis. Images are hidden.

### About editing

Inline editing changes the copy stored in Prompter, not the file in Notion. **Re-importing the script from Notion replaces any edits made in Prompter**, so make the same fixes in Notion if you plan to re-export.

The editor works one paragraph at a time: paragraphs are split on blank lines. Clearing a paragraph deletes it, and adding a blank line splits it in two.

## Built with

- [Laravel](https://laravel.com) 13
- [Livewire](https://livewire.laravel.com) 4 and [Alpine.js](https://alpinejs.dev)
- [Tailwind CSS](https://tailwindcss.com) 4

Laravel is more than a teleprompter needs, but it makes storing, importing and editing scripts easy.

## Running the tests

```sh
php artisan test
```

## License

Prompter is open-source software licensed under the [MIT license](LICENSE).
