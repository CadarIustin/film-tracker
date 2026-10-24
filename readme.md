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

## How to run the tests

```bash
php bin/console --env=test doctrine:migrations:migrate --no-interaction
php bin/phpunit --testdox
```

The first command is only needed once. It creates a separate test database,
`var/data_test.db`, so the tests never touch the data in the app. `--testdox`
prints every test as a readable sentence.

There are 38 tests in three groups:

- `tests/Entity/TitleTest.php` (4 tests): the `Title` entity on its own, without
  the framework.
- `tests/Repository/TitleRepositoryTest.php` (12 tests): sorting, filters, search
  and the duplicate check, against the test database.
- `tests/Controller/` (22 tests): real HTTP requests, the same way the page sends
  them, for every endpoint and every status code. All invalid inputs run through
  one test with a data provider.

I decided to test every rule once instead of writing as many tests as possible: a
test should protect a rule, not raise a number. The tests were also checked by
breaking the code on purpose (the sort order, the case-insensitive duplicate check,
the 409 status and the year limit): each time the matching tests failed, and they
passed again once the code was restored.

The JavaScript has no automated tests; I tested the page by hand in the browser.
Testing it automatically would have needed a second set of tools (Node.js) for one
small page.

## What is finished

The assignment:

- `Title` entity with name, type (film or series), year and watched, plus its
  database migration
- API endpoints to list, add and delete titles
- A web page that shows the list, adds titles with a form and deletes titles
  (after a confirmation)

Extras:

- A checkbox on each row to switch "watched" on or off
- Input validation on the server, with the same limits in the form
- Filters (type and watched) and a search on the name. The filtering happens in
  the database query, not in the browser.
- A duplicate check, so the same title cannot be added twice
- 38 automated tests

Smaller additions:

- Opening `/` leads straight to the app.
- Comments at the important places in the code, explaining why something is done
  that way.
- The watched checkboxes have a label for screen readers, and a checkbox is
  disabled while its change is being saved, so a double click cannot send two
  requests.
- The search waits until typing pauses (300 ms), so typing "Dune" sends one
  request instead of four.

## What is not finished

- The extras I did not pick: genres, editing titles, a rating, and a React
  frontend.
- The duplicate check happens in the code. With many users at the same time, two
  identical requests could still both get through, so a real multi-user app would
  also need a unique constraint in the database. For a personal list that is not
  needed.
- Errors that Symfony creates itself (an unknown id, an invalid filter value, a
  body that is not JSON) come back as Symfony's standard HTML error page, not as
  JSON like my own validation errors. The page never causes them, but an API used
  by other programs should answer them in JSON too.
- Search and the duplicate check ignore upper and lower case only for letters
  without accents, because SQLite's `LOWER()` only converts A to Z. A title saved
  as "AMÉLIE" is not found by searching for "amélie".

## How I approached it

1. **Plan first.** I read the assignment, split the work into small steps that
   each fit in one commit, and chose the design patterns before writing code, so
   the code would stay consistent from the first file to the last.
2. **Backend before frontend.** First the data model (enum, entity, migration),
   then the three endpoints one at a time, each tested before the next. The page
   depends on the API, and an API can be tested without a page.
3. **Frontend in the same small steps:** the list, then the form, then the delete
   buttons, each tested in the browser.
4. **A checkpoint.** When the assignment itself was complete, I wrote the README
   before starting any extra, so there was a finished, documented version first.
5. **Extras, partly chosen by testing.** I started with the watched switch. While
   testing it, I could add a film with the year -1, so validation came next.
   While testing the filters and search, I could add the same Dune twice, so the
   duplicate check followed.
6. **Tests** for every rule, in three groups (see above).
7. **A final check.** I went through every file and removed what was not used:
   four empty placeholder files, `AGENTS.md` (an AI instruction file that came
   with the Symfony skeleton and that this project does not follow), two unused
   lines of JavaScript and an unused test package. Then I ran all checks and tests
   again.

## How it is built

- `src/Enum/TitleType.php`: the type as a backed enum, so it can only be `film`
  or `series`, never a typo.
- `src/Entity/Title.php`: the Doctrine entity. Required fields are constructor
  arguments, so a title can never exist half-filled, and the only setter is the
  one for "watched". `jsonSerialize()` defines the JSON shape in one place.
- `src/Repository/TitleRepository.php`: every database read and write (the
  repository pattern). Queries are built with Doctrine's QueryBuilder, and every
  value goes in as a parameter, which prevents SQL injection.
- `src/Controller/TitleController.php`: the API endpoints. Each one reads the
  request, checks it, calls the repository and returns JSON. Symfony injects the
  repository (dependency injection).
- `src/Controller/HomeController.php`: sends `/` to the page.
- `public/index.html` and `public/app.js`: the frontend. All `fetch` calls live in
  one `api` object, and the functions that draw the page never call the server.
  After every change the list is reloaded from the API, so the page always shows
  what the server has. Names are inserted as text, never as HTML, so a name like
  `<script>` cannot run.

What I chose not to use, and why:

- No extra packages for JSON or validation (Symfony's Serializer and Validator).
  With four fields, reading the JSON and checking it by hand is short, and every
  line is visible. Validation rules on the entity would also have run too late:
  creating a `Title` without a name already fails before a validator gets a
  chance.
- No service layer, DTOs or factory classes. The app only stores and reads titles,
  so those classes would only pass calls along.
- No Twig. The page is plain HTML in `public/`, as the assignment asks for HTML
  and JavaScript.

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
A body that is not valid JSON also gets a 400.

- `name`: required text, not only spaces, at most 255 characters. Spaces around
  it are removed.
- `type`: `film` or `series`.
- `year`: a whole number from 1900 up to five years after the current year,
  because a watchlist can also hold titles that are announced but not out yet.
- `watched`: optional, `true` or `false`.

A title with the same name (ignoring upper and lower case and spaces around it),
type and year as one that is already on the list is refused with 409 Conflict. A
remake has another year, so "Dune" (1984) and "Dune" (2021) can both be on the
list.

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
- Installing the test package ended with an error (exit code 255) in its last
  step, where Composer clears Symfony's cache. Most likely a cache file was locked
  by the running development server, which happens on Windows. The packages were
  installed correctly, and running `composer install` again finished without
  errors.

## AI tools

I used Claude Code (an AI assistant in VS Code). Before starting, I gave it rules from the start so that i work with it as professional as possible
in `CLAUDE.md`: explain every change first, work in small steps of one commit
each, and ask me before adding packages, changing the database or deleting files. I always work like this for every project that I so that I always in control.
After every step it gave me my progress in percent so that I know where I am at.

I used it to plan the steps and to have an opinion if my aproaches are goodI used ai for generating the boiler plate i also used ai for generating the database and filling it with dummy data for me to test my project, to check for the problems I found while testing : the year -1, the
duplicate Dune, the empty page at `/index.html/`, also because I did not had any mentioned linters I used ai as a linter and to see if my README.md was updated corectly for each of my steps.

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
| Frontend: delete buttons | 19 min |
| First version of this README | 15 min |
| Comments in the code | 8 min |
| Extra: watched switch | 15 min |
| Extra: validation | 16 min |
| Extra: filters and search | 14 min |
| Extra: duplicate check | 13 min |
| Tests: setup and unit tests | 33 min |
| Tests: repository and API tests | 28 min |
| Start page redirect and script path fix | 10 min |
| Final check, cleanup, final README | ~30 min |
| **Total** | **~5 h 31 min** |
