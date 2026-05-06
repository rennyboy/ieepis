# Production-optimized Docker Compose stack for IEEPIS
# Build:  docker compose -f docker-compose.prod.yml build
# Run:    docker compose -f docker-compose.prod.yml up -d
# Tail:   docker compose -f docker-compose.prod.yml logs -f app

x-app-env: &app-env
  APP_NAME: ${APP_NAME:-IEEPIS}
  APP_ENV: production
  APP_DEBUG: "false"
  APP_KEY: ${APP_KEY}
  APP_URL: ${APP_URL}
  LOG_CHANNEL: ${LOG_CHANNEL:-stderr}
  LOG_LEVEL: ${LOG_LEVEL:-warning}
  DB_CONNECTION: pgsql
  DB_HOST: db
  DB_PORT: 5432
  DB_DATABASE: ${DB_DATABASE:-ieepis_db}
  DB_USERNAME: ${DB_USERNAME:-ieepis_user}
  DB_PASSWORD: ${DB_PASSWORD:-ieepis_password}
  CACHE_STORE: redis
  REDIS_HOST: redis
  REDIS_PORT: 6379
  SESSION_DRIVER: redis
  SESSION_LIFETIME: ${SESSION_LIFETIME:-120}
  SESSION_SECURE_COOKIE: ${SESSION_SECURE_COOKIE:-true}
  SESSION_SAME_SITE: ${SESSION_SAME_SITE:-lax}
  QUEUE_CONNECTION: redis
  FILESYSTEM_DISK: ${FILESYSTEM_DISK:-public}
  MAIL_MAILER: ${MAIL_MAILER:-log}
  BROADCAST_DRIVER: ${BROADCAST_DRIVER:-log}

x-app-build: &app-build
  build:
    context: .
    dockerfile: Dockerfile
    target: app

x-app-volumes: &app-volumes
  - app-storage:/var/www/storage
  - app-bootstrap-cache:/var/www/bootstrap/cache

# json-file driver with rotation — keeps each container's logs bounded.
x-logging: &default-logging
  driver: json-file
  options:
    max-size: "10m"
    max-file: "5"

services:
  nginx:
    build:
      context: .
      dockerfile: Dockerfile
      target: web
    image: ieepis/web:latest
    container_name: ieepis-nginx
    restart: unless-stopped
    ports:
      - "${NGINX_PORT:-8080}:80"
    volumes:
      - app-storage:/var/www/storage:ro
    networks:
      - ieepis-network
    depends_on:
      app:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-fsS", "http://localhost/up", "-o", "/dev/null"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 20s
    logging: *default-logging
    deploy:
      resources:
        limits:
          memory: ${NGINX_MEM_LIMIT:-128M}

  app:
    <<: *app-build
    image: ieepis/app:latest
    container_name: ieepis-app
    restart: unless-stopped
    working_dir: /var/www
    init: true
    environment:
      <<: *app-env
    volumes: *app-volumes
    networks:
      - ieepis-network
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "cgi-fcgi", "-bind", "-connect", "127.0.0.1:9000"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 30s
    logging: *default-logging
    deploy:
      resources:
        limits:
          memory: ${APP_MEM_LIMIT:-768M}

  worker:
    image: ieepis/app:latest
    container_name: ieepis-worker
    restart: unless-stopped
    working_dir: /var/www
    init: true
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --max-jobs=1000 --backoff=10
    environment:
      <<: *app-env
      CONTAINER_ROLE: worker
    volumes: *app-volumes
    networks:
      - ieepis-network
    depends_on:
      app:
        condition: service_healthy
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f 'queue:work' >/dev/null || exit 1"]
      interval: 30s
      timeout: 5s
      retries: 3
    logging: *default-logging
    deploy:
      resources:
        limits:
          memory: ${WORKER_MEM_LIMIT:-512M}

  scheduler:
    image: ieepis/app:latest
    container_name: ieepis-scheduler
    restart: unless-stopped
    working_dir: /var/www
    init: true
    command: sh -c "while true; do php artisan schedule:run --no-interaction --verbose --no-ansi; sleep 60; done"
    environment:
      <<: *app-env
      CONTAINER_ROLE: scheduler
    volumes: *app-volumes
    networks:
      - ieepis-network
    depends_on:
      app:
        condition: service_healthy
    logging: *default-logging
    deploy:
      resources:
        limits:
          memory: ${SCHEDULER_MEM_LIMIT:-256M}

  db:
    image: postgres:16-alpine
    container_name: ieepis-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE:-ieepis_db}
      POSTGRES_USER: ${DB_USERNAME:-ieepis_user}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-ieepis_password}
      POSTGRES_INITDB_ARGS: "--encoding=UTF-8 --lc-collate=C --lc-ctype=C"
    volumes:
      - db-data:/var/lib/postgresql/data
    networks:
      - ieepis-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-ieepis_user} -d ${DB_DATABASE:-ieepis_db}"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 10s
    logging: *default-logging
    deploy:
      resources:
        limits:
          memory: ${DB_MEM_LIMIT:-1G}

  redis:
    image: redis:7-alpine
    container_name: ieepis-redis
    restart: unless-stopped
    command: >
      redis-server
      --appendonly yes
      --maxmemory ${REDIS_MAXMEMORY:-256mb}
      --maxmemory-policy allkeys-lru
    networks:
      - ieepis-network
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    logging: *default-logging
    deploy:
      resources:
        limits:
          memory: ${REDIS_MEM_LIMIT:-320M}

networks:
  ieepis-network:
    driver: bridge

volumes:
  db-data:
    driver: local
  redis-data:
    driver: local
  app-storage:
    driver: local
  app-bootstrap-cache:
    driver: local
