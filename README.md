# Vintage Motorcycle Fuel Tracker (Digital Logbook)

A “light architecture” full-stack web application for tracking vintage motorcycle fuel economy as a diagnostic signal (MPG as engine health telemetry). The stack is intentionally conservative and durable: **PostgreSQL + Apache/PHP (thin REST API) behind Nginx (reverse proxy)**, fully **Dockerized** for repeatable dev and deployment.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Tech Stack](#tech-stack)
- [Repository Structure](#repository-structure)
- [Prerequisites](#prerequisites)
- [Quick Start (Local Development)](#quick-start-local-development)
- [Configuration](#configuration)
- [API Endpoints](#api-endpoints)
- [Database Schema & Odometer Rollover Logic](#database-schema--odometer-rollover-logic)
- [HTTPS in Development](#https-in-development)
- [Deployment (VPS)](#deployment-vps)
- [CI (GitHub Actions)](#ci-github-actions)
- [Git Workflow](#git-workflow)
- [Operations](#operations)
- [Troubleshooting](#troubleshooting)
- [Roadmap](#roadmap)
- [License](#license)

---

## Architecture Overview

**Network topology (Docker bridge network: `vintage_net`)**

- **web**: `nginx:alpine`  
  - Exposes: **80** and **443** to the host  
  - Serves static assets directly from `src/public` (read-only mount)  
  - Proxies dynamic requests to **backend**
- **backend**: `php:8.2-apache` (custom build)  
  - Not exposed publicly  
  - Hosts a minimal **no-framework** REST API (Front Controller)
  - Connects to **db**
- **db**: `postgres:15-alpine`  
  - Not exposed publicly  
  - Persistent data volume: `db_data`  
  - Initializes schema on first run from `docker/postgres/init/`

**Key idea:** Nginx acts as the “shield” (static delivery, TLS termination, traffic handling) while Apache/PHP is the “engine” (API logic). PostgreSQL is the source of truth and handles the tricky odometer edge cases via SQL.

---

## Tech Stack

- **Docker / Docker Compose**
- **PostgreSQL 15 (Alpine)**
- **Nginx (Alpine)** reverse proxy
- **Apache + PHP 8.2**
  - Extensions: `pdo`, `pdo_pgsql`, `opcache`
  - Modules: `rewrite`, `headers`

---

## Repository Structure

```

motorcycle-tracker/
├── docker-compose.yml
├── docker/
│   ├── apache/
│   │   └── Dockerfile
│   ├── nginx/
│   │   ├── conf.d/
│   │   │   └── default.conf
│   │   └── ssl/
│   │       ├── dev.crt (dev only)
│   │       ├── dev.key (dev only)
│   │       └── .gitkeep
│   └── postgres/
│       └── init/
│           └── 001_schema.sql
├── src/
│   ├── public/
│   │   ├── index.php         # Front Controller (API entry)
│   │   └── assets/           # Static assets
│   ├── config/
│   │   └── database.php      # PDO connection
│   ├── controllers/
│   │   ├── VehicleController.php
│   │   └── LogController.php
│   └── models/
│       ├── VehicleModel.php
│       └── LogModel.php
├── .github/
│   └── workflows/
│       └── ci.yml
├── .env                      # NOT committed
└── .gitignore

````

---

## Prerequisites

Local machine:

- Docker Desktop (or Docker Engine)
- Docker Compose v2 (`docker compose`)
- Git
- Optional: `curl`, `jq`

Verify:

```bash
docker --version
docker compose version
git --version
````

---

## Quick Start (Local Development)

### 1) Create `.env`

Create a `.env` file in the project root:

```bash
cat > .env <<'EOF'
DB_NAME=moto
DB_USER=moto_user
DB_PASS=change_me_strong
EOF
```

### 2) (Dev) Generate self-signed TLS certs

For local HTTPS on port 443:

```bash
openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout docker/nginx/ssl/dev.key \
  -out docker/nginx/ssl/dev.crt \
  -subj "/CN=localhost"
```

> Browsers will warn because it’s self-signed. That’s expected for development.

### 3) Build and start containers

```bash
docker compose up -d --build
```

### 4) Check status / logs

```bash
docker compose ps
docker logs moto_nginx --tail=50
docker logs moto_backend --tail=50
docker logs moto_db --tail=50
```

---

## Configuration

### Environment variables (`.env`)

Used by both database and backend:

* `DB_NAME` — PostgreSQL database name
* `DB_USER` — PostgreSQL user
* `DB_PASS` — PostgreSQL password

**Important:** never commit `.env`. Keep it only on your machine / server.

---

## API Endpoints

Base URL (local): `http://localhost`

### Vehicles

* `GET /api/vehicles`
  Lists active vehicles.

* `POST /api/vehicles`
  Creates a vehicle.

Example:

```bash
curl -s -X POST http://localhost/api/vehicles \
  -H "Content-Type: application/json" \
  -d '{
    "name":"The Cafe Racer",
    "make":"Honda",
    "model":"CB750",
    "year":1974,
    "fuel_capacity":3.50,
    "initial_odometer":99950
  }'
```

### Fuel logs

* `POST /api/logs`
  Adds a fuel log entry.

Example:

```bash
curl -s -X POST http://localhost/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_id":1,
    "odometer":50,
    "fuel_volume":2.800,
    "is_full":true,
    "missed_previous":false,
    "fuel_grade":"E0",
    "station_location":"Shell on Main St",
    "notes":"filled upright on center stand"
  }'
```

---

## Database Schema & Odometer Rollover Logic

PostgreSQL initializes on first run from:

* `docker/postgres/init/001_schema.sql`

### Tables (3NF)

* `vehicles` — entity/invariant data about each motorcycle
* `fuel_logs` — time-series transactional fuel entries

### View: `view_fuel_efficiency`

This view computes a virtual `distance_traveled` column using window functions:

* Uses `LAG(odometer)` to read the previous odometer reading per vehicle
* Handles:

  * First record (no previous) → distance `0`
  * Missed previous fill → distance `NULL`
  * Rollover (current < previous) → `(current + 100000) - previous`
  * Normal progression → `current - previous`

Verify it:

```bash
docker exec -it moto_db psql -U moto_user -d moto
```

Then:

```sql
SELECT id, vehicle_id, odometer, prev_odometer, distance_traveled
FROM view_fuel_efficiency
WHERE vehicle_id = 1
ORDER BY log_date ASC;
```

---

## HTTPS in Development

Nginx listens on:

* `80` (HTTP)
* `443` (HTTPS)

For local use, the repo expects dev certs at:

* `docker/nginx/ssl/dev.crt`
* `docker/nginx/ssl/dev.key`

For production, replace self-signed certs with real certificates (e.g., Let’s Encrypt).

---

## Deployment (VPS)

This project is designed for “light” deployment on a typical VPS.

### 1) On the server: install dependencies

* Install Docker Engine + Docker Compose plugin
* Install Git

### 2) Clone the repository

```bash
git clone https://github.com/<your-username>/vintage-moto-tracker.git
cd vintage-moto-tracker
```

### 3) Create `.env` on the VPS (never commit secrets)

```bash
cat > .env <<'EOF'
DB_NAME=moto
DB_USER=moto_user
DB_PASS=SuperSecretPassword123
EOF
```

### 4) TLS for production

Replace dev certs with production certs. Common options:

* Terminate TLS at Nginx with Let’s Encrypt certs (recommended)
* Or terminate TLS upstream (cloud load balancer / reverse proxy)

Ensure the referenced paths in `docker/nginx/conf.d/default.conf` match your cert filenames.

### 5) Start services

```bash
docker compose up -d --build
```

### 6) Updating the deployment

```bash
git pull
docker compose up -d --build
```

---

## CI (GitHub Actions)

Workflow: `.github/workflows/ci.yml`

Runs on pushes/PRs to `main`:

* Validates Compose syntax: `docker compose config`
* Builds containers: `docker compose build`

This prevents broken Dockerfiles / Compose syntax from reaching deployment.

---

## Git Workflow

This repo follows **GitHub Flow**:

* `main` is always deployable
* Create feature branches from `main`
* Open PRs and merge when ready

Example:

```bash
git checkout -b feature/add-efficiency-endpoint
# work...
git add .
git commit -m "feat(api): add fuel efficiency read endpoint"
git push origin feature/add-efficiency-endpoint
```

---

## Operations

### Stop / start

```bash
docker compose down
docker compose up -d
```

### Rebuild after changes

```bash
docker compose up -d --build
```

### Database persistence

Postgres data is stored in Docker volume `db_data`. Removing containers does not remove the volume unless you explicitly delete it.

### Backups (simple)

Example logical backup:

```bash
docker exec -t moto_db pg_dump -U moto_user -d moto > backup.sql
```

Restore (careful: overwrites data depending on approach):

```bash
cat backup.sql | docker exec -i moto_db psql -U moto_user -d moto
```

---

## Troubleshooting

### Nginx returns 502 Bad Gateway

* Backend not running or not reachable.
* Check:

```bash
docker compose ps
docker logs moto_backend --tail=100
docker logs moto_nginx --tail=100
```

### DB connection errors in API

* Ensure `.env` exists and is correct.
* Confirm db healthcheck:

```bash
docker inspect --format='{{json .State.Health}}' moto_db
```

### Schema didn’t initialize

Initialization scripts run **only on first database creation**. If you already had `db_data`, the init scripts won’t re-run.

To reinitialize from scratch (DESTROYS DATA):

```bash
docker compose down
docker volume rm motorcycle-tracker_db_data
docker compose up -d --build
```

---

## Roadmap

Planned expansions:

* MPG aggregation across “partial fills” (`is_full=false`)
* Efficiency read endpoint(s) and minimal reporting
* Correction factor per vehicle for odometer drift (tire size / gearing)
* PostGIS integration for GPS logging (future)
* Predictive maintenance alerts (oil changes, valve checks, etc.)
* Multi-user support (future)