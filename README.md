# AI-Powered Customer Support Refund System (ai_refund_system)

An enterprise-grade, AI-powered e-commerce customer support refund platform that deterministically applies store refund policies, leverages Google Gemini 1.5 Flash for empathetic customer reasoning and structured classification, defends against adversarial prompt injections via a 3-tier security shield, and provides complete auditability and supervisor overrides through an administrative dashboard.

---

## Architecture Overview

```
                               ┌─────────────────────────────────────────┐
                               │             Web Browser                 │
                               │   (Customer Portal / Admin Dashboard)   │
                               └────────────────────┬────────────────────┘
                                                    │
                                             HTTP / Inertia
                                                    │
                                                    ▼
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │ Laravel 11 Application Container (:8000)                                               │
 │                                                                                        │
 │   ┌───────────────────────────────┐        ┌───────────────────────────────────────┐   │
 │   │    Inertia React Frontend     │        │          Backend Controllers          │   │
 │   │  • Customer Profile Switcher  │        │  • CustomerApiController              │   │
 │   │  • Order Selection & Badges   │◄──────►│  • RefundController                   │   │
 │   │  • Interactive Chat & Verdict │        │  • Supervisor Override Endpoint       │   │
 │   │  • Admin Audit Drawer (5-tab) │        │                                       │   │
 │   │  • Supervisor Override Panel  │        │                                       │   │
 │   └───────────────────────────────┘        └───────────────────┬───────────────────┘   │
 │                                                                │                       │
 │                                            ┌───────────────────▼───────────────────┐   │
 │                                            │       RefundEvaluationService         │   │
 │                                            └─────────┬───────────────────┬─────────┘   │
 │                                                      │                   │             │
 │                       ┌──────────────────────────────┴──────┐            │             │
 │                       ▼                                     ▼            ▼             │
 │        ┌─────────────────────────────┐        ┌─────────────────────────────┐          │
 │        │ 3-Tier Injection Guard      │        │ Refund Policy Rule Engine   │          │
 │        │ • Pre-filter regex patterns │        │ • Clause 1: Final Sale      │          │
 │        │ • XML system prompt sandbox │        │ • Clause 2: High Value >$500│          │
 │        │ • Post-evaluation invariant │        │ • Clause 3: Damaged Goods   │          │
 │        │   override guardrails       │        │ • Clause 4: 30-Day Window   │          │
 │        └──────────────┬──────────────┘        │ • Clause 5: Fraud / Abuse   │          │
 │                       │                       │ • Clause 6: Valid Standard  │          │
 │                       ▼                       └──────────────┬──────────────┘          │
 │        ┌─────────────────────────────┐                       │                         │
 │        │ Gemini LLM Client           │                       │                         │
 │        │ • gemini-1.5-flash JSON API │                       │                         │
 │        │ • Deterministic Fallback    │◄──────────────────────┘                         │
 │        │   Engine (Zero-Config Mode) │                                                 │
 │        └─────────────────────────────┘                                                 │
 └──────────────────────────────────────┬─────────────────────────────────────────────────┘
                                        │ SQLite / PDO
                                        ▼
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │ Database Storage Layer                                                                 │
 │ • customers table (15 distinct test archetypes, risk tiers, fraud scores)              │
 │ • orders table (15 matched orders: final sale, damaged, >$500, expired/valid dates)    │
 │ • refund_requests table (full audit trail, reasoning logs, safety flags, overrides)    │
 └────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 6-Clause Refund Policy & Precedence

Requests are evaluated against 6 store policies evaluated with strict top-to-bottom priority:

1. **Clause 1 (Final Sale Non-Refundable):** Items designated as `is_final_sale=true` are strictly non-refundable (`DENIED`). Takes absolute precedence over all other clauses (even transit damage).
2. **Clause 2 (High Value Exceeding $500.00):** Orders where `price > 500.00` require human authorization and are escalated to senior claims management (`ESCALATED`). Takes precedence over transit damage.
3. **Clause 5 (Fraud & Abuse Risk Escalation):** Accounts with `risk_tier === 'HIGH'`, `fraud_score >= 70`, or return rates `>= 40%` are routed to Loss Prevention for identity and account verification (`ESCALATED`).
4. **Clause 4 (Return Window Expired):** Orders older than the 30-day delivery window are denied (`DENIED`), unless demonstrable extenuating circumstances (hospitalization, emergency surgery, natural disasters) are indicated in the customer message, in which case the request is escalated (`ESCALATED`).
5. **Clause 3 (Damaged Goods):** Items with verified transit damage delivered within the return window are fast-track approved with a prepaid return label (`APPROVED`).
6. **Clause 6 (Standard Valid Return):** Eligible items within the return window with low customer risk are approved (`APPROVED`).

---

## 3-Tier Prompt Injection Defense Shield

1. **Pre-Filter Regex Scanner (`PromptInjectionGuard`):** Inspects incoming customer messages for jailbreaks, prompt override directives, roleplay attacks (DAN, developer mode), forced JSON outputs, and delimiter breaking.
2. **XML Boundary Containment (`PromptBuilder`):** Wraps untrusted customer input within `<customer_message>` system XML boundaries and explicitly instructs the model to treat the text as untrusted customer data.
3. **Hard Invariant Post-Guardrails:** Programmatic barriers verify that the model's verdict cannot violate immutable business invariants. If an adversarial prompt tricks the LLM into approving a final sale item or an order over $500, the guardrail automatically intercepts and forces `DENIED` or `ESCALATED`.

---

## Offline Deterministic Fallback Mode

The system features **zero-config offline evaluation**:
- When `GEMINI_API_KEY` is not configured, or if network connectivity to Google's API fails, the backend seamlessly falls back to the in-memory `DeterministicPolicyEngine`.
- The fallback engine produces identical JSON DTO outputs and marks `evaluation_mode = 'DETERMINISTIC_FALLBACK'`, ensuring high availability and 100% test passing out of the box.

---

## Setup & Execution

### Option A: Local Execution
```bash
# 1. Install dependencies
composer install
npm install

