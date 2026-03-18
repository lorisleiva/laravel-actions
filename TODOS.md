# TODOs

## Modernize property type declarations

- **What:** Convert PHPDoc-only property types to PHP typed properties in `ValidateActions.php` and `ListenerDecorator.php`.
- **Why:** Package minimum is PHP 8.2. Typed properties improve type safety and IDE support.
- **Files:** `src/Concerns/ValidateActions.php`, `src/Decorators/ListenerDecorator.php`
- **Depends on:** Nothing.
