version: "3.9"

services:
  # Laravel Web Application with Nginx + PHP-FPM
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: ieepis-app
    restart: unless-stopped
    working_dir: /var/www
    ports:
      - "8080:8080"
    environment:
      APP_NAME: IEEPIS
      APP_ENV: local
      APP_DEBUG: true
      APP_KEY: base64:HQ9m4lBf0yUVKoqOm2eZ7X1J3L8vKqPoN9Q4R2T5M6U=
      APP_URL: http://localhost:8080

      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: ieepis_db
      DB_USERNAME: ieepis_user
      DB_PASSWORD: ieepis_password

      CACHE_DRIVER: redis
      CACHE_HOST: redis
      CACHE_PORT: 6379

      SESSION_DRIVER: redis
      SESSION_HOST: redis

      QUEUE_CONNECTION: redis
      REDIS_HOST: redis
      REDIS_PORT: 6379

      MAIL_MAILER: log
      LOG_CHANNEL: single
      LOG_LEVEL: debug
    volumes:
      - .:/var/www
      - /var/www/vendor
      - /var/www/node_modules
    networks:
      - ieepis-network
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  # MySQL Database
  db:
    image: mysql:8.0
    container_name: ieepis-db
    restart: unless-stopped
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: ieepis_db
      MYSQL_USER: ieepis_user
      MYSQL_PASSWORD: ieepis_password
      MYSQL_INITDB_SKIP_TZINFO: 1
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - ieepis-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
    command: --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci

  # Redis Cache and Queue
  redis:
    image: redis:7-alpine
    container_name: ieepis-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - ieepis-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

networks:
  ieepis-network:
    driver: bridge

volumes:
  db-data:
    driver: local
  redis-data:
    driver: local
