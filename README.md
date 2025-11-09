# CommitPulse 🚀

**Spotify Wrapped for Developers** - Track your coding activity with beautiful weekly reports.

## Overview

CommitPulse fetches your GitHub/GitLab commits, aggregates the data, and provides weekly insights including:
- Total commits
- Lines added/removed
- Top languages
- Most active repositories
- Visual summaries with streaks and badges

## Architecture

```
commitpulse/
├── backend/          # Laravel API
├── frontend/         # Vue 3 Dashboard
├── worker/           # Golang microservice for async commit fetching
└── README.md
```

## Tech Stack

- **Frontend**: Vue 3 + TailwindCSS + Chart.js
- **Backend**: Laravel 11 (API, Auth, Jobs)
- **Worker**: Golang (async commit fetching)
- **Database**: MySQL/PostgreSQL
- **Email**: Laravel Mail

## Quick Start

### Prerequisites

- PHP 8.2+
- Node.js 18+
- Go 1.21+
- MySQL/PostgreSQL
- Composer
- npm/yarn

### Backend Setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

### Worker Setup

```bash
cd worker
go mod download
go run main.go
```

## Environment Variables

See `.env.example` files in each directory for required configuration.

## Features

- ✅ GitHub OAuth integration
- ✅ Commit tracking and aggregation
- ✅ Weekly statistics dashboard
- ✅ Email digest (weekly)
- ✅ Public profile pages
- 🔄 Background job processing
- 🔄 Golang worker for async fetching

## License

MIT

