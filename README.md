# Power Outage Reporting Platform with Live Map

[![Docker Compose](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](docker-compose.yml)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4--FPM-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![React 18](https://img.shields.io/badge/React-18-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![PostgreSQL 16 + PostGIS](https://img.shields.io/badge/PostGIS-3.5%20%7C%20Postgres%2016-336791?logo=postgresql&logoColor=white)](https://postgis.net)
[![Redis 7](https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white)](https://redis.io)

A production-grade, real-time power outage reporting platform built as a senior full-stack technical assessment. The platform enables citizens to visualize active electrical blackouts on an interactive live map and empowers authenticated users to report, update, and resolve power outages with custom geospatial radii (0.5 km to 25 km).

---

## Table of Contents

1. [System Overview & Architecture](#1-system-overview--architecture)
2. [Prerequisites](#2-prerequisites)
3. [Quickstart (Turnkey Setup)](#3-quickstart-turnkey-setup)
4. [Step-by-Step Setup Guide](#4-step-by-step-setup-guide)
5. [Database Migrations, Seeders & Tests](#5-database-migrations-seeders--tests)
6. [Map Provider Configuration](#6-map-provider-configuration)
7. [Demo Credentials & Seeded Data](#7-demo-credentials--seeded-data)
8. [Architectural Assumptions](#8-architectural-assumptions)
9. [Bonus Features Implemented](#9-bonus-features-implemented)
10. [Known Limitations & Future Improvements](#10-known-limitations--future-improvements)
11. [Useful Docker & Operational Commands](#11-useful-docker--operational-commands)

---

## 1. System Overview & Architecture

The application is architected as a monorepo containing an API backend and a frontend SPA, containerized into 7 isolated services communicating over a dedicated Docker bridge network (`powerout_network`):

```
├── api/             # Laravel 13 REST API (PHP 8.4-FPM, PostGIS, Redis, Sanctum)
├── client/          # React 18 + Vite SPA (TypeScript, Zustand, Leaflet Adapter)
├── docker-compose.yml # 7 Orchestrated services (web, api, client, db, redis, worker, scheduler)
├── setup.sh         # Automated one-step setup script
├── ARCHITECTURE.md  # Comprehensive architectural specification, ERD, and 6 ADRs
├── PLAN.md          # Implementation plan, milestone tracking, and retrospective
└── postman_collection.json # Official Postman collection with automatic Bearer token capture
```

### Container Services

| Service | Container Name | Technology | Responsibility | Exposed Port |
| :--- | :--- | :--- | :--- | :--- |
| **`web`** | `powerout_web` | Nginx 1.25 Alpine | Reverse proxy, static asset handler, FastCGI router | `8080` (Host) |
| **`api`** | `powerout_api` | PHP 8.4-FPM Alpine | Laravel 13 JSON REST API application | `9000` (Internal) |
| **`client`** | `powerout_client` | Node 20 Alpine / Vite | React 18 SPA development server with HMR | `5173` (Host) |
| **`db`** | `powerout_db` | PostgreSQL 16 + PostGIS | Spatial relational engine, `geography(Point, 4326)` | `5432` (Host) |
| **`redis`** | `powerout_redis` | Redis 7 Alpine | Rate limiter, cache store, queue message broker | `6379` (Host) |
| **`queue-worker`** | `powerout_queue_worker` | PHP 8.4 CLI Alpine | Asynchronous background job worker (`queue:work`) | — |
| **`scheduler`** | `powerout_scheduler` | PHP 8.4 CLI Alpine | Recurring task execution daemon (`schedule:work`) | — |

---

## 2. Prerequisites

Ensure the following tools are installed on your host system:

- **Docker Engine:** `24.0+`
- **Docker Compose:** `v2.20+` (included in Docker Desktop)
- **Git**
- Available host ports: `8080`, `5173`, `5432`, `6379`.

---

## 3. Quickstart (Turnkey Setup)

For the fastest evaluation, execute the automated setup script from the project root:

```bash
chmod +x setup.sh
./setup.sh
```

This single command will:
1. Copy `.env.example` templates to `.env`, `api/.env`, and `client/.env`.
2. Install Composer dependencies inside a temporary Docker container.
3. Configure required storage and bootstrap cache permissions.
4. Generate the Laravel application encryption key (`APP_KEY`).
5. Start all 7 Docker Compose containers in detached mode.
6. Run database migrations with PostGIS extensions and load realistic seeders.

Once complete, access the application:
- **Frontend SPA:** [http://localhost:5173](http://localhost:5173)
- **Backend API:** [http://localhost:8080](http://localhost:8080)
- **Demo Login:** `demo@powerout.test` / `password`

---

## 4. Step-by-Step Setup Guide

If you prefer executing each step manually:

### Step 1: Clone Repository & Prepare Environment Files

```bash
# Clone the repository
git clone https://github.com/ipuhC/powerout-tracker.git
cd powerout-tracker

# Copy environment templates
cp .env.example .env
cp api/.env.example api/.env
cp client/.env.example client/.env
```

### Step 2: Install Backend Dependencies

Since PHP and Composer are containerized, install dependencies through Docker:

```bash
docker compose run --rm --user root api composer install --no-interaction --prefer-dist --optimize-autoloader
```

### Step 3: Configure Storage Permissions & Encryption Key

```bash
# Ensure storage and bootstrap cache are writable by www-data
docker compose run --rm --user root api chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate Laravel cryptographic APP_KEY
docker compose run --rm api php artisan key:generate
```

### Step 4: Start Docker Services

```bash
docker compose up -d
```

Verify that all containers are healthy and running:

```bash
docker compose ps
```

### Step 5: Run Database Migrations & Seeders

```bash
docker compose exec api php artisan migrate --seed
```

---

## 5. Database Migrations, Seeders & Tests

### Running Migrations and Fresh Resets

To execute pending database migrations:
```bash
docker compose exec api php artisan migrate
```

To perform a clean database reset and re-seed sample data:
```bash
docker compose exec api php artisan migrate:fresh --seed
```

### Automated Backend Test Suite

The test suite covers authentication, input validation, PostGIS spatial queries, expiration lifecycle, active report quotas, ownership authorization policies, and privacy protection (zero PII leaks):

```bash
docker compose exec api php artisan test
```

**Test Coverage Summary:**
- `AuthTest`: User registration, email uniqueness, login credential validation, token issuance, profile access (`/me`), and token revocation on logout.
- `OutageReportModelTest`:
  - Geodesic spatial calculation and GiST bounding-box filtering (`scopeWithinBoundingBox`).
  - Strict query-level expiration guarantee (`scopeActive()`) ensuring expired records are never returned even under queue latency.
  - 72-hour hard safety cap enforcement on indefinite reports.
  - User active report quota constraint tracking.
- `ExampleTest`: API health and standard responses.

---

## 6. Map Provider Configuration

### Chosen Provider: OpenStreetMap + Leaflet (100% Free & Turnkey)

To ensure evaluators can run the project **without paying, signing up, or creating third-party billing accounts**, the platform is configured out-of-the-box with **OpenStreetMap tiles via Leaflet**:
- **Zero Cost:** Evaluators require no API keys or credit card registration.
- **Immediate Execution:** The live map renders instantly upon startup.

### Architectural Decoupling: Adapter Pattern

The map implementation is strictly encapsulated behind an abstract React adapter component:
[`client/src/components/map/OutageMap.tsx`](file:///home/manuel/proyectos/prueba-tecnica/client/src/components/map/OutageMap.tsx)

No screen or view interacts directly with the underlying map SDK. The component exposes a generic, provider-agnostic interface:

```typescript
interface OutageMapProps {
  center: [number, number];
  zoom?: number;
  outages?: OutageDto[];
  selectedLocation?: { lat: number; lng: number } | null;
  radiusKm?: number;
  interactive?: boolean;
  onLocationSelect?: (coords: { lat: number; lng: number }) => void;
}
```

### Switching to Google Maps in Production

If an organization chooses Google Maps Platform:
1. **API Key Setup:** Obtain a key from the Google Cloud Console with the **Maps JavaScript API** enabled.
2. **Environment Variable:** Set `VITE_GOOGLE_MAPS_KEY=your_key_here` in `client/.env`.
3. **Key Restrictions (Security Mandatory):**
   - **HTTP Referrer Restrictions:** Restrict the key to authorized domain origins (e.g. `https://powerout.example.com/*`).
   - **API Scope Restrictions:** Limit the key strictly to Maps JavaScript API to prevent misuse across Geocoding or Places APIs.
   - **Budget Quotas & Alerts:** Configure daily request caps and email spending alerts in Google Cloud Billing.

---

## 7. Demo Credentials & Seeded Data

The seeder initializes **3 test users** and **realistic sample outages** centered in Estado Carabobo, Venezuela (Valencia, Naguanagua, and San Diego):

### User Credentials

| User Name | Email Address | Password | Role / Purpose |
| :--- | :--- | :--- | :--- |
| **Demo User** | `demo@powerout.test` | `password` | Primary assessment user (has active, closed, and expired reports) |
| **Jane Citizen** | `jane@powerout.test` | `password` | Community reporter (active outages in El Trigal / Las Chimeneas) |
| **Alex Rivera** | `alex@powerout.test` | `password` | Community reporter (active outages in Guaparo / Naguanagua) |

### Sample Outages Overview

- **Active Fixed Duration Outages:** Valencia (El Viñedo, Av. Carlos Sanda), San Diego (El Remanso), Naguanagua (La Granja).
- **Active "Until I Close It" Outages:** Casco Central de Valencia (Plaza Bolívar), San Diego (Castillito).
- **Expired Outages:** Historical blackouts in Los Guayos and Naguanagua with status set to `expired`.
- **Manually Resolved Outages:** Completed repairs in Prebo and Lomas del Este marked as `closed`.

---

## 8. Architectural Assumptions

1. **Bearer Tokens over Cookies (ADR-1):** Sanctum Personal Access Tokens were selected instead of SPA session cookies. This completely decouples frontend and backend deployment targets, avoids cross-origin domain cookie synchronization issues across local Docker ports, and enables mobile/CLI client readiness.
2. **PostgreSQL 16 with PostGIS (ADR-2):** Chosen over standard MySQL 8 spatial columns. PostGIS provides true geodesic spatial computations via `geography(Point, 4326)` in meters on the WGS 84 ellipsoid, backed by native GiST indexing for fast bounding-box queries (`ST_DWithin` and `ST_MakeEnvelope`).
3. **Two-Tier Expiration Model (ADR-3):** Background cron jobs alone are insufficient to guarantee real-time data integrity. The platform couples:
   - **Tier 1 (Database Query Scope):** An Eloquent `scopeActive()` filters `status = 'active' AND expires_at > NOW()` on every query, ensuring zero stale data is ever presented to citizens.
   - **Tier 2 (Asynchronous Worker):** A recurring scheduler job updates expired records to `status = 'expired'` in bulk and emits domain events.
   - **72-Hour Safety Cap:** Indefinite reports ("Until I close it") are capped at a maximum lifetime of 72 hours to prevent forgotten reports from polluting the map.
4. **Independent Overlapping Circles (ADR-4):** Blackout reports frequently overlap in geographical regions. Merging them automatically would destroy user ownership and unique descriptions. The frontend renders overlapping reports with semi-transparent fills and accented borders, naturally visualizing higher blackout density through additive optical density.
5. **Zero PII Exposure (Security Policy):** The unauthenticated public endpoint (`GET /api/v1/public/outages`) utilizes a strict `PublicOutageResource` that explicitly excludes `user_id`, `user` relationships, email, name, and IP addresses.

---

## 9. Bonus Features Implemented

- [x] **Strict TypeScript & Modular Design System (Bonus B4):** Fully typed frontend codebase (`strict: true`) with custom design tokens (`--color-accent: #E5484D`, subtle technical grid background, window panels with 3-dot headers, and Lucide icon integration).
- [x] **Container Health Checks & Least-Privilege Security (Bonus B7):** Complete service healthchecks (`pg_isready`, `redis-cli ping`), unprivileged container execution (`www-data` non-root user in Alpine PHP-FPM), and persistent named volumes.
- [x] **Anti-Abuse Rate Limiting & User Quotas (Bonus B9):** Enforces a maximum of 5 report creations per hour (`throttle:5,60`) and restricts authenticated users to a maximum of 3 concurrent active reports (`OUTAGE_MAX_ACTIVE_REPORTS=3`).
- [x] **Comprehensive Postman Collection:** Ready-to-import [`postman_collection.json`](file:///home/manuel/proyectos/prueba-tecnica/postman_collection.json) featuring test scripts that automatically capture and persist the Bearer token into collection variables upon login or registration.

---

## 10. Known Limitations & Future Improvements

With additional development time, the following enhancements are planned:
1. **Real-Time WebSockets (Bonus B1):** Replace the current 30-second viewport polling with Laravel Reverb or Pusher WebSocket channels for push-based map updates.
2. **Community Confirmation (Bonus B2):** Introduce a "+1 Still No Power" and "Power Restored" citizen voting system with confidence scoring.
3. **Spatial Clustering (Bonus B5):** Integrate PostGIS `ST_ClusterDBSCAN` or client-side circle clustering at low zoom levels (< 10) to aggregate dense metropolitan outage clusters.
4. **Cached Reverse Geocoding (Bonus B8):** Integrate OpenStreetMap Nominatim with Redis caching to display approximate neighborhood names alongside coordinates.

---

## 11. Useful Docker & Operational Commands

```bash
# View real-time logs across all services
docker compose logs -f

# View logs for a specific service
docker compose logs -f api
docker compose logs -f queue-worker
docker compose logs -f scheduler

# Stop all services without deleting data
docker compose stop

# Restart all services
docker compose restart

# Stop and remove containers and networks (preserves volumes)
docker compose down

# Stop and remove all containers, networks, and persistent database volumes
docker compose down -v

# Open a shell inside the PHP API container
docker compose exec api sh

# Open a PostgreSQL psql session
docker compose exec db psql -U powerout_user -d powerout_db

# Open a Redis CLI session
docker compose exec redis redis-cli
```

---

*Authored by **Manuel Peralta** — Senior Full-Stack Assessment submission.*
