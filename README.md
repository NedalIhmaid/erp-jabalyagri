# Al-Jabali Agricultural Company Management System

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-v5-F59E0B?style=for-the-badge&logo=laravel&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**Sales Approval & HR Management System**

[Features](#features) • [Workflow](#approval-workflows) • [Installation](#installation) • [Testing](#testing) • [Tech Stack](#technology-stack)

</div>

---

## 📋 Overview

A comprehensive web-based management system for **Al-Jabali Agricultural Company** that streamlines:
- 📦 Multi-stage sales approval workflow (5 stages)
- 📝 Daily field visit tracking by engineers
- 🏖️ HR requests management (leaves & departures)
- 📊 Role-based dashboards and reporting
- 🌐 Bilingual interface (Arabic/English)

---

## ✨ Features

### Core Modules

| Module | Description |
|--------|-------------|
| **Sales Approval** | 5-stage approval workflow with PDF export |
| **Daily Visits** | Field engineer visit tracking with GPS & photos |
| **HR Requests** | Leave & departure management with balance tracking |
| **Product Catalog** | Categories, products, and units management |
| **User Management** | Role-based access control via Filament Shield |
| **Audit Logging** | Complete audit trail for all critical actions |

### Key Capabilities

- ✅ **PDF Export** — Generate professional PDF documents for approved sales requests
- ✅ **Form Validation** — Inline validation for quantities, dates, phone numbers, and coordinates
- ✅ **Real-time Notifications** — Email & database notifications for all workflow actions
- ✅ **Data Export** — Excel export for sales and HR reports
- ✅ **Leave Balance Tracking** — Automatic calculation of remaining leave days
- ✅ **Role-Based Access** — 6 distinct roles with granular permissions

---

## 🔄 Approval Workflows

### Sales Approval Workflow (5 Stages)

```
┌──────────────┐
│   Engineer   │  Submits sales request
│  (Submit)    │  SAR-YYYYMMDD-XXXX
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│  Stage 1: Warehouse Keeper                              │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ✓ Approve → Stage 2                               │  │
│  │ ✗ Reject → Cancelled (notify engineer)            │  │
│  │ ↺ Return → Engineer edits & resubmits             │  │
│  └───────────────────────────────────────────────────┘  │
└──────┬──────────────────────────────────────────────────┘
       │ Approve
       ▼
┌─────────────────────────────────────────────────────────┐
│  Stage 2: Sales Manager                                 │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ✓ Approve → Stage 3                               │  │
│  │ ✗ Reject → Cancelled (notify engineer)            │  │
│  └───────────────────────────────────────────────────┘  │
└──────┬──────────────────────────────────────────────────┘
       │ Approve
       ▼
┌─────────────────────────────────────────────────────────┐
│  Stage 3: Purchasing Manager                            │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ✓ Approve → Stage 4                               │  │
│  │ ✗ Reject → Cancelled (notify engineer)            │  │
│  └───────────────────────────────────────────────────┘  │
└──────┬──────────────────────────────────────────────────┘
       │ Approve
       ▼
┌─────────────────────────────────────────────────────────┐
│  Stage 4: Financial Manager                             │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ✓ Approve → Stage 5                               │  │
│  │ ✗ Reject → Cancelled (notify engineer)            │  │
│  └───────────────────────────────────────────────────┘  │
└──────┬──────────────────────────────────────────────────┘
       │ Approve
       ▼
┌─────────────────────────────────────────────────────────┐
│  Stage 5: General Manager                               │
│  ┌───────────────────────────────────────────────────┐  │
│  │ 👁 View Only + Generate Reports                    │  │
│  │ Status → Approved                                 │  │
│  │ 📄 PDF Export Available                            │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

#### Status Flow

```
                    ┌──────────────────────────────────────┐
                    │                                      │
  ┌───────┐  Submit  ▼        ┌──────────┐                ┌──────────┐
  │Pending ├────────► In Progress ├───Reject──► Cancelled │
  └───────┘                    └──────────┘                └──────────┘
                    │
                    │ Return (Stage 1 only)
                    ▼
              ┌──────────┐
              │ Returned ├──Resubmit──► In Progress (restart)
              └──────────┘
```

#### Actions by Role

| Role | Stage | Actions |
|------|-------|---------|
| **Engineer** | — | Create, Edit (returned only), View own |
| **Warehouse Keeper** | 1 | Approve, Reject, **Return** |
| **Sales Manager** | 2 | Approve, Reject |
| **Purchasing Manager** | 3 | Approve, Reject |
| **Financial Manager** | 4 | Approve, Reject |
| **General Manager** | 5 | View, Reports, PDF Export |

---

### HR Request Workflow (2 Stages)

```
┌──────────────┐
│  Employee    │  Submits HR request
│  (Submit)    │  (Leave / Departure)
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│  Stage 1: Direct Manager                                │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ✓ Approve → Stage 2                               │  │
│  │ ✗ Reject → Rejected (notify employee)             │  │
│  └───────────────────────────────────────────────────┘  │
└──────┬──────────────────────────────────────────────────┘
       │ Approve
       ▼
┌─────────────────────────────────────────────────────────┐
│  Stage 2: General Manager                               │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ✓ Approve → Approved                              │  │
│  │ ✗ Reject → Rejected (notify employee)             │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

#### Leave Types (9 Types)

| Type | Arabic | Requires Attachment |
|------|--------|---------------------|
| Annual Leave | إجازة سنوية | No |
| Sick Leave | إجازة مرضية | Yes (medical report) |
| Unpaid Leave | إجازة غير مدفوعة | No |
| Bereavement (1st) | وفاة درجة أولى | No |
| Bereavement (2nd) | وفاة درجة ثانية | No |
| Marriage Leave | إجازة زواج | No |
| Paternity Leave | إجازة مولود جديد | No |
| Early Departure | مغادرة | No |
| Departure from Annual | مغادرة من الإجازات | No |

---

## 🏗️ Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **Backend** | Laravel | 13.x |
| **Language** | PHP | 8.3+ |
| **Admin Panel** | Filament PHP | v5.x |
| **Frontend** | Livewire | v4 |
| **CSS** | Tailwind CSS | 4.x |
| **Database** | MySQL | 8.x |
| **Authorization** | Spatie Permission + Filament Shield | |
| **PDF Generation** | DomPDF | |
| **Activity Logging** | Spatie Activity Log | |
| **Testing** | Laravel Dusk + PHPUnit | |

---

## 🚀 Installation

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js & NPM
- MySQL 8.x

### Setup Steps

```bash
# 1. Clone the repository
cd /Applications/ServBay/www/al-jabali-co

# 2. Install dependencies
composer install
npm install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env file
# DB_DATABASE=al_jabali
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# 5. Run migrations
php artisan migrate

# 6. Seed roles, permissions, and sample data
php artisan db:seed

# 7. Build assets
npm run build

# 8. Start development server
composer run dev
```

### Default Users

After seeding, you can log in with:

| Role | Email | Password |
|------|-------|----------|
| General Manager | gm@aljabali.com | password |
| Sales Manager | sales@aljabali.com | password |
| Engineer | engineer@aljabali.com | password |

> ⚠️ **Note:** Change default passwords in production!

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run browser tests (requires Dusk)
php artisan dusk

# Run specific test file
php artisan test tests/Feature/AdminResourceCustomViewTest.php

# Run with coverage
php artisan test --coverage
```

---

## 📁 Project Structure

```
al-jabali-co/
├── app/
│   ├── Enums/                    # PHP 8.3 backed enums
│   ├── Filament/
│   │   ├── Resources/            # CRUD resources
│   │   ├── Pages/                # Custom pages (dashboards)
│   │   └── Widgets/              # Dashboard widgets
│   ├── Http/
│   │   ├── Controllers/          # PDF & export controllers
│   │   └── Middleware/           # Locale middleware
│   ├── Models/                   # Eloquent models
│   ├── Notifications/            # Email notifications
│   ├── Observers/                # Model observers
│   ├── Policies/                 # Authorization policies
│   └── Services/                 # Business logic services
├── database/
│   ├── migrations/               # Database migrations
│   ├── factories/                # Model factories
│   └── seeders/                  # Database seeders
├── resources/
│   └── views/
│       ├── pdf/                  # PDF templates
│       └── filament/             # Custom Filament views
├── routes/
│   └── web.php                   # Custom routes
└── tests/
    ├── Browser/                  # Dusk browser tests
    └── Feature/                  # Feature tests
```

---

## 🔐 Roles & Permissions

### 6 User Roles

| Role | Key | Permissions |
|------|-----|-------------|
| Field Engineer | `engineer` | Create visits, submit sales requests |
| Warehouse Keeper | `warehouse_keeper` | Stage 1 approval, return requests |
| Sales Manager | `sales_manager` | Stage 2 approval, view team visits |
| Purchasing Manager | `purchasing_manager` | Stage 3 approval |
| Financial Manager | `financial_manager` | Stage 4 approval |
| General Manager | `general_manager` | View all, reports, final HR approval |

Permissions are managed via **Filament Shield** (`/shield/roles`).

---

## 🌐 Localization

The system supports **Arabic** (primary) and **English**.

- Users can switch language via the locale switcher in the admin panel
- Preference is stored in `users.locale`
- RTL/LTR is handled automatically based on locale
- Translation files are in `lang/ar/` and `lang/en/`

---

## 📄 PDF Export

Approved sales requests can be exported as professionally formatted PDFs:

- **Access:** Available for approved requests or users with viewing permissions
- **Route:** `/sales-requests/{request}/pdf`
- **Content:** Request details, items, approval stages, and engineer notes
- **Format:** A4 portrait, Arabic (RTL) layout

---

## ✅ Form Validation

All forms include inline validation:

| Form | Validation Rules |
|------|------------------|
| **Sales Request Items** | Quantity > 0, Unit Price > 0, Required fields |
| **HR Requests** | Start date ≥ today, End date ≥ start date, Duration ≥ 1 |
| **Departure Requests** | End time > start time |
| **Daily Visits** | Valid phone format, GPS coordinates in range, Min text lengths |
| **File Uploads** | Accepted file types, Max file size |

---

## 🔧 Development

```bash
# Start development environment
composer run dev

# This runs concurrently:
# - PHP server
# - Queue worker
# - Pail logs
# - Vite (hot reload)
```

### Code Quality

```bash
# Format code with Pint
vendor/bin/pint

# Run static analysis (if configured)
vendor/bin/phpstan
```

---

## 📊 Features Roadmap

### Completed ✅
- [x] User management with roles
- [x] Sales approval workflow (5 stages)
- [x] HR request workflow (2 stages)
- [x] Daily visit tracking
- [x] Product catalog
- [x] PDF export for sales requests
- [x] Form validation
- [x] Bilingual support (AR/EN)
- [x] Audit logging
- [x] Leave balance tracking
- [x] Excel export

### Planned 📋
- [ ] WhatsApp notifications
- [ ] Scheduled report generation
- [ ] Mobile-responsive improvements
- [ ] Team calendar view
- [ ] Conflict detection for leaves
- [ ] Bulk approval actions
- [ ] Dashboard charts & analytics
- [ ] API endpoints for future integrations

---

## 🐛 Troubleshooting

**Permission denied errors:**
```bash
php artisan shield:generate --all
php artisan cache:clear
php artisan config:clear
```

**Database issues:**
```bash
php artisan migrate:status
php artisan db:show
```

**Build failures:**
```bash
rm -rf node_modules vendor
composer install
npm install
npm run build
```

---

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

<div align="center">

**Al-Jabali Agricultural Company** • Management System • 2026

</div>
# erp-jabalyagri
