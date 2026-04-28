#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt

mkdir -p instance uploads

cat <<MSG
Installation base complete.
Now configure your app in browser:
  1) start app with: source venv/bin/activate && FLASK_APP=app.py flask run
  2) open /install and finish setup (DB + admin user)
For cPanel Passenger, point application to passenger_wsgi.py.
MSG