# 2. Build frontend assets
npm run build

# 3. Initialize SQLite database & seed 15 archetypes
php artisan migrate:fresh --seed

# 4. Start local development server
php artisan serve
```

### Option B: Docker Containerization
```bash
docker-compose up --build
```
On container start, `docker-entrypoint.sh` automatically runs database migrations, executes seeders, verifies record assertions (>= 15 customers and orders), and launches the service on port 8000.

---

## Portals & Endpoints

- **Customer Portal:** `http://localhost:8000/`
  - Customer profile switcher (select among seeded CUST-1001 to CUST-1015).
  - Order selection dropdown with delivery date, price, and policy flags.
  - Quick-fill prompt chips (Standard return, Damaged goods, Hospital emergency, Injection test).
  - Rich AI Decision Card with uppercase status badges (`APPROVED`, `DENIED`, `ESCALATED`).

- **Admin & Supervisor Dashboard:** `http://localhost:8000/admin`
  - Real-time KPI Metric cards (Total Claims, Approved, Denied, Escalated).
  - Searchable and filterable audit table.
  - 5-Tab Slide-Over Forensic Audit Drawer (Context, Message, Policy, Safety, Override).
  - Supervisor Manual Override action with mandatory audit notes.

### Key REST APIs
- `GET /api/health`: Healthcheck probed by monitoring systems.
- `GET /api/customers`: Returns all 15 customer archetypes with risk profiles.
- `GET /api/customers/{id}/orders`: Returns orders and calculated delivery metrics.
- `POST /api/refunds/evaluate`: Core evaluation endpoint returning decision DTO.
- `GET /api/refund-requests`: Lists all audited refund claims with KPI metrics.
- `GET /api/refund-requests/{id}`: Full audit log details.
- `POST /api/refund-requests/{id}/override`: Supervisor override endpoint.

---

## Trade-offs & Assumptions

| Area | Decision | Rationale |
|---|---|---|
| **Authentication** | Intentionally omitted for demo purposes. | In production, this would use Laravel Sanctum with JWT tokens and row-level tenant scoping to isolate customer data per support agent session. |
| **Database** | SQLite instead of PostgreSQL/MySQL. | Zero-configuration portability for the Docker environment — no separate DB container required. Schema is fully portable to any PDO-compatible engine. |
| **AI Orchestration** | Direct Gemini HTTP calls instead of LangChain/CrewAI. | Keeps the app lightweight with zero heavyweight orchestration dependencies. Easier to audit, reason about, and unit-test. Structured JSON output mode replaces the need for prompt parsing. |
| **Prompt Injection** | System-prompt-level defense + post-evaluation invariants. | A fully production-grade setup would add a second LLM pass (e.g. a dedicated small classifier) as an input sanitizer. The hard invariant guardrails provide a deterministic last line of defense regardless of LLM output. |
| **Frontend Styling** | Tailwind CDN via `<script>` tag. | Avoids PostCSS configuration complexity in the demo. A production setup would use a local Tailwind build with PurgeCSS for optimal bundle size. |

---

## Verification & Testing

Run the automated test suites:

```powershell
# 1. PHPUnit feature and unit tests (77 tests, 1,946 assertions)
php artisan test

# 2. Complete opaque-box E2E acceptance test suite (336/336 tests, 100% Pass)
php ../e2e_tests/run_all.php

# 3. Cross-platform verification script
./scripts/verify.ps1   # Windows PowerShell
./scripts/verify.sh    # Linux / macOS Bash
```
