package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"time"

	"github.com/google/go-github/v56/github"
	"golang.org/x/oauth2"
)

type User struct {
	ID           uint      `json:"id"`
	GithubID     string    `json:"github_id"`
	GithubToken  string    `json:"github_token"`
	LastSyncedAt *string   `json:"last_synced_at"`
}

type SyncRequest struct {
	UserID      uint                   `json:"user_id"`
	Repositories []RepositoryData      `json:"repositories"`
	Commits     []CommitData           `json:"commits"`
}

type RepositoryData struct {
	Name     string `json:"name"`
	URL      string `json:"url"`
	Language string `json:"language"`
}

type CommitData struct {
	RepoName  string `json:"repo_name"`
	Date      string `json:"date"`
	Message   string `json:"message"`
	Additions int    `json:"additions"`
	Deletions int    `json:"deletions"`
}

var (
	apiBaseURL  string
	workerAPIKey string
)

func main() {
	// Load environment variables
	apiBaseURL = os.Getenv("API_BASE_URL")
	if apiBaseURL == "" {
		apiBaseURL = "http://localhost:8000"
	}

	workerAPIKey = os.Getenv("WORKER_API_KEY")
	if workerAPIKey == "" {
		log.Fatal("WORKER_API_KEY environment variable is required")
	}

	log.Println("Worker started successfully")

	// Run sync job immediately, then schedule it
	syncAllUsers()

	// Schedule daily sync
	ticker := time.NewTicker(24 * time.Hour)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			syncAllUsers()
		}
	}
}

func syncAllUsers() {
	log.Println("Starting sync for all users...")

	// Fetch users from Laravel API (with decrypted tokens)
	users, err := fetchUsersFromAPI()
	if err != nil {
		log.Printf("Error fetching users from API: %v", err)
		return
	}

	log.Printf("Found %d users to sync", len(users))

	for _, user := range users {
		if err := syncUserCommits(user); err != nil {
			log.Printf("Error syncing user %d: %v", user.ID, err)
			continue
		}
		time.Sleep(2 * time.Second) // Rate limiting
	}

	log.Println("Sync completed")
}

func fetchUsersFromAPI() ([]User, error) {
	req, err := http.NewRequest("GET", apiBaseURL+"/api/worker/users", nil)
	if err != nil {
		return nil, err
	}

	req.Header.Set("X-Worker-API-Key", workerAPIKey)

	client := &http.Client{Timeout: 30 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("API returned status %d", resp.StatusCode)
	}

	var users []User
	if err := json.NewDecoder(resp.Body).Decode(&users); err != nil {
		return nil, err
	}

	return users, nil
}

func syncUserCommits(user User) error {
	log.Printf("Syncing commits for user %d", user.ID)

	ctx := context.Background()
	ts := oauth2.StaticTokenSource(
		&oauth2.Token{AccessToken: user.GithubToken},
	)
	tc := oauth2.NewClient(ctx, ts)
	client := github.NewClient(tc)

	// Get authenticated user to fetch their own repos
	githubUser, _, err := client.Users.Get(ctx, "")
	if err != nil {
		return fmt.Errorf("failed to get GitHub user: %w", err)
	}

	username := githubUser.GetLogin()

	// Get user's repositories
	repos, _, err := client.Repositories.List(ctx, username, &github.RepositoryListOptions{
		Type:        "all",
		Sort:        "updated",
		Direction:   "desc",
		ListOptions: github.ListOptions{PerPage: 100},
	})
	if err != nil {
		return fmt.Errorf("failed to list repositories: %w", err)
	}

	// Calculate date range (last 7 days)
	weekAgo := time.Now().AddDate(0, 0, -7)

	var syncRequest SyncRequest
	syncRequest.UserID = user.ID

	// Process each repository
	for _, repo := range repos {
		if repo.GetArchived() {
			continue
		}

		repoData := RepositoryData{
			Name:     repo.GetFullName(),
			URL:      repo.GetHTMLURL(),
			Language: repo.GetLanguage(),
		}
		syncRequest.Repositories = append(syncRequest.Repositories, repoData)

		// Get commits for the last 7 days
		commits, _, err := client.Repositories.ListCommits(ctx, repo.GetOwner().GetLogin(), repo.GetName(), &github.CommitsListOptions{
			Author: username,
			Since:  weekAgo,
			ListOptions: github.ListOptions{PerPage: 100},
		})
		if err != nil {
			log.Printf("Error fetching commits for %s: %v", repo.GetFullName(), err)
			continue
		}

		// Process commits
		for _, commit := range commits {
			commitDate := commit.GetCommit().GetAuthor().GetDate()
			if commitDate.Before(weekAgo) {
				continue
			}

			// Get commit stats
			commitDetail, _, err := client.Repositories.GetCommit(ctx, repo.GetOwner().GetLogin(), repo.GetName(), commit.GetSHA(), false)
			if err != nil {
				log.Printf("Error fetching commit details: %v", err)
				continue
			}

			stats := commitDetail.GetStats()
			commitData := CommitData{
				RepoName:  repo.GetFullName(),
				Date:      commitDate.Format("2006-01-02"),
				Message:   commit.GetCommit().GetMessage(),
				Additions: stats.GetAdditions(),
				Deletions: stats.GetDeletions(),
			}
			syncRequest.Commits = append(syncRequest.Commits, commitData)
		}

		time.Sleep(500 * time.Millisecond) // Rate limiting
	}

	// Send data to Laravel API
	if err := sendSyncRequest(syncRequest); err != nil {
		return fmt.Errorf("failed to send sync request: %w", err)
	}

	log.Printf("Successfully synced %d commits for user %d", len(syncRequest.Commits), user.ID)
	return nil
}

func sendSyncRequest(syncReq SyncRequest) error {
	jsonData, err := json.Marshal(syncReq)
	if err != nil {
		return err
	}

	req, err := http.NewRequest("POST", apiBaseURL+"/api/commits/sync", jsonData)
	if err != nil {
		return err
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-Worker-API-Key", workerAPIKey)

	client := &http.Client{Timeout: 30 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("API returned status %d", resp.StatusCode)
	}

	return nil
}

