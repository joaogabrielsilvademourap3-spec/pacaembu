# AgencyCRM (Windows WinForms .NET Framework 4.8)

Offline-first CRM desktop application for web development and social media agencies.

## Stack
- C# / .NET Framework 4.8
- Windows Forms (Windows 7/8 style compatible)
- SQLite local database (auto-created in `%LOCALAPPDATA%/AgencyCRM`)

## Features
- Dashboard KPIs
- CRUD modules: Clients, Leads, Projects, Tasks, Calendar, Proposals, Contracts, Finance, Social posts, Website maintenance
- Reports tab
- Global Search tab
- Settings tab with API key persistence
- AI Assistant tab (OpenAI-compatible chat completions endpoint)
- Demo data on first launch

## Build
1. Open `AgencyCRM.sln` in Visual Studio 2022/2019.
2. Restore NuGet packages.
3. Build Release.
4. Run `AgencyCRM.exe`.

## Portable release
- Copy `AgencyCRM/bin/Release` folder to target machine with .NET Framework 4.8 runtime.

## Installer
- Recommended: Visual Studio Installer Project or Inno Setup pointing to Release output.

## Database schema
- See `AgencyCRM/schema.sql`.

## Backup/Restore
- Database file path: `%LOCALAPPDATA%/AgencyCRM/agencycrm.db`.
- Backup by copying file while app is closed.

