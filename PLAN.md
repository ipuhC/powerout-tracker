# Project Plan: Power Outage Reporting Platform with Live Map

## 1. Project Overview & Objectives
This project is a full-stack, real-time power outage reporting platform built as a senior technical assessment. It enables citizens to visualize active blackouts on a responsive map in real-time and empowers authenticated users to submit, edit, and close outage reports with custom geographical radii (0.5 km to 25 km).

### Key Architectural Choices
- **Backend:** Laravel 13 (PHP 8.4) RESTful API (JSON-only).
- **Authentication:** Laravel Sanctum using stateless Bearer Tokens (Personal Access Tokens) for client-server decoupling and mobile-readiness.
- **Database & Spatial Engine:** PostgreSQL 16 with PostGIS (`postgis/postgis:16-alpine`), using `geography(Point, 4326)` and GiST spatial indexing for high-performance bounding-box queries.
- **Frontend:** React 18+ with Vite, Zustand for state management, React Router, and a custom CSS design system adhering to the technical minimal visual guidelines.
- **Map Provider:** OpenStreetMap tiles via Leaflet, fully encapsulated behind an abstract `<OutageMap />` component adapter.
- **Expiration Guarantee:** Two-tier expiration strategy (real-time Eloquent query scopes + background Laravel Scheduler/Queue jobs with a 72-hour hard safety cap).
- **Abuse Prevention:** Configurable rate limiting (default 5 reports/hour) and active reports quota (default 3 active reports per user).

---

## 2. Work Breakdown & Milestones

### Phase 0: Planning, Architecture & Design Deliverables
- **Task 0.1: Initial Planning (`PLAN.md`)**
  - Break down milestones, tasks, and time estimates.
  - Identify project risks and practical mitigations.
  - *Estimate: 0.5 h*
- **Task 0.2: Architectural Specification (`ARCHITECTURE.md`)**
  - Design Mermaid system architecture diagram (containers, network flows).
  - Define Data Model (ERD) with spatial types, constraints, and indexes.
  - Document REST API contract (endpoints, request/response payloads, status codes).
  - Draft the 6 mandatory Architecture Decision Records (ADRs):
    1. Authentication Strategy: Sanctum Bearer Tokens.
    2. Database & Spatial Indexing: PostgreSQL + PostGIS with GiST.
    3. Expiration Strategy: Database query-level guarantee + Scheduler cleanup with 72h cap.
    4. Duplicate Reports Handling: Independent visual overlap with density opacity.
    5. Map Provider: Leaflet + OpenStreetMap with adapter pattern encapsulation.
    6. Frontend Stack: React + Zustand + React Router.
  - Outline Security policies and Scalability plan (handling 100,000 concurrent users during mass blackouts).
  - *Estimate: 2.0 h*

### Phase 1: Infrastructure & Docker Orchestration
- **Task 1.1: Multi-Container Docker Compose Setup**
  - Configure `docker-compose.yml` with isolated networks and persistent volumes:
    - `api`: PHP 8.4-FPM with `pdo_pgsql`, `redis`, `pcntl`, `bcmath`.
    - `web`: Nginx reverse proxy serving the API and client routes.
    - `db`: `postgis/postgis:16-alpine` with healthcheck.
    - `redis`: Redis alpine for cache, rate limiting, and queues.
    - `queue-worker`: Background worker executing asynchronous jobs.
    - `scheduler`: Daemon executing `artisan schedule:work`.
    - `client`: Node/Vite development server or static web server.
  - *Estimate: 1.5 h*
- **Task 1.2: Environment Configuration & Single-Command Startup**
  - Create comprehensive `.env.example` templates for both `/api` and `/client`.
  - Validate one-command initialization (`docker compose up -d`).
  - *Estimate: 1.0 h*

### Phase 2: Backend Core (Database, PostGIS & Authentication)
- **Task 2.1: PostGIS Setup & Database Migrations**
  - Enable PostGIS extension: `CREATE EXTENSION IF NOT EXISTS postgis;`.
  - Create `outage_reports` table with spatial `geography(Point, 4326)`, `radius_km`, duration type, status (`active`, `closed`, `expired`), timestamps, and foreign keys.
  - Add spatial GiST index on location and composite indexes for status and expiration.
  - *Estimate: 1.0 h*
- **Task 2.2: Eloquent Models, Casts, Enums & Seeders**
  - Define `OutageReport` model with spatial attribute accessors/mutators (X/Y coordinates to GeoJSON).
  - Define PHP Enums: `OutageStatus` (`active`, `closed`, `expired`) and `DurationType` (`fixed`, `indefinite`).
  - Create database factories and seeders generating realistic test users and sample city outages.
  - *Estimate: 1.0 h*
