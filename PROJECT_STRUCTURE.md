# CommitPulse Project Structure

## Directory Overview

```
commitpulse/
├── backend/                 # Laravel 11 API
│   ├── app/
│   │   ├── Console/
│   │   │   └── Kernel.php          # Scheduled jobs (daily sync, weekly emails)
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── API/
│   │   │   │   │   ├── CommitsController.php    # Worker sync endpoint
│   │   │   │   │   ├── PublicProfileController.php
│   │   │   │   │   ├── StatsController.php      # Dashboard stats
│   │   │   │   │   └── WorkerController.php     # Worker user list
│   │   │   │   └── Auth/
│   │   │   │       └── GithubController.php     # OAuth flow
│   │   │   └── Middleware/
│   │   ├── Jobs/
│   │   │   ├── FetchCommitsJob.php
│   │   │   ├── GenerateWeeklyStatsJob.php
│   │   │   └── SendWeeklyDigestEmail.php
│   │   └── Models/
│   │       ├── User.php
│   │       ├── Repository.php
│   │       ├── Commit.php
│   │       └── WeeklyStat.php
│   ├── database/
│   │   └── migrations/              # Database schema
│   ├── resources/
│   │   └── views/
│   │       ├── emails/
│   │       │   └── weekly-digest.blade.php
│   │       └── welcome.blade.php
│   └── routes/
│       ├── api.php                  # API routes
│       └── web.php                  # Web routes
│
├── frontend/                # Vue 3 SPA
│   ├── src/
│   │   ├── components/
│   │   │   ├── LanguagesChart.vue
│   │   │   ├── StatCard.vue
│   │   │   └── WeeklyChart.vue
│   │   ├── pages/
│   │   │   ├── Dashboard.vue        # Main dashboard
│   │   │   ├── Login.vue
│   │   │   ├── PublicProfile.vue
│   │   │   └── Settings.vue
│   │   ├── App.vue
│   │   └── main.js
│   └── package.json
│
├── worker/                  # Golang microservice
│   ├── main.go              # Worker logic
│   └── go.mod
│
└── docker-compose.yml       # MySQL container for local dev
```

## Key Components

### Backend (Laravel)

**Authentication Flow:**
1. User clicks "Connect with GitHub" → `/auth/github`
2. GitHub OAuth callback → `/auth/github/callback`
3. Store encrypted GitHub token
4. Redirect to dashboard

**API Endpoints:**
- `GET /api/stats/current-week` - Dashboard statistics
- `POST /api/sync` - Manual sync trigger
- `GET /api/public/{username}` - Public profile data
- `GET /api/worker/users` - Worker endpoint (API key protected)
- `POST /api/commits/sync` - Worker sync endpoint (API key protected)

**Scheduled Jobs:**
- Daily: Fetch commits for all users
- Weekly (Monday): Generate weekly stats
- Weekly (Sunday): Send email digests

### Frontend (Vue 3)

**Pages:**
- `/` - Login page with GitHub OAuth
- `/dashboard` - Main dashboard with stats and charts
- `/settings` - User settings
- `/u/:username` - Public profile view

**Features:**
- Real-time stats display
- Chart.js integration for visualizations
- Responsive TailwindCSS design
- API integration with Laravel backend

### Worker (Golang)

**Flow:**
1. Fetch users from Laravel API (`/api/worker/users`)
2. For each user:
   - Get repositories from GitHub
   - Fetch commits for last 7 days
   - Aggregate data
3. Send to Laravel (`/api/commits/sync`)

**Benefits:**
- Async processing (doesn't block Laravel)
- Rate limiting built-in
- Can scale independently

## Data Flow

```
User → GitHub OAuth → Laravel (store token)
                    ↓
Worker (daily) → GitHub API → Fetch commits → Laravel API → Database
                    ↓
Laravel Scheduler → Generate weekly stats → Database
                    ↓
Laravel Scheduler → Send email digest → User
                    ↓
Frontend → Laravel API → Display stats
```

## Database Schema

- **users**: GitHub OAuth data, encrypted tokens
- **repositories**: User's GitHub repos
- **commits**: Individual commit records
- **weekly_stats**: Aggregated weekly statistics

## Security

- GitHub tokens encrypted in database (Laravel Crypt)
- Worker API key authentication
- CORS configured for frontend
- Sanctum for API authentication

## Next Steps for Production

1. Set up proper environment variables
2. Configure queue workers (Redis/database)
3. Set up email service (Mailgun/Postmark)
4. Deploy components:
   - Backend: Laravel Forge / DigitalOcean
   - Frontend: Vercel / Netlify
   - Worker: Railway / Fly.io
   - Database: PlanetScale / Supabase
5. Set up monitoring and logging
6. Configure CI/CD pipelines

