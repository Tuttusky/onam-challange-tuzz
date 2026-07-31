# Testing Checklist

## Public Game Flow

- [ ] Home page loads featured campaign from API
- [ ] Enter name → start session → receive token
- [ ] Answer all 10 questions (yes/no, MCQ, emoji, text)
- [ ] Finalize → get challenge code
- [ ] Share page shows WhatsApp link
- [ ] Friend opens `/challenge/{code}`
- [ ] Friend joins with name → plays same questions
- [ ] Result page shows match %, badge, funny message
- [ ] Friend can start own challenge (viral loop)

## Admin Panel

- [ ] Login at `/admin/login`
- [ ] Dashboard stats load
- [ ] Create / edit / clone campaign
- [ ] Manage questions & reorder
- [ ] View players & export CSV
- [ ] Website / SEO / Analytics settings save
- [ ] CMS pages render on `/page/{slug}`
- [ ] Banners CRUD
- [ ] Leaderboard rebuild
- [ ] Activity & login logs visible
- [ ] Backup trigger works

## API

- [ ] `GET /api/v1/campaigns/active` returns 200
- [ ] `POST /api/v1/sessions/start` returns session + token
- [ ] Invalid `X-Player-Session` returns 403
- [ ] Maintenance mode returns 503 JSON

## Performance & SEO

- [ ] `npm run build` succeeds
- [ ] `/sitemap.xml` and `/robots.txt` accessible
- [ ] Mobile responsive (375px viewport)
- [ ] PWA manifest loads

## Security

- [ ] CSRF on admin forms
- [ ] XSS escaped in Blade views
- [ ] Rate limiting on API
- [ ] Admin routes require authentication

## Commands

```bash
php artisan test
php artisan route:list
npm run build
```