- **Task 2.3: Authentication API (Laravel Sanctum)**
  - Implement `/api/v1/auth/register`, `/api/v1/auth/login`, `/api/v1/auth/logout`, `/api/v1/auth/me`.
  - Configure rate limiting on auth endpoints to prevent brute-force attacks.
  - Standardize JSON API error handling and Form Requests.
  - *Estimate: 1.5 h*

### Phase 3: Backend Reports API, Business Logic & Spatial Engine
- **Task 3.1: Reports Management API**
  - Implement `POST /api/v1/reports` with Form Request validation (lat/lng bounds, 0.5–25 km radius, duration).
  - Implement `GET /api/v1/reports`: Paginated list of authenticated user's reports.
  - Implement `PATCH /api/v1/reports/{id}`: Update radius, duration, description of active reports.
  - Implement `POST /api/v1/reports/{id}/close`: Manual closure ('Power is back').
  - Create `OutageReportPolicy` enforcing strict ownership (users can only edit/close their own reports).
  - *Estimate: 1.5 h*
- **Task 3.2: Abuse Prevention & Rate Limiting**
  - Enforce user-based rate limiting on report creation (`OUTAGE_MAX_REPORTS_PER_HOUR`, default: 5/hr).
  - Enforce active reports quota rule (`OUTAGE_MAX_ACTIVE_REPORTS`, default: 3 active reports per user).
  - *Estimate: 0.5 h*
- **Task 3.3: Bulletproof Expiration Mechanism**
  - Implement Eloquent `scopeActive()` filtering `status = active` AND `expires_at > NOW()`.
  - Create `ExpireOutagesJob` and configure Laravel Scheduler to update expired records in background.
  - Enforce 72-hour maximum lifetime rule for all reports.
  - *Estimate: 1.0 h*
- **Task 3.4: Public Spatial Bounding-Box API**
  - Implement `GET /api/v1/public/outages` accepting `min_lat`, `min_lng`, `max_lat`, `max_lng`.
  - Query visible outages via PostGIS `ST_MakeEnvelope` and GiST index.
  - Implement `PublicOutageResource` guaranteeing zero leakage of personal identifiable information (PII).
  - *Estimate: 0.5 h*

### Phase 4: Frontend Foundation & Design System
- **Task 4.1: React Project Setup & Modular Architecture**
  - Initialize React 18+ with Vite.
  - Structure modular folders: `/services`, `/hooks`, `/store`, `/components`, `/views`.
  - Configure React Router with protected route guards.
  - *Estimate: 0.5 h*
- **Task 4.2: Design Tokens & Minimal Technical UI**
  - Implement CSS variables: `--color-bg: #FFFFFF`, subtle grid background `#F1F1F1`, `--color-accent: #E5484D`, `--color-accent-soft: #FDECEC`, `--color-border: #E4E4E4`, `--color-surface: #F5F5F5`.
  - Implement clean typography: Inter/System sans-serif with monospace labels, coordinates, and step numbers (`01`, `02`).
  - Build reusable UI primitives: Window-style panels with 3-dot headers, cards, status badges, buttons, and form inputs.
  - *Estimate: 1.0 h*
- **Task 4.3: HTTP Client & State Management**
  - Configure Axios instance with Bearer token interceptor and API error normalization.
  - Implement Zustand stores: `authStore` (user, token, login/logout actions) and `outageStore`.
  - Ensure loading, empty, and error feedback states across all views.
  - *Estimate: 1.5 h*

### Phase 5: Map Integration & Feature Views
- **Task 5.1: Map Adapter Component (`<OutageMap />`)**
  - Encapsulate Leaflet inside a dedicated component adapter to prevent vendor lock-in.
  - Render OpenStreetMap tiles with custom styling.
  - Render outage circles using `--color-accent` border and semi-transparent fill for additive density overlap.
  - *Estimate: 1.0 h*
- **Task 5.2: Public Outage Map & Landing Page**
  - Build landing page hero section with two-tone typography and 3-step 'How it works' cards.
  - Implement interactive public map with viewport bounding-box synchronization.
  - Add periodic background polling (e.g., 30s) without full-page reloads.
  - Add interactive popup showing outage details (start time, remaining time, radius, description) without PII.
  - *Estimate: 1.0 h*
- **Task 5.3: Report Creation View**
  - Interactive point selection on map (click or draggable marker).
  - Radius slider (0.5 to 25 km) with real-time visual circle preview.
  - Duration selector (fixed hours 1–72 vs. 'Until I close it').
  - Form validation with API error feedback and rate limit alerts.
  - *Estimate: 1.0 h*
- **Task 5.4: 'My Reports' Management Dashboard**
  - Paginated list of user's active and historical reports.
  - Status indicators, countdown timers, and edit modal (radius, duration, description).
  - One-click manual report resolution ('Power is back').
  - *Estimate: 0.5 h*

