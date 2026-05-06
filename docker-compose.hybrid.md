version: "3.9"

services:
  # MySQL Database - Docker
  db:
    image: mysql:8.0
    container_name: ieepis-mysql-hybrid
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: ieepis_db
      MYSQL_USER: ieepis_user
      MYSQL_PASSWORD: ieepis_password
      MYSQL_INITDB_SKIP_TZINFO: 1
    ports:
      - "3306:3306"
    volumes:
      - db-data-hybrid:/var/lib/mysql
    networks:
      - ieepis-hybrid-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
    command: --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci

  # Redis Cache & Session & Queue - Docker
  redis:
    image: redis:7-alpine
    container_name: ieepis-redis-hybrid
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis-data-hybrid:/data
    networks:
      - ieepis-hybrid-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Mailpit for local email testing
  mail:
    image: axllent/mailpit:latest
    container_name: ieepis-mail-hybrid
    restart: unless-stopped
    ports:
      - "1025:1025" # SMTP
      - "8025:8025" # Web UI
    networks:
      - ieepis-hybrid-network

networks:
  ieepis-hybrid-network:
    driver: bridge

volumes:
  db-data-hybrid:
  redis-data-hybrid:
