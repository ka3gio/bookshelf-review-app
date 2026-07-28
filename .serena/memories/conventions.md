# Conventions
- Follow Laravel 10 conventions and PSR-4 namespaces (`App\\`, `Database\\Factories\\`, `Tests\\`).
- PHP uses 4 spaces and LF; YAML uses 2 spaces (`.editorconfig`).
- Feature tests extend `Tests\\TestCase`, use `RefreshDatabase`, snake_case `test_*` method names, model factories, fluent response assertions, and route names rather than hard-coded URLs.
- Domain feature tests live in `tests/Feature/<Domain>`; shared domain helpers may use an abstract `<Domain>TestCase`.
- Models use `HasFactory`, `$fillable`, and Eloquent relationship methods.
- Blade views use Tailwind utility classes and named routes.
- Preserve unrelated working-tree changes; run Pint on edited PHP files.