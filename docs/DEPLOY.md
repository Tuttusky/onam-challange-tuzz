# Deployment Guide

## Production Checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false`
2. Configure MySQL with proper credentials
3. Set `APP_URL` to your domain
4. Run `php artisan migrate --force`
5. Run `php artisan config:cache route:cache view:cache`
6. Run `npm ci && npm run build`
7. Set up queue worker (Supervisor)
8. Set up scheduler cron
9. Configure HTTPS (Let's Encrypt)
10. Set file permissions: `storage/` and `bootstrap/cache/` writable

## Supervisor Queue Config

```ini
[program:onam-dare-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/onam-dare/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/onam-dare/storage/logs/worker.log
```

## Nginx Example

```nginx
server {
    listen 80;
    server_name onamdare.example.com;
    root /var/www/onam-dare/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Environment Variables

| Variable | Description |
|----------|-------------|
| `APP_URL` | Public site URL |
| `DB_*` | MySQL connection |
| `QUEUE_CONNECTION` | `database` or `redis` |
| `CACHE_STORE` | `database` or `redis` |
| `FILESYSTEM_DISK` | `local` or `s3` |

## Backups

Trigger from Admin → Backups or schedule `DatabaseBackupJob`.
