# Project core
- Laravel monolith for a bookshelf/review application.
- HTTP entrypoints: `routes/web.php` (Blade web UI), `routes/api.php` (API).
- Domain code: `app/Models`, `app/Http/Controllers`, `app/Http/Requests`, `app/Policies`, `app/Http/Resources`.
- UI: server-rendered Blade under `resources/views`; Vite/Tailwind/Alpine assets under `resources`.
- Persistence: migrations/factories/seeders under `database`.
- Tests: PHPUnit feature tests grouped by domain under `tests/Feature`; relationship unit tests under `tests/Unit/Models`.
- Authenticated web routes are grouped with `auth` middleware in `routes/web.php`; public book index/show and ranking routes sit outside that group.
- Read `mem:tech_stack` for pinned tooling, `mem:conventions` before editing, and `mem:task_completion` before handing off changes.