# Architecture

## Architecture Style

- Universal (SSR) Nuxt frontend consuming a REST API
  - Nuxt runs in its default universal mode: server-side rendering on first
    request, then client-side hydration and navigation.
  - The frontend calls the backend over two base URLs: the browser uses the
    public host (`localhost:8000`), while SSR uses the internal service
    hostname (`http://backend:8000`).
- Frontend and backend deployed independently
- Backend is stateless

## Containerization

### Development

The entire application must run using Docker Compose.

Services:

- frontend (Nuxt)
- backend (Laravel)
- database (MySQL)

Goals:

- Consistent development environment
- Easy onboarding
- No local dependency installation except Docker

## API Standards

- REST API
- Return standard JSON responses
- Versioned endpoints (/api/v1)
- Use snake_case in database columns
- Use camelCase in frontend and backend code
- Use pagination for list endpoints

## Frontend

### Technologies

- Nuxt 4
- Nuxt Ui
- TypeScript

### Architecture

- Pages
- Components
- Composables
- Services
- Types

## Backend

### Technologies

- Laravel API
- Laravel 13
- PHP 8.4

### Architecture

- Controllers
- Services
- Models
- Form Requests
- Role-Based Access Control
- Policies

## Local Development

- Orchestrated with Docker Compose (development only).
- Three containers:
  - `backend` — Laravel (PHP 8.4), served on `:8000`
  - `frontend` — Nuxt 4 (Node 22), served on `:3000`
  - `db` — MySQL 8, on `:3306` with a persistent volume
- Services communicate over the Compose network by service name (backend reaches the database at host `mysql`).
- Source is bind-mounted for hot reload (`artisan serve` / Nuxt HMR); `vendor` and `node_modules` use container-managed volumes to avoid host masking.
- The browser uses host ports (`localhost:8000` / `localhost:3000`); Nuxt server-side rendering uses the internal service hostname (`http://backend:8000`).

## Infrastructure

> Docker Compose covers local development only. Production keeps the topology
> below; the backend dev image and the App Runner production image should share
> a multi-stage base so the two environments do not drift.

### Frontend

- Docker Container
- S3
- CloudFront

### Backend

- Docker Container
- AWS App Runner

### Database

- MySQL 8

### Deployment

- GitHub Actions

### Goals

- Minimize operational complexity
- Keep infrastructure cost below USD 10/month
- Prefer managed services over self-managed servers

## Authentication

- Laravel Sanctum

## Authorization

### Roles

- Administrator
- Player

### Only administrators can

- Manage finances
- Manage matches
- Manage players

### Players can

- View matches
- Confirm attendance
- View and edit their own information

## Documentation

- Swagger

## Project Management

- Github Issues
- Github Projects

## Testing

### Backend

- Pest

### Frontend

- Vitest

### End-to-end

- Playwright
