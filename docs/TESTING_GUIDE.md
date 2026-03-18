# IEEPIS Complete Testing Guide

## 🚀 Quick Start

**Access the application:**
```
URL: http://localhost:8080/admin
```

---

## 👤 Test User Accounts

### 1. Super Admin (System-Wide Access)
```
Email: admin@deped.gov.ph
Password: P@ssw0rd123
Role: super-admin
School: SYSTEM (sees all)
Permissions: Full system access
```

### 2. SDO Admin (All Schools)
```
Email: admin.sdo@deped.gov.ph
Password: P@ssw0rd123
Role: sdo-admin
School: SYSTEM (sees all)
Permissions: Manage all schools' data
```

### 3. School Admins (One Per School)

**School 1 - Davao City National High School (DCNHS)**
```
Email: admin.dcnhs@deped.gov.ph
Password: P@ssw0rd123
Role: school-admin
School: Davao City National High School
Permissions: View/edit only DCNHS data
```

**School 2 - Mintal National High School (MNHS)**
```
Email: admin.mnhs@deped.gov.ph
Password: P@ssw0rd123
Role: school-admin
School: Mintal National High School
Permissions: View/edit only MNHS data
```

**School 3 - Tugbok District Science School (TDSS)**
```
Email: admin.tdss@deped.gov.ph
Password: P@ssw0rd123
Role: school-admin
School: Tugbok District Science School
Permissions: View/edit only TDSS data
```

**School 4 - Paquibato Elementary School (PES)**
```
Email: admin.pes@deped.gov.ph
Password: P@ssw0rd123
Role: school-admin
School: Paquibato Elementary School
Permissions: View/edit only PES data
```

### 4. Technicians (One Per School)

```
tech.dcnhs@deped.gov.ph (DCNHS) / P@ssw0rd123
tech.mnhs@deped.gov.ph (MNHS) / P@ssw0rd123
tech.tdss@deped.gov.ph (TDSS) / P@ssw0rd123
tech.pes@deped.gov.ph (PES) / P@ssw0rd123
```

---

## 🧪 Testing Scenarios

### Test 1: Super Admin Access (Full System)
1. Login with: `admin@deped.gov.ph` / `P@ssw0rd123`
2. Go to **Equipment** menu
3. ✅ **Expected:** See equipment from ALL 4 schools
4. Go to **Employees** menu
5. ✅ **Expected:** See employees from ALL 4 schools
6. Go to **Tickets** menu
7. ✅ **Expected:** See tickets from ALL 4 schools
8. Go to **Users** menu
9. ✅ **Expected:** See all users and can manage them

---

### Test 2: School Admin - DCNHS (ONLY DCNHS Data)
1. Logout (if logged in)
2. Login with: `admin.dcnhs@deped.gov.ph` / `P@ssw0rd123`
3. Go to **Equipment** menu
4. ✅ **Expected:** See ONLY equipment from DCNHS
5. ❌ **Should NOT see:** Equipment from MNHS, TDSS, or PES
6. Go to **Employees** menu
7. ✅ **Expected:** See ONLY employees from DCNHS
8. Go to **Assignments** menu
9. ✅ **Expected:** See ONLY assignments from DCNHS
10. Go to **Tickets** menu
11. ✅ **Expected:** See ONLY tickets from DCNHS

---

### Test 3: School Admin - MNHS (ONLY MNHS Data)
1. Logout
2. Login with: `admin.mnhs@deped.gov.ph` / `P@ssw0rd123`
3. Repeat Test 2 but verify:
   - ✅ See ONLY MNHS data
   - ❌ NO data from DCNHS, TDSS, or PES

---

### Test 4: School Admin - TDSS (ONLY TDSS Data)
1. Logout
2. Login with: `admin.tdss@deped.gov.ph` / `P@ssw0rd123`
3. Repeat Test 2 but verify:
   - ✅ See ONLY TDSS data
   - ❌ NO data from DCNHS, MNHS, or PES

---

### Test 5: School Admin - PES (ONLY PES Data)
1. Logout
2. Login with: `admin.pes@deped.gov.ph` / `P@ssw0rd123`
3. Repeat Test 2 but verify:
   - ✅ See ONLY PES data
   - ❌ NO data from DCNHS, MNHS, or TDSS

---

