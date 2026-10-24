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

Then open **http://localhost:8000/index.html**. Keep the terminal with the server
open while you use the app; closing it stops the server.

## API

| Method | Path | What it does | Response |
|---|---|---|---|
| GET | `/api/titles` | List all titles, newest first | 200 with a JSON array |
| POST | `/api/titles` | Add a title | 201 with the new title, 400 with error messages if the input is invalid |
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

## How it is built

- `src/Enum/TitleType.php`: the two allowed types, `film` and `series`, as a
  backed enum.
- `src/Entity/Title.php`: the Doctrine entity. Required fields are constructor
  arguments, so a title can never exist half-filled. `jsonSerialize()` defines
  the JSON shape in one place.
- `src/Repository/TitleRepository.php`: every database read and write.
- `src/Controller/TitleController.php`: the API endpoints. It only reads
  the request, calls the repository and returns JSON.
- `public/index.html` and `public/app.js`: the frontend. All `fetch` calls live
  in one `api` object, and after every change the list is reloaded from the API.

## What is finished

- Title entity (name, type, year, watched) and its database migration
- API endpoints to list, add and delete titles
- A web page that shows the list, adds titles with a form and deletes titles
  (after a confirmation)
- Extra: a checkbox on each row to switch "watched" on or off
- Extra: input validation on the server, with the same limits in the form

## What is not finished (yet)

- Opening `http://localhost:8000/` shows a Symfony 404 page; the app is at
  `/index.html`.
- More extras from the assignment. Planned next: filters and a test.

## Problems I ran into

- Symfony 8.1 needs PHP 8.4, while the assignment mentions PHP 8.2, so this
  README asks for 8.4.
- The page first showed `ERR_CONNECTION_REFUSED` because the PHP server was not
  running. It has to keep running in its own terminal.
- PHP's built-in server sends `/` to Symfony's `index.php` before it looks for
  `index.html`, so the page is opened at `/index.html`.
- While testing, I could add a film with the year -1, because neither the form
  nor the API checked the year. That is why I added validation before the other
  extras.

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
