# Tech stack
- PHP `^8.1` in Composer; Laravel framework locked at `10.50.2`; Laravel Fortify and Sanctum.
- PHPUnit `^10.1`; Laravel Pint `^1.0`; Faker factories; Mockery.
- Frontend: Vite 5, Tailwind CSS 3, Alpine.js 3, Axios.
- Composer and npm manage dependencies (`composer.lock`, `package-lock.json`).
- Local runtime is Laravel Sail via `compose.yaml`; app service is `laravel.test` using Sail PHP 8.5 image.
- Test environment in `phpunit.xml`: in-memory SQLite, array cache/session/mail, sync queue.