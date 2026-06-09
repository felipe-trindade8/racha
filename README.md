# Racha

Soccer management system for a group of friends — Laravel API + Nuxt 4 frontend,
backed by MySQL. See [docs/product.md](docs/product.md) and
[docs/architecture.md](docs/architecture.md) for details.

## Local development

The whole stack runs in Docker Compose — **no local PHP, Node, or MySQL required.**

### Services

| Service    | Stack            | URL / Port              |
| ---------- | ---------------- | ----------------------- |
| `frontend` | Nuxt 4 (Node 22) | http://localhost:3000   |
| `backend`  | Laravel (PHP 8.4)| http://localhost:8000   |
| `db`       | MySQL 8.4        | localhost:3306          |

### First-time setup

```bash
# Create the backend env file (the DB defaults already point at the db container).
cp backend/.env.example backend/.env

# Build images and start the stack.
docker compose up --build

# In another terminal, generate the app key and run migrations.
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate
```

Verify the backend is healthy:

```bash
curl http://localhost:8000/api/v1/health
# {"status":"ok"}
```

### Everyday commands

```bash
docker compose up               # start the stack (add -d to detach)
docker compose exec backend php artisan migrate   # run migrations
docker compose down             # stop and remove containers (keeps the db volume)
docker compose down -v          # stop and also wipe the database volume
```
