# Student Online Voting System
> A complete CRUD-based School Election System in PHP + MySQL with a connected frontend.  
> Built for: **Tangub City Global College — Institute of Computer Studies**

---

## Tech Stack
- **Backend:** PHP (vanilla, no framework)
- **Database:** MySQL via XAMPP
- **Frontend:** HTML + CSS + Vanilla JavaScript (Fetch API)

---

## Project Structure
```
student-voting-system/
├── api/
│   ├── config/
│   │   └── database.php          ← DB connection + helpers
│   ├── endpoints/
│   │   ├── candidates.php        ← CRUD: candidates
│   │   ├── voters.php            ← CRUD: voters
│   │   ├── positions.php         ← CRUD: positions
│   │   ├── votes.php             ← Cast vote (with transaction)
│   │   ├── results.php           ← Live election tally
│   │   └── settings.php         ← Election settings
│   ├── index.php                 ← Router
│   └── .htaccess
├── frontend/
│   └── index.html                ← Full admin + voting UI
├── database.sql                  ← Schema + sample data
└── README.md
```

---

## Setup Instructions

### 1. Start XAMPP
Make sure **Apache** and **MySQL** are both running.

### 2. Copy project
Place the folder in XAMPP's htdocs:
```
C:/xampp/htdocs/student-voting-system/
```

### 3. Import database
- Open `http://localhost/phpmyadmin`
- Click **Import** → select `database.sql` → **Go**

### 4. Open the app
```
http://localhost/student-voting-system/frontend/index.html
```

---

## API Endpoints

Base URL: `http://localhost/student-voting-system/api`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/positions` | Get all positions |
| POST | `/positions` | Create position |
| PUT | `/positions/{id}` | Update position |
| DELETE | `/positions/{id}` | Delete position |
| GET | `/candidates` | Get all candidates (supports ?search=, ?position_id=, ?status=) |
| POST | `/candidates` | Add candidate |
| PUT | `/candidates/{id}` | Update candidate |
| DELETE | `/candidates/{id}` | Delete candidate |
| GET | `/voters` | Get all voters (supports ?search=, ?has_voted=) |
| POST | `/voters` | Register voter |
| PUT | `/voters/{id}` | Update voter |
| DELETE | `/voters/{id}` | Delete voter |
| GET | `/votes` | Get all cast votes |
| POST | `/votes` | Cast vote (with DB transaction) |
| GET | `/results` | Live election tally with turnout % |
| GET | `/settings` | Get election settings |
| PUT | `/settings` | Update election settings |

---

## Features
- Full **CRUD** for Candidates, Voters, and Positions
- **Vote casting** with duplicate prevention and DB transactions
- **Live results** with vote tally per position
- **Voter turnout** percentage
- Prevent voting twice (one-time vote per voter)
- Candidate disqualification support
- Search and filter on all tables
- Tabbed admin dashboard with real-time stats

---

## Developer
**STEPHEN MARK MALUTO**  
Institute of Computer Studies — Tangub City Global College
