# AgencyCRM (Windows WinForms .NET Framework 4.8)

Offline-first CRM desktop application for web development and social media agencies.

## What is already functional
- Local SQLite database creation on first startup.
- Data persistence to `%LOCALAPPDATA%\AgencyCRM\agencycrm.db`.
- Real CRUD screens (add/edit/delete/refresh/export CSV) for agency modules.
- Dashboard KPI summary.
- Global search across clients/leads/projects.
- Reports tab (project status summary).
- Settings with API key save, demo-data cleanup, and local DB backup.
- AI Assistant tab with optional API call support.

## Run the EXE (no Visual Studio required)
1. Use a Windows machine with .NET Framework 4.8 installed.
2. Open the `release` folder (or build output folder) and run `AgencyCRM.exe`.
3. On first run, the app initializes the local database automatically.

## Build without Visual Studio UI
Use a Developer Command Prompt on Windows:
- `nuget restore AgencyCRM.sln`
- `msbuild AgencyCRM.sln /p:Configuration=Release`

## Database schema
- See `AgencyCRM/schema.sql`.

## Backup
- In-app: `Settings -> Backup Database`
- Manual: copy `%LOCALAPPDATA%\AgencyCRM\agencycrm.db` while the app is closed.
