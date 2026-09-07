# CampusEvent Hub — Enterprise Event Booking Management System

A production-grade, web-based **Event Booking Management System** engineered in **clean PHP 8+ (PDO)**, **MySQL (Normalized 3NF)**, **Modern Vanilla CSS3**, and **Lightweight Vanilla JavaScript**.

Designed specifically for universities, academic institutions, and enterprise organizations requiring role-based access control (Admin, Faculty/Coordinator, Student), atomic concurrency-safe ticketing, simulated payment gateway workflows, and printable cryptographic digital passes with QR verification.

---

## 1. System Architecture & Directory Hierarchy

```
event-booking-system/
├── config/
│   ├── config.php            # Global application constants, base URL, timezone, session configs
│   └── database.php          # Database PDO connection singleton with error trapping
├── database/
│   ├── schema.sql            # Normalized 3NF DDL tables, foreign keys, indexes, cascades
│   ├── seed.sql              # Rich sample data (Admin, Coordinators, Students, 8+ Events, Bookings)
│   └── setup.php             # Automated CLI database installer & password hash generator
├── includes/
│   ├── header.php            # Universal topbar, navigation, profile dropdown, mobile drawer
│   ├── footer.php            # Universal footer with platform status & script loaders
│   ├── sidebar.php           # Role-based dashboard sidebar navigation
│   ├── auth.php              # Authentication & RBAC guards (require_login, require_role)
│   ├── session.php           # Secure session bootstrap & flash messaging alerts
│   ├── security.php          # CSRF protection (csrf_field, verify_csrf_token), XSS escaping (e())
│   ├── validation.php        # Form input, date logic, capacity, and email validators
│   └── functions.php         # Presentation helpers, currency/date formatters, SVG icons
├── public/
│   ├── index.php             # Public landing page (Hero, Categories, Featured Events, Workflow)
│   ├── events.php            # Event catalog with search, category filters, date filters, sorting
│   ├── event-details.php     # Comprehensive Event details, schedule, venue, capacity gauge & CTA
│   ├── booking.php           # Step 1: Multi-ticket selection & attendee information form
│   ├── payment.php           # Step 2: Simulated multi-rail payment gateway (Card, UPI, NetBanking)
│   ├── confirmation.php      # Step 3: Instant booking confirmation & pass overview
│   ├── ticket-view.php       # Printable / PDF-ready digital ticket pass with verification QR/barcode
│   ├── verify-ticket.php     # Gate entrance ticket scanner & verification terminal
│   ├── login.php             # Multi-role sign-in with 1-click demo account switcher
│   ├── register.php          # Student account registration with Department & Student ID
│   ├── logout.php            # Secure session destruction
│   ├── install.php           # Web-based 1-click database installer & environment diagnostic wizard
│   └── assets/
│       ├── css/
│       │   ├── main.css      # Core design tokens, typography, buttons, tables, badges, modals
│       │   ├── landing.css   # Landing page hero, category cards, workflow steps
│       │   └── dashboard.css # Dashboard layouts, sidebar, KPI metric cards, filters
│       └── js/
│           ├── app.js        # Dropdowns, mobile drawer, flash dismiss, confirm dialogs
│           ├── booking.js    # Dynamic ticket calculator & attendee input repeater
│           └── charts.js     # Lightweight HTML5 Canvas bar and donut chart engines
├── admin/
│   ├── dashboard.php         # Enterprise dashboard with KPI widgets & HTML5 canvas analytics
│   ├── events/               # Event CRUD with faculty assignment and capacity management
│   ├── categories/           # Academic discipline & category management
│   ├── students/             # Enrolled student registry & lifetime booking inspection
│   ├── coordinators/         # Faculty coordinator provisioning and roster
│   ├── bookings/             # Global booking audit trail & status overrides
│   ├── payments/             # Financial payment transactions log & settlements
│   └── reports/              # Executive intelligence reports & streaming CSV data exporter
├── coordinator/
│   ├── dashboard.php         # Coordinator overview (Assigned events, attendance velocity)
│   ├── events/               # Department event management (Create, Edit, Status toggles)
│   ├── attendees/            # Live gate check-in desk with instant check-in action
│   └── reports/              # Attendance show-rates and CSV manifest downloads
├── student/
│   ├── dashboard.php         # Student hub: upcoming passes, booking counts, notification feed
│   ├── tickets.php           # My Digital Passes gallery with direct print actions
│   ├── bookings.php          # Booking history with filterable status & self-service cancellation
│   └── profile.php           # Academic profile management & password updates
└── README.md                 # Technical documentation & setup guide
```

---

## 2. Relational Database Schema (3NF Normalized)

The database schema enforces 3NF normalization, strict foreign key constraints with `ON DELETE CASCADE` or `SET NULL`, unique indexes, and timestamp auditing.

