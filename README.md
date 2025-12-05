# JD Realty & Investment - Complete Real Estate Platform

A comprehensive PHP-based real estate platform with user authentication, property listing, search, and property management features for JD Realty & Investment.

## 🎯 Key Features

✅ **User Authentication & Authorization**
- User registration and login with validation
- Admin authentication with separate dashboard
- Secure password hashing using bcrypt
- Session management and logout

✅ **Property Management**
- Users can list their properties with detailed information
- Property title, description, type, location, price, area, bedrooms, bathrooms
- Property status tracking (available, sold, under_construction)
- Edit and delete properties from dashboard

✅ **Property Browsing & Search**
- Browse all available properties with filters
- Advanced search by city, property type, category
- Pagination for better navigation
- View detailed property information

✅ **Property Details & Inquiries**
- Detailed property pages with full information
- Contact property owner directly
- Send inquiries to property owners
- View owner contact information

✅ **User Dashboard**
- Manage all listed properties
- Statistics showing total, available, sold properties
- Quick access to list new property
- View and manage property status

✅ **About Us Page**
- Company vision, mission, and values
- Team information
- Core values displayed as cards

✅ **Responsive Design**
- Mobile-friendly interface
- Clean and modern UI
- Smooth animations and transitions

---

## 📁 File Structure

```
jd-realty/
├── index.php                 # Homepage (public, shows recent listings)
├── login.php                 # User login page
├── signup.php                # User registration page
├── about.php                 # About Us page
├── search.php                # Search and browse properties
├── property-details.php      # Property details and inquiries
├── list-property.php         # Add new property (login required)
├── user-dashboard.php        # Manage user properties (login required)
├── database.sql              # Database setup
│
├── admin/
│   ├── login.php            # Admin login
│   └── dashboard.php        # Admin dashboard
│
├── includes/
│   ├── config.php           # Database configuration
│   └── logout.php           # Logout handler
│
├── css/
│   └── style.css            # Global stylesheets
│
├── js/
│   └── script.js            # JavaScript functionality
│
├── images/                  # Image storage
└── README.md                # This file
```

---

## 🚀 Installation Steps

### 1. **Start XAMPP Services**
```bash
# Start Apache and MySQL from XAMPP Control Panel
# Or use command line:
xampp start
```

### 2. **Setup Database**

**Option A: Using phpMyAdmin**
- Open: http://localhost/phpmyadmin
- Click "Import"
- Select `database.sql`
- Click "Go"

**Option B: Using Command Line**
```bash
mysql -u root -p < database.sql
```

### 3. **Copy Project Files**
```
Copy jd-realty folder to: C:\xampp\htdocs\
```

### 4. **Access Application**
```
http://localhost/jd-realty/
```

---

## 🔐 Login Credentials

### Admin Account:
```
Email: admin@jdrealty.com
Password: Admin@123
URL: http://localhost/jd-realty/admin/login.php
```

### Demo User Account:
```
Email: user@example.com
Password: User@123
URL: http://localhost/jd-realty/login.php
```

---

## 📄 Page Descriptions

### 1. **Homepage (`index.php`)**
- Shows 6 most recent available properties
- Search filters for city, property type, budget
- CTA to list properties (login required)
- Navigation to login/signup

### 2. **About Us (`about.php`)**
- Company mission and vision
- Core values section
- Team information
- Company tagline and description

### 3. **User Registration (`signup.php`)**
- Full name, email, phone, password
- Form validation (10-digit phone, 6+ char password)
- Duplicate email checking
- Auto-redirect to login on success

### 4. **User Login (`login.php`)**
- Secure login with bcrypt verification
- Links to signup and admin login
- Error messages for invalid credentials

### 5. **Search Properties (`search.php`)**
- Filter by city, property type, category
- Pagination (12 properties per page)
- Display total results count
- View details button for each property

### 6. **Property Details (`property-details.php`)**
- Full property information display
- Owner contact details
- Send inquiry form (login required)
- Property features display

### 7. **List Property (`list-property.php`)**
- Form to add new property
- Fields: Title, description, type, category, city, price, area, bedrooms, bathrooms
- Form validation
- Success/error messages

