#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}  IEEPIS Local Docker Setup${NC}"
echo -e "${BLUE}================================${NC}\n"

# Check if Docker is installed
echo -e "${YELLOW}[1/7] Checking Docker installation...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker is not installed${NC}"
    echo "Visit https://docs.docker.com/get-docker/ to install"
    exit 1
fi
echo -e "${GREEN}✓ Docker installed: $(docker --version)${NC}"

# Check if Docker Compose is installed
echo -e "${YELLOW}[2/7] Checking Docker Compose installation...${NC}"
if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}✗ Docker Compose is not installed${NC}"
    echo "Visit https://docs.docker.com/get-docker/ to install"
    exit 1
fi
echo -e "${GREEN}✓ Docker Compose installed: $(docker compose version | head -1)${NC}"

# Create .env if it doesn't exist
echo -e "${YELLOW}[3/7] Setting up environment file...${NC}"
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env

    # Update key values for Docker
    if [ -f .env ]; then
        sed -i.bak 's/^APP_ENV=.*/APP_ENV=local/' .env
        sed -i.bak 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env
        sed -i.bak 's/^DB_HOST=.*/DB_HOST=db/' .env
        sed -i.bak 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
        sed -i.bak 's/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/' .env
        sed -i.bak 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
        rm -f .env.bak
    fi
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Build Docker images
echo -e "${YELLOW}[4/7] Building Docker images...${NC}"
echo "This may take 2-5 minutes on first run..."
if docker compose -f docker-compose.local.yml build; then
    echo -e "${GREEN}✓ Docker images built successfully${NC}"
else
    echo -e "${RED}✗ Failed to build Docker images${NC}"
    exit 1
fi

# Start containers
echo -e "${YELLOW}[5/7] Starting Docker containers...${NC}"
if docker compose -f docker-compose.local.yml up -d; then
    echo -e "${GREEN}✓ Docker containers started${NC}"
else
    echo -e "${RED}✗ Failed to start Docker containers${NC}"
    exit 1
fi

# Wait for database to be ready
echo -e "${YELLOW}[6/7] Waiting for database to be ready...${NC}"
for i in {1..30}; do
    if docker compose -f docker-compose.local.yml exec -T db mysqladmin ping -h localhost -u root -proot &> /dev/null; then
        echo -e "${GREEN}✓ Database is ready${NC}"
        break
    fi
    if [ $i -eq 30 ]; then
        echo -e "${RED}✗ Database took too long to start${NC}"
        exit 1
    fi
    echo "  Waiting... ($i/30)"
    sleep 1
done

# Run migrations
echo -e "${YELLOW}[7/7] Running database migrations...${NC}"
if docker compose -f docker-compose.local.yml exec -T app php artisan migrate; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${YELLOW}⚠ Migrations warning (may already exist)${NC}"
fi

# Display completion message
echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}  Setup Complete! 🎉${NC}"
echo -e "${GREEN}================================${NC}\n"

echo -e "${BLUE}Your application is ready!${NC}\n"

echo "📍 Access your application:"
echo -e "   Web App: ${YELLOW}http://localhost:8080${NC}"
echo -e "   Admin:   ${YELLOW}http://localhost:8080/admin${NC}"
echo -e "   Health:  ${YELLOW}http://localhost:8080/health${NC}\n"

echo "📊 Database credentials:"
echo -e "   Host:     ${YELLOW}localhost:3306${NC}"
echo -e "   Database: ${YELLOW}ieepis_db${NC}"
echo -e "   User:     ${YELLOW}ieepis_user${NC}"
echo -e "   Password: ${YELLOW}ieepis_password${NC}\n"

echo "🔴 Redis credentials:"
echo -e "   Host: ${YELLOW}localhost:6379${NC}\n"

echo "📝 Useful commands:"
echo -e "   ${YELLOW}View logs:${NC}"
echo -e "     docker compose -f docker-compose.local.yml logs -f app"
echo ""
echo -e "   ${YELLOW}Run migrations:${NC}"
echo -e "     docker compose -f docker-compose.local.yml exec app php artisan migrate"
echo ""
echo -e "   ${YELLOW}Create test user:${NC}"
echo -e "     docker compose -f docker-compose.local.yml exec app php artisan tinker"
echo ""
echo -e "   ${YELLOW}Run tests:${NC}"
echo -e "     docker compose -f docker-compose.local.yml exec app php artisan test"
echo ""
echo -e "   ${YELLOW}Stop containers:${NC}"
echo -e "     docker compose -f docker-compose.local.yml down"
echo ""
echo -e "   ${YELLOW}Reset everything:${NC}"
echo -e "     docker compose -f docker-compose.local.yml down -v"
echo ""

echo -e "${BLUE}Next steps:${NC}"
echo "1. Open http://localhost:8080 in your browser"
echo "2. Create a test user in the admin panel"
echo "3. Test your application features"
echo "4. Read LOCAL_DOCKER_SETUP.md for more information"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"
