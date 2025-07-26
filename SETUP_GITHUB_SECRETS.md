# GitHub Secrets Setup for Auto-Deployment

To enable automatic deployment to Cloudways when PRs are merged, add these secrets to your GitHub repository:

## Steps:
1. Go to https://github.com/schroederryanj/schroeder247-admin/settings/secrets/actions
2. Click "New repository secret"
3. Add each of these secrets:

### Required Secrets:

**CLOUDWAYS_EMAIL**
```
tech@vhdental.com
```

**CLOUDWAYS_API_KEY**
```
Cm5jQnspi9WYcYNVEGM6ZIRsj1zbVJ
```

**CLOUDWAYS_SERVER_ID**
```
1494122
```

**CLOUDWAYS_APP_ID**
```
5724401
```

## How it works:
- When code is pushed to the `main` branch (or PR merged)
- GitHub Action automatically triggers
- Authenticates with Cloudways API
- Pulls latest code on your server
- Clears Laravel caches
- Restarts queue workers

## Manual Deployment:
You can also trigger deployment manually:
1. Go to Actions tab in GitHub
2. Select "Deploy to Cloudways"
3. Click "Run workflow"

## Local Testing:
To test the deployment script locally:
```bash
php cloudways-deploy.php
```