# Film and Series Tracker

A personal watchlist for films and series. The backend is a JSON API built with
Symfony 8.1 and Doctrine ORM on SQLite. The frontend is a plain HTML page with
JavaScript (`fetch`) that talks to that API.

## Requirements

- PHP 8.4 or higher, with the `pdo_sqlite` extension enabled. Symfony 8.1 needs
  PHP 8.4, even though the assignment mentions 8.2.
- Composer
- No database server: the data is stored in a SQLite file, `var/data_dev.db`,
  which the migration creates.

## How to run it

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php -S localhost:8000 -t public/
```

Then open **http://localhost:8000**. Keep the terminal with the server open while
you use the app; closing it stops the server.

The packages themselves (`vendor/`) are not in git; `composer install` downloads
them. I did commit `composer.json` and `composer.lock` on purpose: the lock file
holds the exact versions I tested with, so everyone who runs the project gets the
same ones.

## API

| Method | Path | What it does | Response |
|---|---|---|---|
| GET | `/api/titles` | List titles, newest first. Optional filters: `?type=film` or `series`, `?watched=true` or `false`, `?search=dun` (part of the name, not case-sensitive) | 200 with a JSON array, 400 if a filter value is invalid |
| POST | `/api/titles` | Add a title | 201 with the new title, 400 with error messages if the input is invalid, 409 if the same title is already on the list |
| PATCH | `/api/titles/{id}` | Switch "watched" on or off, body `{"watched": true}` | 200 with the updated title, 400 if `watched` is not `true` or `false`, 404 if the id does not exist |
| DELETE | `/api/titles/{id}` | Delete a title | 204, or 404 if the id does not exist |

Example body for POST (`watched` is optional and defaults to `false`):

```json
{"name": "Dune", "type": "film", "year": 2021, "watched": false}
```

Input rules for POST. If one is broken, the API answers 400 with one message per
field, for example `{"errors": {"year": "The year must be a whole number from 1900 to 2031."}}`.

- `name`: required text, not only spaces, at most 255 characters. Spaces around
  it are removed.
- `type`: `film` or `series`.
- `year`: a whole number from 1900 up to five years after the current year,
  because a watchlist can also hold titles that are announced but not out yet.
- `watched`: optional, `true` or `false`.

A title with the same name (ignoring upper and lower case), type and year as one
that is already on the list is refused with 409 Conflict. A remake has another
year, so "Dune" (1984) and "Dune" (2021) can both be on the list.

## How it is built

- `src/Enum/TitleType.php`: the two allowed types, `film` and `series`, as a
  backed enum.
- `src/Entity/Title.php`: the Doctrine entity. Required fields are constructor
  arguments, so a title can never exist half-filled. `jsonSerialize()` defines
  the JSON shape in one place.
- `src/Repository/TitleRepository.php`: every database read and write.
- `src/Controller/TitleController.php`: the API endpoints. It only reads
  the request, calls the repository and returns JSON.
- `src/Controller/HomeController.php`: sends `/` to the page.
- `public/index.html` and `public/app.js`: the frontend. All `fetch` calls live
  in one `api` object, and after every change the list is reloaded from the API.

## Tests

```bash
php bin/console --env=test doctrine:migrations:migrate --no-interaction
php bin/phpunit
```

The first command is only needed once. It creates a separate test database,
`var/data_test.db`, so the tests never touch the data in the app.

I decided to test every rule once instead of writing as many tests as possible: a
test should protect a rule, not raise a number. Most of the logic lives in the
controller and the repository, so most tests run against the real test database
and send requests the same way the page does. Only the `Title` entity is tested on
its own, because it does not need the framework.

The JavaScript has no automated tests; I tested the page by hand in the browser.
Testing it automatically would have needed a second set of tools (Node.js) for one
small page.

## What is finished

- Title entity (name, type, year, watched) and its database migration
- API endpoints to list, add and delete titles
- A web page that shows the list, adds titles with a form and deletes titles
  (after a confirmation)
- Extra: a checkbox on each row to switch "watched" on or off
- Extra: input validation on the server, with the same limits in the form
- Extra: filters (type and watched) and a search on the name. The filtering
  happens in the database query, not in the browser.
- Extra: a duplicate check, so the same title cannot be added twice.

## What is not finished (yet)

- The duplicate check happens in the code. With many users at the same time, two
  identical requests could still both get through, so a real multi-user app would
  also need a unique constraint in the database. For a personal list that is not
  needed.
- More extras from the assignment. Planned next: a test.

## Problems I ran into

- Symfony 8.1 needs PHP 8.4, while the assignment mentions PHP 8.2, so this
  README asks for 8.4.
- The page first showed `ERR_CONNECTION_REFUSED` because the PHP server was not
  running. It has to keep running in its own terminal.
- PHP's built-in server sends `/` to Symfony's `index.php` before it looks for
  `index.html`, so opening the start URL showed Symfony's welcome page instead of
  the app. I added a small redirect from `/` to `/index.html`.
- While testing, I could add a film with the year -1, because neither the form
  nor the API checked the year. That is why I added validation before the other
  extras.
- While testing the filters, I noticed I could add the same Dune twice. In a
  personal watchlist that makes no sense: one entry stands for one thing to
  watch, and with two copies it is unclear whether I have watched it, because one
  could be ticked and the other not. So I added a duplicate check: the same name,
  type and year is refused, but a remake with another year is still allowed.
- When I opened `/index.html/` (with a slash at the end), the page stayed empty.
  The browser looked for the script at `/index.html/app.js`, and the server sent
  the HTML page back instead of the JavaScript, so none of it ran. Loading the
  script from `/app.js`, with a leading slash, fixed it.

## Time log

Based on my commit times. The setup before my first commit is an estimate.

| Part | Time |
|---|---|
| Project setup: Symfony skeleton | ~30 min |
| AI rules (CLAUDE.md), installing Doctrine and MakerBundle | 23 min |
| Planning: reading the assignment, step plan, design patterns | ~25 min |
| Data model: enum, entity, repository, migration | ~18 min |
| API endpoints: list, add, delete (each one tested) | 11 min |
| Frontend: list | 15 min |
| Frontend: add form | 8 min |
| Frontend: delete buttons | 10 min |
| **Total so far** | **~2 h 20 min** |
