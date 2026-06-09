# Coding Standards

## General Principles

- Prefer simplicity over abstraction.
- Follow existing patterns before introducing new ones.
- Keep files focused on a single responsibility.
- Avoid premature optimization.
- Write self-documenting code whenever possible.

## Naming Conventions

### Backend

- Classes: PascalCase
- Methods: camelCase
- Variables: camelCase
- Database tables: snake_case plural
- Database columns: snake_case
- Array indexes: snake_case

Examples:

- players
- financial_transactions
- attendance_confirmations
- $user['player_id']

### Frontend

- Components: PascalCase
- Composables: useXxx
- Services: XxxService
- Types: XxxType

Examples:

- PlayerCard.vue
- useAttendance.ts
- MatchService.ts

## Backend Standards

- Business rules belong in Services.
- Controllers should remain thin annd use invokables.
- Validation must use Form Requests.
- Authorization must use Policies.
- Avoid direct database queries in Controllers.
- Prefer Eloquent relationships over manual joins when reasonable.
- Avoid whereHas, use join instead.

## Frontend Standards

- Pages should focus on composition and orchestration.
- Business logic belongs in Composables.
- API communication belongs in Services.
- Reusable UI belongs in Components.
- Avoid duplicated logic across pages.

## Container Standards

- All services must run through Docker Compose.
- Local development must not require PHP, Node.js or MySQL installed on the host machine.
- Environment variables must be provided through .env files.

## API Standards

- RESTful endpoints.
- Use /api/v1 prefix.
- Return JSON only.
- Use pagination for collection endpoints.
- Use consistent error responses.

Example:

{
"message": "Validation failed",
"errors": {}
}

## Testing Standards

### Backend

- Use Pest.
- Test Services and API endpoints.
- Cover business rules with automated tests.

### Frontend

- Use Vitest.
- Test composables and critical components.

## Git Standards

Branch naming:

- feature/<issue-number>-description
- fix/<issue-number>-description
