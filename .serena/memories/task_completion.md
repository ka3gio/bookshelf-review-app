# Task completion
For PHP changes:
1. Run focused PHPUnit tests through Sail: `./vendor/bin/sail artisan test <affected test paths>`.
2. Run the full suite: `./vendor/bin/sail artisan test` when practical.
3. Check formatting: `./vendor/bin/sail pint --test` (or narrowly format edited files first).
For frontend asset changes, additionally run `npm run build`.
Before handoff, inspect `git diff --check` and `git status --short`; report any pre-existing/unrelated changes and any checks that could not run.