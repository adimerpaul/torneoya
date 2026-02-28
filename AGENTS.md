# Repository Guidelines

## Project Structure & Module Organization
This repository is split into two apps:
- `front/`: Quasar + Vue 3 PWA client. Main code lives in `front/src` (`pages/`, `layouts/`, `components/`, `router/`, `stores/`, `boot/`), static files in `front/public`, and build output in `front/dist`.
- `back/`: Laravel 12 API/server. Core code in `back/app`, HTTP routes in `back/routes`, templates in `back/resources/views`, DB files in `back/database` (`migrations/`, `seeders/`, `factories/`), and tests in `back/tests`.

## Build, Test, and Development Commands
Run commands from each module directory.
- Frontend install: `cd front && npm install`
- Frontend dev server: `npm run dev` (Quasar dev mode)
- Frontend production build: `npm run build`
- Backend setup (first run): `cd back && composer run setup`
- Backend local stack (server + queue + Vite): `composer run dev`
- Backend tests: `composer run test` or `php artisan test`
- Backend asset build: `cd back && npm run build`

## Coding Style & Naming Conventions
- Follow `.editorconfig` strictly: frontend uses 2-space indentation; backend uses 4 spaces; UTF-8, LF, final newline.
- Vue SFCs and JS modules use descriptive PascalCase for components (`Usuarios.vue`) and camelCase for utilities (`Imprimir.js`).
- PHP classes follow PSR-4 and Laravel defaults (`App\\Http\\Controllers\\UserController`).
- Use Laravel Pint for backend formatting: `cd back && ./vendor/bin/pint`.
- Do not manually edit generated folders: `front/dist`, `front/.quasar`, `back/vendor`, `back/storage/framework/*`.

## Testing Guidelines
- Backend tests use Pest/Laravel test runner under `back/tests/Feature` and `back/tests/Unit`.
- Name tests by behavior (for example, `UserCanRegisterTest.php` or clear Pest `it('...')` descriptions).
- Keep API, auth, and permission changes covered by Feature tests before opening a PR.
- Frontend currently has no automated test suite (`npm test` is a placeholder); validate key flows manually when changing UI logic.

## Commit & Pull Request Guidelines
- Recent commits are short, feature-focused, and often Spanish (for example, `Creado version`, `Funcionando user`).
- Keep commit messages concise and imperative; prefer `<area>: <change>` when possible (example: `back-auth: fix login validation`).
- PRs should include: purpose summary, affected module(s) (`front`/`back`), migration or env changes, linked issue/ticket, and screenshots/video for UI updates.
- Separate frontend and backend concerns into distinct commits when feasible.
