# PHPDoc Shaped Array Rules

When arrays are used in parameters or return values, PHPDoc must describe them with explicit shaped-array syntax.

## Requirement

- Do **not** use vague `array` annotations when the structure is known.
- Use `array{...}` with named keys and precise value types.
- Include nested shapes for sub-arrays (`array{key: array{subKey: type}}`).
- Apply this rule to both `@param` and `@return` annotations.

## Examples

Prefer:

```php
/**
 * @param array{
 *   id: non-empty-string,
 *   statuses: list<non-empty-string>,
 *   registrant: array{
 *     id: non-empty-string,
 *     email: non-empty-string
 *   }
 * } $payload
 * @return array{
 *   success: bool,
 *   code: int,
 *   data: array{
 *     domain: non-empty-string,
 *     expiresAt: non-empty-string
 *   }
 * }
 */
```

Avoid:

```php
/**
 * @param array $payload
 * @return array
 */
```

## Notes

- Keep shapes aligned with real DTO/response structures.
- If a field is optional, mark it explicitly (for example: `array{foo?: string}`).
- Prefer dedicated DTOs over large array payloads, but when arrays are required, shape them fully.
