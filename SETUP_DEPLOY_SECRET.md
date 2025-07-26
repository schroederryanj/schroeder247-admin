# Deploy Secret Setup

To enable automatic cache clearing after deployments, you need to add a deploy secret to GitHub.

## Steps:

1. **Generate a secure secret** (or use this example):
   ```
   deploy-secret-2025-schroeder247
   ```

2. **Add to your .env file on production**:
   ```
   DEPLOY_SECRET=deploy-secret-2025-schroeder247
   ```

3. **Add to GitHub Secrets**:
   - Go to: https://github.com/schroederryanj/schroeder247-admin/settings/secrets/actions
   - Click "New repository secret"
   - Name: `DEPLOY_SECRET`
   - Value: `deploy-secret-2025-schroeder247`
   - Click "Add secret"

## How it works:

1. GitHub Actions pulls latest code via Cloudways API
2. Waits 15 seconds for files to update
3. Calls `/deploy` webhook with secret
4. Laravel clears and rebuilds all caches:
   - Route cache (fixes your 404 issue!)
   - Config cache
   - View cache
   - Runs migrations
   - Restarts queue workers

## Testing the webhook:

You can manually test the deployment webhook:
```bash
curl -X POST https://admin.schroeder247.com/deploy \
  -H "Content-Type: application/json" \
  -d '{"secret": "your-deploy-secret-here"}'
```

## Security:

The deployment endpoint is protected by:
- Secret validation
- POST-only access
- Logging of unauthorized attempts
- No sensitive data exposed