# CommitPulse Worker

Golang microservice that fetches commits from GitHub asynchronously.

## Setup

1. Install dependencies:
```bash
go mod download
```

2. Configure environment variables (see `.env.example`)

3. Run:
```bash
go run main.go
```

The worker will:
- Fetch all users with GitHub tokens from Laravel API (with decrypted tokens)
- Fetch commits for the last 7 days from GitHub
- Send commit data to Laravel API endpoint

## Environment Variables

- `API_BASE_URL`: Laravel API base URL (default: http://localhost:8000)
- `WORKER_API_KEY`: API key for authenticating with Laravel (must match backend config)

## How It Works

1. Worker calls `/api/worker/users` to get list of users with decrypted GitHub tokens
2. For each user, fetches their repositories and commits from GitHub API
3. Sends aggregated data to `/api/commits/sync` endpoint
4. Laravel stores the data in the database

This approach avoids the need for the worker to have database access or Laravel's encryption keys.

