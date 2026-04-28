# Pacaembu OS

A full-stack Flask app for social media + web agency operations.

## Quick install (Hostinger/HostGator friendly)

### Option A: Terminal/SSH installer
```bash
bash install.sh
```
Then run Flask and open `/install` in browser.

### Option B: cPanel Python App + Passenger
1. Upload project files.
2. Create Python app (3.10+), app root as this folder, startup file `passenger_wsgi.py`.
3. Install dependencies: `pip install -r requirements.txt` in created virtualenv.
4. Open your domain `/install` and complete one-time wizard:
   - `SECRET_KEY`
   - `DATABASE_URL` (SQLite or MySQL)
   - admin account
5. After installation, `instance/install.lock` is created and installer is disabled.

## Database
- Default: SQLite (`instance/pacaembu.db`)
- For MySQL example:
  `mysql+pymysql://user:password@localhost/database`

## Local run
```bash
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
python app.py
```

## Installer route
- `/install` (one-time setup)
- Creates admin user, database tables and optional demo seed.