1. **`users`**: Core identity table (`id`, `role`, `name`, `email` [UNIQUE], `password_hash`, `phone`, `status`, `created_at`, `updated_at`).
2. **`students`**: Student extension table (`user_id` [FK], `student_id_number` [UNIQUE], `department`, `year_of_study`, `emergency_contact`).
3. **`coordinators`**: Faculty coordinator extension (`user_id` [FK], `department`, `designation`, `office_location`).
4. **`event_categories`**: Disciplines (`id`, `name` [UNIQUE], `slug` [UNIQUE], `description`, `icon`).
5. **`events`**: Events directory (`id`, `category_id` [FK], `coordinator_id` [FK], `title`, `slug` [UNIQUE], `short_description`, `description`, `event_date`, `start_time`, `end_time`, `venue`, `venue_address`, `ticket_price`, `max_capacity`, `available_seats`, `registration_deadline`, `status`, `is_featured`).
6. **`bookings`**: Transaction header (`id`, `booking_reference` [UNIQUE], `user_id` [FK], `event_id` [FK], `quantity`, `unit_price`, `total_amount`, `status`, `cancellation_reason`, `booked_at`).
7. **`booking_attendees`**: Digital ticket passes (`id`, `booking_id` [FK], `attendee_name`, `attendee_email`, `ticket_code` [UNIQUE], `is_checked_in`, `checked_in_at`).
8. **`payments`**: Payment audit records (`id`, `booking_id` [FK], `transaction_reference` [UNIQUE], `payment_method`, `amount`, `status`, `gateway_response`, `paid_at`).
9. **`notifications`**: User alerts (`id`, `user_id` [FK], `title`, `message`, `link`, `is_read`, `created_at`).
10. **`audit_logs`**: System audit trail (`id`, `user_id` [FK], `action`, `details`, `ip_address`, `created_at`).

---

## 3. Quick Setup & Installation Guide (XAMPP / PHP 8+)

### Option A: 1-Click Web Installer (Recommended)
1. Place the project inside your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\event-booking-system\
   ```
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open your browser and navigate to:
   ```
   http://localhost/event-booking-system/public/install.php
   ```
4. Review the environment diagnostics and click **"Run 1-Click Database Setup & Seed"**.
5. All 10 tables, relational indexes, and rich sample data will be provisioned automatically.

### Option B: CLI Setup Script
Run the automated PHP migration script from your terminal:
```bash
php database/setup.php
```

---

## 4. Pre-Configured Demo Accounts

For rapid grading, review, and demonstration, the system includes pre-seeded accounts across all three user roles:

| Role | Email Address | Password | Privileges |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@campus.edu` | `Admin@123` | Full system access, event management, user directory, global bookings, financial audits, CSV exports |
| **Faculty Coordinator** | `coordinator@campus.edu` | `Coord@123` | Department event creation, live check-in desk, attendee rosters, attendance reports |
| **Enrolled Student** | `student@campus.edu` | `Student@123` | Browse catalog, multi-ticket checkout, simulated payment, printable QR passes, booking cancellation |

*(Note: The login page includes a fast 1-click demo role selector button).*

---

## 5. Security & Architectural Standards

- **Role-Based Access Control (RBAC)**: Protected routes enforce `require_role('admin')`, `require_role('coordinator')`, or `require_role('student')`.
- **CSRF Defense**: All state-changing POST forms embed `<?= csrf_field() ?>` and validate tokens server-side.
- **SQL Injection Defense**: 100% of database interactions execute through PDO prepared statements with bound parameters.
- **XSS Prevention**: Output variables are escaped via the `e()` helper function using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Atomic Concurrency Protection**: Multi-ticket bookings lock the event row (`FOR UPDATE`) within an SQL transaction and verify real-time `available_seats` before decrementing to prevent over-booking.
- **Cryptographic Pass Tokens**: Each attendee receives a unique verification code (e.g. `TCK-8841-A1`) and SVG verification matrix for gate entrance scanning.

---

## 6. Standalone Pure HTML Edition (`html-version/`)

If you want to run or demonstrate the project **immediately without setting up a PHP or MySQL web server**, a complete standalone **Pure HTML/CSS/JS Edition** is located inside the `html-version/` folder!

### How to Run:
1. Navigate to the `html-version/` folder on your computer:
   ```
   c:\Users\vettr\OneDrive\Desktop\EVENT BOOKING\html-version\
   ```
2. **Double-click `index.html`** to open it directly in any browser (Google Chrome, Microsoft Edge, Mozilla Firefox, Safari).
3. **No server, no installation, and no database configuration required.**

### Interactive Features in the HTML Edition:
- **LocalStorage Reactive Mock Database (`mock-db.js`)**: Real-time event bookings, dynamic ticket generation, attendee lists, capacity decrements, and cancellations stored locally in your browser.
- **Full End-to-End User Journeys**:
  - `index.html`: Public Landing page with live featured events and stats.
  - `events.html`: Real-time instant search & category/price filtering.
  - `event-details.html`: Dynamic event schedules and capacity counters.
  - `booking.html`: Multi-ticket selection with dynamic attendee repeater.
  - `payment.html`: Simulated checkout gateway (Credit/Debit Card, UPI, NetBanking).
  - `confirmation.html`: Instant booking confirmation with voucher download links.
  - `ticket-view.html`: Digital printable ticket passes with QR codes.
  - `verify-ticket.html`: Interactive gate entrance ticket scanner and validator.
  - `login.html` & `register.html`: 1-click role switcher (Admin, Coordinator, Student).
  - `student-dashboard.html`: Student pass viewer & cancellation portal.
  - `coordinator-dashboard.html`: Faculty coordinator event management & live check-in list.
  - `admin-dashboard.html`: Enterprise analytics with responsive HTML5 Canvas charts.

