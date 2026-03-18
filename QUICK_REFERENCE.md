# IEEPIS Quick Reference Card

## 🔐 Test User Credentials

### Super Admin (Full System Access)
- Email: `admin@deped.gov.ph`
- Password: `P@ssw0rd123`
- Access: Everything

### SDO Admin (All Schools)
- Email: `admin.sdo@deped.gov.ph`
- Password: `P@ssw0rd123`
- Access: All 4 schools' data

### School Admins (School-Specific)
```
DCNHS:  admin.dcnhs@deped.gov.ph / P@ssw0rd123
MNHS:   admin.mnhs@deped.gov.ph / P@ssw0rd123
TDSS:   admin.tdss@deped.gov.ph / P@ssw0rd123
PES:    admin.pes@deped.gov.ph / P@ssw0rd123
```

### Technicians (Support Staff)
```
DCNHS:  tech.dcnhs@deped.gov.ph / P@ssw0rd123
MNHS:   tech.mnhs@deped.gov.ph / P@ssw0rd123
TDSS:   tech.tdss@deped.gov.ph / P@ssw0rd123
PES:    tech.pes@deped.gov.ph / P@ssw0rd123
```

---

## 🚀 Common Commands

### Create Test Users
```bash
docker exec ieepis-app php artisan test:create-users
```

### Build Frontend Assets
```bash
npm run build
```

### Development Mode (Watch)
```bash
npm run dev
```

### Clear All Caches
```bash
docker exec ieepis-app php artisan cache:clear
docker exec ieepis-app php artisan view:clear
docker exec ieepis-app php artisan config:clear
```

### Access Tinker Console
```bash
docker exec -it ieepis-app php artisan tinker
```

### Run Database Migrations
```bash
docker exec ieepis-app php artisan migrate
```

---

## 🌐 Access Points

### Main Application
- URL: `http://localhost:8080/admin`
- Login required
- Use credentials above

### Database
- Host: `localhost`
- Port: `3307`
- Database: `ieepis_db`
- User: `ieepis_user`
- Password: `ieepis_password`

### Redis Cache
- Host: `localhost`
- Port: `6380`

---

## 📂 Project Structure

```
ieepis/
├── app/
│   ├── Console/Commands/
│   │   └── CreateTestUsers.php ← User creation
│   ├── Models/
│   │   ├── User.php
│   │   ├── School.php
│   │   └── ...
│   ├── Filament/
│   │   ├── Resources/
│   │   ├── Pages/
│   │   └── Widgets/
│   └── Providers/
│       └── Filament/AdminPanelProvider.php ← Layout config
├── resources/
│   ├── css/
│   │   ├── app.css
│   │   └── filament/admin/custom.css ← Custom styles
│   ├── js/
│   └── views/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   └── build/ ← Compiled assets
├── vite.config.js ← Build configuration
└── docker-compose.yml
```

---

## 👥 Roles Overview

| Role | Access | Use Case |
|------|--------|----------|
| super-admin | System-wide | System administrator |
| sdo-admin | All schools | Division/SDO manager |
| school-admin | One school only | School principal/admin |
| technician | One school (limited) | IT support staff |

---

## 🎨 Layout Features

✅ Vertical sidebar on left
✅ Full-width content area
✅ Minimal top bar
✅ Collapsible sidebar on desktop
✅ Hamburger menu on mobile
✅ Dark mode support
✅ Responsive design

---

## 🧪 Quick Testing

### Test Permission Isolation
1. Login as `admin.dcnhs@deped.gov.ph`
2. Verify: Only see DCNHS data
3. Logout, login as `admin.mnhs@deped.gov.ph`
4. Verify: Only see MNHS data (not DCNHS)

### Test SDO Access
1. Login as `admin.sdo@deped.gov.ph`
2. Verify: Can see all 4 schools

### Test Super Admin
1. Login as `admin@deped.gov.ph`
2. Verify: Can access user management

### Test Responsive Layout
1. Resize browser to different widths
2. Desktop (>1024px): Sidebar visible, collapsible
3. Mobile (<768px): Hamburger menu visible

---

## 🔧 Troubleshooting Quick Fixes

| Problem | Solution |
|---------|----------|
| Users not showing | `php artisan test:create-users` |
| CSS not loading | `npm run build && php artisan view:clear` |
| Login fails | Check database: `User::all()` in tinker |
| Sidebar not visible | Clear config: `php artisan config:clear` |
| Cache issues | `php artisan cache:clear` |

---

## 📊 Database Quick Queries (Tinker)

```php
# View all users with roles
User::with('roles')->get()->map(fn($u) => [
    'email' => $u->email,
    'role' => $u->getRoleNames()->first(),
    'school' => $u->school?->name
])

# Find user by email
User::where('email', 'admin.dcnhs@deped.gov.ph')->first()

# Check user roles
$user = User::find(1);
$user->hasRole('school-admin')
$user->getRoleNames()

# Get all schools
School::all()->map(fn($s) => ['id' => $s->id, 'name' => $s->name])
```

---

## 🎯 Key Files

| File | Purpose |
|------|---------|
| `app/Console/Commands/CreateTestUsers.php` | Create test users |
| `app/Providers/Filament/AdminPanelProvider.php` | Filament configuration |
| `resources/css/filament/admin/custom.css` | Custom layout styles |
| `vite.config.js` | Frontend build config |
| `TASK_COMPLETION_GUIDE.md` | Detailed documentation |

---

## 📝 Environment Variables (docker-compose.yml)

```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ieepis_db
DB_USER=ieepis_user
DB_PASSWORD=ieepis_password

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## ✅ Verification Checklist

- [ ] Can login with `admin@deped.gov.ph`
- [ ] Sidebar appears on left after login
- [ ] Content area is full width
- [ ] Can logout and login as different user
- [ ] School admin only sees their school
- [ ] SDO admin sees all schools
- [ ] Mobile view shows hamburger menu
- [ ] Responsive layout works on all sizes

---

## 🔗 Useful Links

- Filament Docs: https://filamentphp.com
- Laravel Docs: https://laravel.com/docs
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Tailwind CSS: https://tailwindcss.com

---

## 📞 Quick Support

**Asset Build Issue?**
```bash
npm run build
php artisan cache:clear
```

**User Issue?**
```bash
php artisan test:create-users
php artisan tinker
# Then: User::all()
```

**Layout Issue?**
```bash
php artisan view:clear
php artisan config:clear
npm run build
```

---

**Last Updated**: 2024
**Status**: Ready to use ✅