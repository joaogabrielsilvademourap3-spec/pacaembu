import sys
from pathlib import Path

INTERP = Path(__file__).resolve().parent / 'venv' / 'bin' / 'python3'
if INTERP.exists() and sys.executable != str(INTERP):
    os = __import__('os')
    os.execl(str(INTERP), str(INTERP), *sys.argv)

from wsgi import application