### 8. **User Dashboard (`user-dashboard.php`)**
- Statistics cards (total, available, sold, under_construction)
- Table view of all user properties
- Edit and delete options
- Status badges
- Button to list new property

### 9. **Admin Dashboard (`admin/dashboard.php`)**
- System statistics
- User management
- Property overview
- Quick action buttons

---

## 🗄️ Database Tables

### users
```sql
id | name | email | password | phone | address | role | created_at | updated_at
```

### properties
```sql
id | title | description | property_type | category | city | price | area_sqft | 
bedrooms | bathrooms | image_url | status | created_by | created_at | updated_at
```

### inquiries
```sql
id | user_id | property_id | name | email | phone | message | status | 
created_at | replied_at
```

---

## 🔒 Security Features

1. **Password Security**
   - Bcrypt hashing with salt
   - `password_hash()` and `password_verify()`
   - No plain text passwords stored

2. **SQL Injection Prevention**
   - MySQLi `real_escape_string()`
   - Input validation and sanitization
   - Parameterized queries

3. **Session Management**
   - Secure session handling
   - Session destruction on logout
   - User authentication checks on protected pages

4. **Access Control**
   - Role-based access (user/admin)
   - Property ownership verification
   - Protected delete/edit operations

---

## 👥 User Features

### For Property Owners:
1. **Register & Login** - Create account with validation
2. **List Properties** - Add unlimited properties with details
3. **Manage Properties** - View, edit, delete listings
4. **Dashboard** - See statistics and overview
5. **Inquiries** - Receive inquiries from buyers

### For Buyers:
1. **Browse Properties** - View all available listings
2. **Search & Filter** - Find properties by criteria
3. **View Details** - See complete property information
4. **Contact Owner** - Send inquiries to property owners
5. **Login** - Create account to send inquiries

---

## 📱 Responsive Design

- Mobile-friendly interface
- Tablet optimized views
- Desktop responsive layout
- Touch-friendly buttons and forms

---

## 🎨 UI Features

- Modern gradient buttons
- Smooth hover effects
- Clean card-based layout
- Professional color scheme
- Status badges with color coding
- Empty state messages
- Success/error notifications

---

## 🔧 Common Tasks

### Create New User Via Database
```sql
INSERT INTO users (name, email, password, phone, role) 
VALUES ('John Doe', 'john@example.com', '$2y$10$...hash...', '9876543210', 'user');
```

### Generate Password Hash
```php
echo password_hash('YourPassword@123', PASSWORD_BCRYPT);
```

### Add Sample Property
```sql
INSERT INTO properties (title, description, property_type, category, city, price, area_sqft, bedrooms, bathrooms, created_by, status)
VALUES ('2BHK Apartment', 'Beautiful apartment', 'residential', '2bhk', 'Thane', 7500000, 950, 2, 2, 1, 'available');
```

---

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Cannot connect to database | Ensure MySQL is running in XAMPP |
| Tables not found | Re-import database.sql in phpMyAdmin |
| Login fails | Verify credentials in database |
| 404 errors | Check folder is at C:\xampp\htdocs\jd-realty |
| Password issues | Use phpMyAdmin to generate bcrypt hash |

---

## 📈 Future Enhancements

- [ ] Property image upload and gallery
- [ ] Advanced search with price range
- [ ] Saved/favorite properties
- [ ] Property comparison feature
- [ ] User reviews and ratings
- [ ] Email notifications
- [ ] Forgot password functionality
- [ ] Email verification
- [ ] Two-factor authentication
- [ ] Admin moderation for listings
- [ ] Property analytics dashboard
- [ ] API for mobile app

---

## 📞 Contact

**JD Realty & Investment**
- Email: admin@jdrealty.com
- Founded by: Jeetender Parasni & Dinesh Mittal
- Developed by: T.Rama Krishna
- Location: Thane, Maharashtra

---

## 📜 License

© 2025 JD Realty & Investment. All rights reserved.

---

## 🎓 Tech Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Authentication:** bcrypt password hashing
- **Server:** Apache (XAMPP)