### Phase 6: Automated Testing & API Documentation
- **Task 6.1: Backend Test Suite (PHPUnit / Pest)**
  - Authentication tests (register, login, token issuance, invalid credentials).
  - Report creation validation (valid data, out-of-range coordinates/radius).
  - Authorization policy tests (users cannot edit or close another user's outage).
  - Abuse prevention tests (rate limiting and maximum active reports constraint).
  - Expiration tests (fixed duration, 'until closed' 72h safety cap, query scope guarantee).
  - Privacy tests (verify public endpoint does not leak `user_id`, `email`, or name).
  - *Estimate: 1.0 h*
- **Task 6.2: OpenAPI Specification & Developer Documentation**
  - Complete OpenAPI 3.0 / Swagger specification committed to the repository.
  - Write comprehensive `README.md` with step-by-step clean installation instructions, demo credentials, and design assumptions.
  - *Estimate: 1.0 h*

### Phase 7: Verification & Clean Install Validation
- **Task 7.1: Clean-Install Verification**
  - Clone repository in a fresh directory and run end-to-end setup following `README.md`.
  - Execute automated tests inside the container environment.
  - Validate all flows (register, create outage, view public map, edit, close, expire).
  - *Estimate: 0.5 h*

---

## 3. Effort Estimates Summary

| Phase | Description | Estimated Effort |
|---|---|---|
| **Phase 0** | Planning, Architecture & Design (`PLAN.md`, `ARCHITECTURE.md`) | 2.5 hours |
| **Phase 1** | Docker Infrastructure & Multi-Container Orchestration | 2.5 hours |
| **Phase 2** | Backend Core: PostGIS, Models, Enums & Authentication | 3.5 hours |
| **Phase 3** | Backend Reports API, Expiration Engine & Spatial Bounding-Box | 3.5 hours |
| **Phase 4** | Frontend Architecture, Design Tokens & State Management | 3.0 hours |
| **Phase 5** | Leaflet Map Adapter, Public Map & Outage Management Views | 3.5 hours |
| **Phase 6** | Automated Test Suite & OpenAPI / README Documentation | 2.0 hours |
| **Phase 7** | Clean Install Verification & Final Quality Assurance | 0.5 hours |
| **Total** | | **21.0 hours** |

---

## 4. Risk Assessment & Mitigation Strategies

| # | Risk Description | Severity | Impact | Mitigation Strategy |
|---|---|---|---|---|
| **R1** | **Delayed or failed scheduler** causes expired reports to continue appearing as active to users. | **High** | Critical business logic violation. | **Query-level enforcement:** Implement an Eloquent query scope (`scopeActive`) checking `expires_at > NOW()` at the database level on all public and private active queries. Even if the background job fails, no expired report will ever be returned. |
| **R2** | **Massive traffic surge** during a city-wide blackout degrades database performance. | **High** | High latency or database outage. | Use **PostGIS GiST 2D spatial indexing**, restrict bounding-box query area limits in Form Requests, cache public tile/bounding-box queries in Redis with short TTL, and isolate read queries. |
| **R3** | **Accidental PII leak** on the unauthenticated public map endpoint. | **High** | User privacy breach. | Create a dedicated `PublicOutageResource` that strictly whitelists non-sensitive fields (`id`, `lat`, `lng`, `radius_km`, `started_at`, `expires_at`, `description`). Write automated assertions asserting the absence of `user_id`, `email`, and `user` relations. |
| **R4** | **Map library tight coupling** makes future provider changes costly. | **Medium** | Architectural inflexibility. | Encapsulate Leaflet inside a reusable `<OutageMap />` component adapter exposing generic props (`center`, `radius`, `onSelectLocation`). No screen component interacts directly with the Leaflet `L` object. |
| **R5** | **Environment/Port conflicts** during evaluator clean install. | **Medium** | Evaluation failure / startup friction. | Use standard environment variable fallbacks in `.env.example`, ensure container health checks gate dependent services, and provide an automated setup command. |

---

## 5. Retrospective (Post-Implementation)
*(To be completed after final delivery without modifying original estimates above)*

- **Estimated Time vs. Actual Time Spent:**
  - *Phase 0:* Estimated 2.5 h | Actual: TBD
  - *Phase 1:* Estimated 2.5 h | Actual: TBD
  - *Phase 2:* Estimated 3.5 h | Actual: TBD
  - *Phase 3:* Estimated 3.5 h | Actual: TBD
  - *Phase 4:* Estimated 3.0 h | Actual: TBD
  - *Phase 5:* Estimated 3.5 h | Actual: TBD
  - *Phase 6:* Estimated 2.0 h | Actual: TBD
  - *Phase 7:* Estimated 0.5 h | Actual: TBD
  - *Total:* Estimated 21.0 h | Actual: TBD
- **Deviations & Root Causes:**
  - *TBD*
- **What would be done differently with more time:**
  - *TBD*
