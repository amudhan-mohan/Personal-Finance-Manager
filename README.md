# Personal Finance Manager (PFM)

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4.3-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![License](https://img.shields.io/badge/License-MIT-0f172a?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Production_Ready-047857?style=flat-square)]()
[![Design System](https://img.shields.io/badge/Theme-Corporate_MNC_Solid-slate?style=flat-square)]()

---

## 1. System Design (Diagram Flow)

```mermaid
flowchart TD
    subgraph S1 ["1. Client Interaction and Action Trigger"]
        UserAction["User Action or Form Submission or AJAX Call"] --> CSRFValidate{"Is CSRF Token Valid?"}
    end

    subgraph S2 ["2. Security Guardrail and Boundary Enforcement"]
        CSRFValidate -->|Invalid| RejectAction["Reject Request and Trigger Security Toast"]
        CSRFValidate -->|Valid| TenantScope["Enforce Session user_id Filter for IDOR Defense"]
        TenantScope --> XSSClean["Contextual HTML Sanitization and Escaping"]
    end

    subgraph S3 ["3. Server-Side Domain Processing"]
        XSSClean --> DomainLogic["Compute Balances, Burn Rates and Forecasts"]
        DomainLogic --> LedgerValidate{"Double-Entry Ledger Balancing Check"}
    end

    subgraph S4 ["4. Atomic Database Layer"]
        LedgerValidate -->|Valid| StartTx["Execute START TRANSACTION in InnoDB"]
        StartTx --> ExecQuery["Run Parameterized Prepared Statements"]
        ExecQuery --> TxCheck{"Did Any Query Throw An Exception?"}
        TxCheck -->|No| CommitTx["Execute COMMIT to Persist Changes"]
        TxCheck -->|Yes| RollbackTx["Execute ROLLBACK to Revert All Operations"]
    end

    subgraph S5 ["5. Client Feedback and Cache Control"]
        CommitTx --> SuccessToast["Dispatch Success Flash Toast Notification"]
        RollbackTx --> ErrorToast["Dispatch Reversible Failure Alert Toast"]
        SuccessToast --> CacheBust["Serve Fresh View with Dynamic CSS Versioning"]
    end
```

---

## 2. System Architecture & Topology (Diagram Flow)

```mermaid
flowchart TD
    subgraph Tier1 ["Tier 1: Client Presentation Layer"]
        C1["Desktop Browser: 1024px to 4K"]
        C2["Tablet Device: 768px to 1023px"]
        C3["Mobile Device: 360px to 767px"]
        UI["Tailwind CSS v4 Solid Theme and Chart.js"]
        C1 --> UI
        C2 --> UI
        C3 --> UI
    end

    subgraph Tier2 ["Tier 2: Security and Guardrail Boundary"]
        UI -->|HTTP Requests| Sec1["CSRF Guard via hash_equals"]
        Sec1 --> Sec2["Hardened Session Cookies with HttpOnly and Secure"]
        Sec2 --> Sec3["Input Validation and Contextual XSS Sanitizer"]
    end

    subgraph Tier3 ["Tier 3: Routing and Controller Layer"]
        Sec3 --> R1["Page Controllers in pages/"]
        Sec3 --> R2["Modular Asynchronous APIs in ajax/"]
    end

    subgraph Tier4 ["Tier 4: Domain Services and Business Logic"]
        R1 --> S1["Authentication Service"]
        R1 --> S2["General Ledger Engine"]
        R1 --> S3["Double-Entry Transfer Controller"]
        R1 --> S4["Predictive Cash Flow Simulator"]
        R1 --> S5["Budget Guardrail and Burn Rate Engine"]
        R1 --> S6["Bank Statement CSV Importer"]
        R2 --> S1
        R2 --> S2
        R2 --> S3
        R2 --> S4
        R2 --> S5
        R2 --> S6
    end

    subgraph Tier5 ["Tier 5: Data Persistence Layer"]
        S1 --> DBConn["MySQLi Connection Pool with utf8mb4"]
        S2 --> DBConn
        S3 --> DBConn
        S4 --> DBConn
        S5 --> DBConn
        S6 --> DBConn
        DBConn --> DB[("MySQL 8.0+ or MariaDB InnoDB Engine")]
        DB --- T1["users"]
        DB --- T2["accounts"]
        DB --- T3["transactions"]
        DB --- T4["transfers"]
        DB --- T5["budgets"]
        DB --- T6["recurring_bills"]
        DB --- T7["savings_goals"]
    end
```

---

## 3. End-to-End Application Workflow (Diagram Flow)

```mermaid
flowchart TD
    Entry(["User Enters Application"]) --> SessionCheck{"Is User Authenticated?"}

    SessionCheck -->|No| LoginPage["Login Portal at pages/login.php"]
    LoginPage --> SubmitLogin["Submit Credentials with CSRF Token"]
    SubmitLogin --> VerifyPassword{"Verify Password with bcrypt"}
    VerifyPassword -->|Failure| LoginError["Show Error Toast and Prompt Again"]
    VerifyPassword -->|Success| RegenSession["Regenerate Session ID and Set Secure Cookies"]

    SessionCheck -->|Yes| Dashboard["Executive Dashboard at pages/dashboard.php"]
    RegenSession --> Dashboard

    Dashboard --> M1["Accounts Portfolio"]
    Dashboard --> M2["Transaction Ledger"]
    Dashboard --> M3["Inter-Account Transfers"]
    Dashboard --> M4["Financial Intelligence Reports"]
    Dashboard --> M5["Monthly Budget Guardrails"]
    Dashboard --> M6["Recurring Bills and Subscriptions"]
    Dashboard --> M7["Savings Milestone Goals"]
    Dashboard --> M8["Predictive 30-Day Cash Flow"]
    Dashboard --> M9["Bank Statement CSV Importer"]

    M1 --> AJAX["Instant Data Sync via AJAX Endpoints"]
    M2 --> AJAX
    M3 --> AJAX
    M4 --> AJAX
    M5 --> AJAX
    M6 --> AJAX
    M7 --> AJAX
    M8 --> AJAX
    M9 --> AJAX

    AJAX --> PreparedSQL["Run Parameterized Prepared Statements with user_id"]
    PreparedSQL --> DBCommit[("ACID Database State Update")]
    DBCommit --> Toast["Interactive Dynamic Flash Toast Popup"]
```

---

## 4. Core Process Flow Diagrams

### 4.1. Atomic Inter-Account Transfer Execution Flow

```mermaid
sequenceDiagram
    autonumber
    actor User as User Browser
    participant Controller as transfers.php
    participant Auth as Auth and CSRF Guard
    participant Finance as FinanceManager Service
    participant DB as MySQL Database InnoDB

    User->>Controller: POST transfer [from_id, to_id, amount, csrf_token]
    Controller->>Auth: validateCSRFRequest()
    alt Invalid CSRF Token
        Auth-->>Controller: Reject Request
        Controller-->>User: Display Security Warning Toast
    else Valid Token
        Auth-->>Controller: Authorized
        Controller->>Finance: transferFunds()
        Finance->>DB: START TRANSACTION
        Finance->>DB: Lock Account Rows FOR UPDATE
        alt Insufficient Liquidity
            Finance->>DB: ROLLBACK
            Finance-->>Controller: Insufficient Balance Error
            Controller-->>User: Display Insufficient Funds Toast
        else Balance Available
            Finance->>DB: Debit Source Account
            Finance->>DB: Credit Target Account
            Finance->>DB: Record Transfer in Ledger
            Finance->>DB: COMMIT
            Finance-->>Controller: Success Confirmation
            Controller-->>User: Display Transfer Completed Toast
        end
    end
```

---

### 4.2. Automated Bank Statement CSV Reconciliation Flow

```mermaid
flowchart TD
    Start["User Selects Bank Account and Uploads Statement"] --> ValidateFile["Validate CSV File and MIME Type"]
    ValidateFile -->|Invalid File| FileError["Reject File and Display Toast Error"]
    ValidateFile -->|Valid File| ParseHeader["Parse Delimiters and Detect Headers"]
    ParseHeader --> UserMapping["Dynamic Column Mapping Confirmation"]
    UserMapping --> ProcessRows["Process Statement Rows"]
    ProcessRows --> DupCheck{"Is Duplicate Hash Found in Ledger?"}
    DupCheck -->|Duplicate Found| SkipRow["Flag Record and Skip Ingestion"]
    DupCheck -->|Unique Entry| NormalizeAmount["Normalize Currency and Categorize Entry"]
    NormalizeAmount --> QueueInsert["Add to Prepared Batch Insert Queue"]
    SkipRow --> CheckMore{"Are More Rows Pending?"}
    QueueInsert --> CheckMore
    CheckMore -->|Yes| ProcessRows
    CheckMore -->|No| AtomicCommit["Execute Atomic Ingestion with InnoDB COMMIT"]
    AtomicCommit --> BalanceRecalc["Recalculate Account Balance and Category Totals"]
    BalanceRecalc --> SummaryReport["Generate Reconciliation Audit Summary"]
```

---

### 4.3. Predictive 30-Day Cash Flow Simulation Engine Flow

```mermaid
flowchart LR
    A["Current Liquid Cash Reserves"] --> B["Query Active Recurring Obligations"]
    B --> C["Generate 30-Day Projection Matrix"]
    C --> D["Subtract Daily Scheduled Dues"]
    D --> E["Calculate Cumulative Daily Balance Horizon"]
    E --> F["Detect Projected Valley Low and Solvency Point"]
    F --> G["Render Dynamic Horizon Area Chart via Chart.js"]
    G --> H{"Is Valley Balance Negative?"}
    H -->|Yes| I["Display Liquidity Alert and Guardrail Warning"]
    H -->|No| J["Display Solvent Runway Indicator Badge"]
```

---

### 4.4. Authentication, Session Hardening & Multi-Tenant Scoping Flow

```mermaid
flowchart TD
    LoginAttempt["User Enters Username and Password"] --> RateCheck["CSRF Token Verification and Rate Guard"]
    RateCheck --> QueryUser["Query users Table by Username via Prepared Statement"]
    QueryUser --> HashVerify{"Verify Password with bcrypt"}
    HashVerify -->|Invalid Password| LogFail["Record Security Warning and Return Error Toast"]
    HashVerify -->|Valid Password| RegenSession["Regenerate Session ID to Prevent Session Fixation"]
    RegenSession --> SetCookies["Set Secure Cookie Flags: HttpOnly and SameSite"]
    SetCookies --> PopulateSession["Populate Session user_id and Identity"]
    PopulateSession --> RedirectApp["Redirect to Dashboard"]
    RedirectApp --> ScopedQueries["All Downstream Queries Scoped by user_id"]
```

---

## 5. Core Modules & Capabilities

| Module | Features & Capabilities |
| :--- | :--- |
| **Institutional Dashboard** | Real-time net worth calculation, monthly cash inflow vs. outflow velocity, quick transfer launcher, recent audit log, and category expenditure charts. |
| **Multi-Account Portfolio** | Checking, savings, credit cards, investments, and operational cash reserves. Tracks account numbers, balances, institution types, and status. |
| **Transaction Ledger** | Categorized income and expense logging with date filtering, category badges, dynamic search, and custom pagination. |
| **Double-Entry Transfers** | Atomic inter-account capital transfers with ACID transaction boundaries (`COMMIT` / `ROLLBACK`) ensuring zero funds discrepancy. |
| **Predictive Cash Flow** | 30-day forward-looking reserve projections based on scheduled income, recurring obligations, and historical spend velocity. |
| **Budgets & Allocations** | Category-specific monthly budget caps with live visual utilization meters and overspend warning indicators. |
| **Bills & Subscriptions** | Recurring liability tracker with due-date monitors, frequency engines (Monthly, Yearly, Weekly), and one-click payment logging. |
| **Savings Goals** | Target capital milestones, automatic percentage completion meters, target date calculators, and dedicated fund deposits. |
| **Bank Statement Importer** | Automated CSV parser with intelligent column schema mapping, duplicate transaction filtering, and batch ledger insertion. |
| **Financial Intelligence & Reports** | Cash flow breakdown by custom date ranges, category distribution charts, high-volume vendor analysis, and print-ready summary exports. |

---

## 6. Production CSS Build & Dynamic Cache-Busting

```html
<!-- Automatically rendered in all 12 page templates based on filemtime -->
<link rel="stylesheet" href="../assets/css/tailwind.css?v=20261114161839">
```

### Build Commands:
```bash
# Build and minify production stylesheet (updates ?v= timestamp automatically)
npm run build:css

# Watch for CSS changes in development
npm run watch:css
```

---

## 7. Architecture & Directory Layout

```text
Personal-Finance-Manager/
├── .htaccess                   # Apache server hardening, custom error handlers & security headers
├── ajax/                       # Modular AJAX endpoints for asynchronous actions
│   ├── accounts.php            # Account creation, editing, and status updates
│   ├── bills.php               # Recurring bills management & mark-as-paid handler
│   ├── budgets.php             # Budget creation & allocation limits
│   ├── filters.php             # Dynamic transaction filtering & search queries
│   ├── goals.php               # Savings goals tracking & deposit contributions
│   ├── import.php              # CSV bank statement parsing & batch ingestion
│   ├── login.php               # Authentication verification endpoint
│   ├── profile.php             # User profile, currency preferences & password changes
│   └── transactions.php        # Transaction CRUD operations
├── assets/                     # Frontend compiled assets & vectors
│   ├── css/
│   │   ├── input.css           # Tailwind CSS v4 source file with @source directives
│   │   └── tailwind.css        # Minified production stylesheet with cache-busting
│   └── img/
│       ├── account.svg         # Corporate account vector graphic
│       └── logo.svg            # Official brand mark with Rupee (₹) emblem
├── config/                     # Core runtime configuration
│   ├── constants.php           # Global application constants, asset_version() helper & session timers
│   └── database.php            # 4-parameter MySQLi connection pool with utf8mb4
├── includes/                   # Core business logic & shared UI templates
│   ├── auth.php                # Authentication class, session manager & CSRF guards
│   ├── footer.php              # Global footer with responsive layout & status indicator
│   ├── functions.php           # Ledger balance calculations, metrics & sanitization
│   └── header.php              # Navigation header, dropdown controller & meta tags
├── pages/                      # Application route controllers
│   ├── 403.php                 # 403 Access Forbidden error page
│   ├── 404.php                 # 404 Not Found error page
│   ├── 500.php                 # 500 Internal Server Error page
│   ├── accounts.php            # Accounts portfolio management
│   ├── bills.php               # Bills & subscription obligations
│   ├── budgets.php             # Budget limits & expenditure meters
│   ├── dashboard.php           # Main analytics dashboard & net worth overview
│   ├── forecast.php            # 30-day cash flow engine & forecast charts
│   ├── goals.php               # Savings targets & progress meters
│   ├── import.php              # CSV statement importer & column mapper
│   ├── login.php               # Secure user login portal
│   ├── profile.php             # Account settings & security preferences
│   ├── reports.php             # Financial intelligence & analytical charts
│   ├── transactions.php        # General ledger & transaction records
│   └── transfers.php           # Inter-account transfer execution
├── finance_manager.sql         # Production database schema & initial seed data
├── index.php                   # Gateway entrypoint & session route dispatcher
├── LICENSE                     # MIT License
├── logout.php                  # Safe session invalidator & redirect handler
├── package.json                # Tailwind CSS v4 compiler tooling & scripts
└── README.md                   # Repository documentation
```

---

## 8. Installation & Database Setup

### 1. Database Initialization
```sql
CREATE DATABASE finance_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Import schema from `finance_manager.sql`:
```bash
mysql -u root -p finance_manager < finance_manager.sql
```

Default credentials:
- **Username**: `admin`
- **Password**: `password`

### 2. Database Configuration
In [config/database.php](config/database.php):
```php
$this->host = getenv('DB_HOST') ?: 'localhost';
$this->username = getenv('DB_USER') ?: 'root';
$this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$this->database = getenv('DB_NAME') ?: 'finance_manager';
```

### 3. Running Locally
```bash
php -S localhost:8000
```
Navigate to `http://localhost:8000`.

---

## 9. Free Hosting & cPanel Deployment

Compatible with shared hosts (InfinityFree, 000webhost, Hostinger, cPanel, Plesk):
1. **Upload Files**: Transfer repository files to `public_html/` or `htdocs/`.
2. **Create Database**: Create MySQL database & user in cPanel.
3. **Import SQL**: Import [finance_manager.sql](finance_manager.sql) in phpMyAdmin.
4. **Update DB Credentials**: Configure host, user, password, and database in [config/database.php](config/database.php). Standard 4-parameter `mysqli` connection requires no port.

---

## 10. Developed By & License

**Amudhan Mohan** — *Full Stack Developer & Software Engineer*  
Licensed under the [MIT License](LICENSE).
