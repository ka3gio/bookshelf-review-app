# Suggested commands
Run PHP commands through Laravel Sail because the host may not expose `php` on PATH.
- Start services: `./vendor/bin/sail up -d`
- Run all tests: `./vendor/bin/sail artisan test`
- Run one test file: `./vendor/bin/sail artisan test tests/Feature/<Domain>/<Test>.php`
- Run Pint check: `./vendor/bin/sail pint --test`
- Apply Pint: `./vendor/bin/sail pint`
- Frontend dev server: `npm run dev`
- Production asset build: `npm run build`
- Stop services: `./vendor/bin/sail down`