### Test 6: SDO Admin (All Schools)
1. Logout
2. Login with: `admin.sdo@deped.gov.ph` / `P@ssw0rd123`
3. Go to **Equipment** menu
4. ✅ **Expected:** See equipment from ALL 4 schools
5. Go to **Employees** menu
6. ✅ **Expected:** See employees from ALL 4 schools
7. Go to **Tickets** menu
8. ✅ **Expected:** See tickets from ALL 4 schools
9. Can manage all schools' data but cannot access **Users** admin menu

---

### Test 7: Technician (Their School Only)
1. Logout
2. Login with: `tech.dcnhs@deped.gov.ph` / `P@ssw0rd123`
3. Go to **Tickets** menu
4. ✅ **Expected:** See ONLY DCNHS tickets
5. Should be able to view and respond to tickets
6. ❌ Should NOT see tickets from other schools

---

### Test 8: Navbar & User Display
1. Login with any account
2. Look at **Top Right** of navbar
3. ✅ Should see:
   - Your name
   - Your role (e.g., "school-admin")
   - Your school name (e.g., "Davao City National High School")
4. Click on user dropdown
5. ✅ Should see:
   - Profile option
   - Logout option

---

### Test 9: Vertical Sidebar Layout
1. Login with any account
2. ✅ **Expected Layout:**
   ```
   ┌──────────────────────────────┐
   │ Logo | Search | Notifications │
   ├─────────┬────────────────────┤
   │ Sidebar │   Main Content     │
   │  (Full  │   (Full Width)     │
   │ Height) │                    │
   │         │                    │
   └─────────┴────────────────────┘
   ```
3. Sidebar should show:
   - Dashboard
   - Schools
   - Equipment
   - Employees
   - Documents
   - Tickets
   - Assignments
   - Internet Connections
   - Users (if admin)
4. Content area should take full width
5. On **Mobile**: Sidebar should collapse into hamburger menu

---

### Test 10: Notifications
1. Login as: `admin.sdo@deped.gov.ph` (SDO Admin)
2. Create a new **Ticket** from another user
3. Go back to SDO Admin
4. Click **Notification Bell** in top right
5. ✅ **Expected:** Should see notification about new ticket
6. Notification should show:
   - Ticket number
   - Priority level
   - School name
   - Link to view ticket

---

## 📊 Data Distribution

### Equipment by School:
- **DCNHS:** 3 items
- **MNHS:** 2 items
- **TDSS:** 2 items
- **PES:** 1 item
- **Total:** 8 equipment items

### Employees by School:
- **DCNHS:** 3 employees
- **MNHS:** 2 employees
- **TDSS:** 2 employees
- **PES:** 1 employee
- **Total:** 8 employees

### Tickets by School:
- **DCNHS:** 1 ticket
- **MNHS:** 1 ticket
- **TDSS:** 1 ticket
- **PES:** 1 ticket
- **Total:** 4 tickets

---

## ✅ Checklist

- [ ] Super Admin sees all data
- [ ] School Admin 1 sees ONLY their school
- [ ] School Admin 2 sees ONLY their school
- [ ] School Admin 3 sees ONLY their school
- [ ] School Admin 4 sees ONLY their school
- [ ] SDO Admin sees all schools
- [ ] Technician sees only their school
- [ ] Navbar displays user name
- [ ] Navbar displays user role
- [ ] Navbar displays school name
- [ ] Vertical sidebar visible on desktop
- [ ] Sidebar collapses on mobile
- [ ] Notifications work
- [ ] No data leakage between schools

---

## 🐛 Troubleshooting

### Issue: Duplicate ticket number error
**Solution:** Model was updated with better ticket generation. Run:
```bash
docker exec ieepis-app php artisan migrate:refresh --seed
docker exec ieepis-app php artisan test:create-users
```

### Issue: Seeing data from wrong school
**Solution:** Global query scope might not be applied. Clear cache:
```bash
docker exec ieepis-app php artisan cache:clear
docker exec ieepis-app php artisan config:clear
```

### Issue: Sidebar not showing on desktop
**Solution:** Refresh browser and clear cache:
```bash
Ctrl+Shift+Delete (or Cmd+Shift+Delete on Mac)
Select "All time" → Clear data
Then refresh page
```

### Issue: Users not created
**Solution:** Run the command again:
```bash
docker exec ieepis-app php artisan test:create-users
```

---

## 📞 Support

For any issues or questions, refer to:
- `docs/IMPLEMENTATION.md` - Technical documentation
- `docs/QUICK_REFERENCE.md` - Quick lookup
- Database: All data is in MySQL container `ieepis-db`

---

**✅ System is ready for comprehensive testing!**
