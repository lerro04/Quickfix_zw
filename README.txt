==============================================
QuickFix ZW — Zimbabwe's Home Services Marketplace
Group 3 | BSIT Level 2.2 | CUT | Mr. Masamha
==============================================

SETUP INSTRUCTIONS (XAMPP)
----------------------------
1. Copy the entire 'quickfix' folder to:
   C:\xampp\htdocs\quickfix

2. Start Apache and MySQL in XAMPP Control Panel

3. Open browser → http://localhost/phpmyadmin

4. Create database:
   - Click "New" → Name: quickfix_db → Create

5. Import database:
   - Click quickfix_db → Import tab
   - Choose: quickfix_database.sql → Go

6. Open the system:
   http://localhost/quickfix

DEMO LOGIN CREDENTIALS (password: password)
---------------------------------------------
Admin:        admin@quickfixzw.co.zw
Plumber:      plumber@quickfixzw.co.zw
Electrician:  electrician@quickfixzw.co.zw
Painter:      painter@quickfixzw.co.zw
Client 1:     client1@quickfixzw.co.zw
Client 2:     client2@quickfixzw.co.zw

HOW THE SYSTEM WORKS 
--------------------------------------
WAY 1 — Client Posts a Job :
  1. Client posts a job request with description + budget
  2. Professionals see the job on the Job Board
  3. Professionals place competitive bids
  4. Client reviews all bids and accepts the best one
  5. Booking is automatically created
  6. Professional starts & completes the job
  7. Client marks complete → payment released

WAY 2 — Client Browses & Books Directly:
  1. Client browses professionals by trade & location
  2. Client clicks "Book Now" on a profile
  3. Client enters job details + agreed amount + date
  4. Booking confirmed immediately

TRADE CATEGORIES
-----------------
Plumbing | Electrical | Painting | Carpentry
Tiling | Roofing | Welding | Landscaping | General Handyman

TECH STACK
-----------
PHP 8.x | MySQL 8.x | HTML5 | CSS3 | JavaScript
