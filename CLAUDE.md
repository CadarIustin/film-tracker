# Film and Series Tracker

A code challenge: a personal watchlist for films and series, with a 6-hour hard limit.
I'm being assessed on how I learn and think, so I must understand and be able to
explain every line in this project.

## Stack
- PHP 8.4+, Symfony (see composer.json for the exact version), Doctrine ORM
- Database: SQLite
- Frontend: plain HTML and JavaScript (fetch) that talks to a JSON API
- No extra packages or bundles unless I agree to them first

## How to work with me
- Explain before you write code. Keep each change small enough that I can read and
  understand it in one go.
- Don't build whole features at once. Work in steps that each fit in one commit.
- If you're not sure something works in this Symfony version, say so and point me
  to the Symfony docs.
- Tell me when I'm making a mistake or overcomplicating something.
- Don't commit or push unless I ask you. I do that myself in GitHub Desktop.

## Code style
- Use PHP attributes (#[Route], #[ORM\Entity]), not annotations
- Follow Symfony's coding standards and folder structure
- Prefer clear names over comments. Only comment to explain why, not what.

## Never
- Never read, print or change .env.local or anything that holds passwords
- Never delete files or change the database without asking me first

## Design patterns (follow them in all code)
- MVC: the `Title` entity is the model, `TitleController` the controller, and the JSON
  responses plus the HTML/JS in `public/` are the view.
- Repository: every database read and write goes through `TitleRepository`.
  Controllers never use the EntityManager directly.
- Dependency injection: services arrive through autowired constructor or action
  arguments, never through `new`.
- Thin controllers: read the request, call the repository, return JSON. No service
  layer, DTOs or factory classes unless real logic appears that needs them.
- Fixed values are backed enums (`App\Enum\TitleType`), never loose strings.
- Always-valid entity: required fields are constructor arguments and there is no
  setter for the id.
- One JSON shape: `Title` implements `\JsonSerializable`, and every endpoint returns
  titles through it.
- REST routes: `/api/titles` and `/api/titles/{id}`, with status codes 200 (read),
  201 (created), 204 (deleted), 400 (bad input) and 404 (unknown id).
- Frontend: all `fetch` calls live in one `api` object in `app.js`; render functions
  only touch the DOM. After every change, reload the list from the API so the
  server stays the single source of truth.
