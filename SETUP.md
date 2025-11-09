# CommitPulse Setup Guide

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- Go 1.21+
- MySQL/PostgreSQL
- GitHub OAuth App (for authentication)

## Step 1: GitHub OAuth Setup

1. Go to GitHub Settings → Developer settings → OAuth Apps
2. Create a new OAuth App:
   - Application name: CommitPulse
   - Homepage URL: `http://localhost:8000`
   - Authorization callback URL: `http://localhost:8000/auth/github/callback`
3. Copy the Client ID and Client Secret

## Step 2: Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE commitpulse;
exit;
```

## Step 3: Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your settings:
# - DB credentials
# - GITHUB_CLIENT_ID
# - GITHUB_CLIENT_SECRET
# - WORKER_API_KEY (generate a random string)

# Run migrations
php artisan migrate

# Start the server
php artisan serve
```

The backend will run on `http://localhost:8000`

## Step 4: Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

The frontend will run on `http://localhost:3000`

## Step 5: Worker Setup

```bash
cd worker

# Install Go dependencies
go mod download

# Create .env file (or set environment variables)
# DB_DSN=root:password@tcp(localhost:3306)/commitpulse?charset=utf8mb4&parseTime=True&loc=Local
# API_BASE_URL=http://localhost:8000
# WORKER_API_KEY=your_worker_api_key_here (same as in backend .env)

# Run the worker
go run main.go
```

## Step 6: Configure Laravel Scheduler (Optional)

For production, set up a cron job:

```bash
* * * * * cd /path-to-project/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

1. Visit `http://localhost:3000`
2. Click "Connect with GitHub"
3. Authorize the application
4. You'll be redirected to the dashboard
5. The worker will fetch your commits automatically

## Troubleshooting

### GitHub OAuth not working
- Check that callback URL matches exactly
- Verify Client ID and Secret in `.env`

### Worker not syncing
- Check database connection
- Verify `WORKER_API_KEY` matches in both backend and worker
- Check worker logs for errors

### Frontend can't connect to API
- Ensure backend is running on port 8000
- Check CORS settings in `backend/config/cors.php`
- Verify API proxy in `frontend/vite.config.js`

## Production Deployment

### Backend (Laravel)
- Use Laravel Forge, DigitalOcean App Platform, or similar
- Set up proper environment variables
- Configure queue workers for background jobs
- Set up Laravel scheduler

### Frontend (Vue)
- Build: `npm run build`
- Deploy to Vercel, Netlify, or similar
- Update API base URL in production

### Worker (Golang)
- Build: `go build -o worker main.go`
- Deploy to Railway, Fly.io, or similar
- Set up as a service/daemon
- Configure environment variables

### Database
- Use managed database service (PlanetScale, Supabase, etc.)
- Set up proper backups
- Configure connection pooling

