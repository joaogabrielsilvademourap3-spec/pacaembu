# Pacaembu OS

Full-stack responsive agency management platform inspired by productivity workflows, with original branding and Windows 7/8 hybrid visual language.

## Features
- JWT authentication (register/login/logout) with protected API
- Dashboard with KPIs, recent activity, and AI suggestions
- CRUD modules: clients, projects, tasks, content calendar, website projects, metrics, payments, approvals
- Task multi-view: table, kanban, calendar-like, timeline-like
- File manager (uploads + links)
- AI assistant endpoint with agency recommendations
- PDF report export per client/month
- SQLite persistence with all requested core database tables
- Role-aware restrictions for client users
- Responsive sidebar + dashboard tile layout

## Run
```bash
npm install
npm run dev
```

Open: http://localhost:3000

Demo admin:
- `admin@pacaembu.app`
- `admin123`
