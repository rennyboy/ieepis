# DCP Distribution Dashboard Instructions

## 📊 Overview
The **DCP Distribution Dashboard** is a specialized analytics panel within IEEPIS designed to track the distribution of DepEd Computerization Program (DCP) packages across school districts. It provides real-time insights into equipment counts, personnel assignments, and distribution status.

## 📍 Accessing the Dashboard
You can access the DCP Distribution Dashboard in two ways:
1.  **Sidebar Menu**: Navigate to the **Overview** group on the left sidebar and click on **DCP Distribution**.
2.  **User Profile (Top Right)**: Click on your user profile name in the top-right corner to open the dropdown menu. Select **DCP Distribution Summary**.

## 🛠️ Data Management & Seeding
The dashboard is powered by dynamic data from the database. To populate or reset the dummy data for development/testing:

### 1. Run the Seeder via Docker
Execute the following command in your terminal within the project directory:
```bash
docker compose exec -T app php artisan db:seed --class=DcpDistributionSeeder
```

### 2. Update Seed Data Logic
If you need to change the counts, roles, or types of equipment in the dummy data, modify the seeder file here:
[`database/seeders/DcpDistributionSeeder.php`](file:///home/rennyboy/Downloads/ieepis/database/seeders/DcpDistributionSeeder.php)

### 3. Clear Caches
Always run this command after making changes to the seeder or dashboard logic in a Docker environment:
```bash
docker compose exec -T app php artisan optimize:clear
```

## 🧠 Core Components
- **Stats Overview**: Shows high-level totals (L4T, L4NT, Smart TV, Population).
- **Distribution Chart**: Breakdown by equipment type (Laptop, Desktop, etc.).
- **Population Insight**: Comparison between total ICT inventory and district personnel population.
- **Percentages Summary**: A detailed table showing delivery efficiency and coverage by district.

## 📝 Dashboard Logic
The data calculation logic is centralized to follow the **DRY (Don't Repeat Yourself)** principle. If you need to modify how the charts calculate their numbers, edit:
[`app/Filament/Pages/DcpDistributionData.php`](file:///home/rennyboy/Downloads/ieepis/app/Filament/Pages/DcpDistributionData.php)
