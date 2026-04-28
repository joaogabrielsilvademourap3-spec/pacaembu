const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const Database = require('better-sqlite3');
const multer = require('multer');
const PDFDocument = require('pdfkit');

const app = express();
const PORT = process.env.PORT || 3000;
const JWT_SECRET = process.env.JWT_SECRET || 'pacaembu-secret-key';

const dataDir = path.join(__dirname, 'data');
const uploadDir = path.join(__dirname, 'uploads');
if (!fs.existsSync(dataDir)) fs.mkdirSync(dataDir);
if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir);

const db = new Database(path.join(dataDir, 'pacaembu.db'));
const upload = multer({ dest: uploadDir });

app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use('/uploads', express.static(uploadDir));
app.use(express.static(path.join(__dirname, 'public')));

function initDb() {
  db.exec(`
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT UNIQUE NOT NULL,
      password_hash TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'admin',
      client_id INTEGER,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS clients (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      company_name TEXT NOT NULL,
      contact_name TEXT,
      whatsapp TEXT,
      email TEXT,
      website TEXT,
      instagram TEXT,
      facebook TEXT,
      tiktok TEXT,
      linkedin TEXT,
      service_plan TEXT,
      monthly_fee REAL DEFAULT 0,
      status TEXT DEFAULT 'prospect',
      internal_notes TEXT,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS projects (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      title TEXT NOT NULL,
      category TEXT,
      status TEXT DEFAULT 'planning',
      priority TEXT DEFAULT 'medium',
      deadline TEXT,
      responsible_user_id INTEGER,
      checklist TEXT DEFAULT '[]',
      comments TEXT DEFAULT '',
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS tasks (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      project_id INTEGER,
      title TEXT NOT NULL,
      description TEXT,
      status TEXT DEFAULT 'todo',
      priority TEXT DEFAULT 'medium',
      responsible_user_id INTEGER,
      due_date TEXT,
      tags TEXT DEFAULT '[]',
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS subtasks (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      task_id INTEGER,
      title TEXT NOT NULL,
      completed INTEGER DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS comments (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      entity_type TEXT NOT NULL,
      entity_id INTEGER NOT NULL,
      user_id INTEGER,
      body TEXT NOT NULL,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS files (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      project_id INTEGER,
      original_name TEXT,
      file_path TEXT,
      file_url TEXT,
      notes TEXT,
      uploaded_by INTEGER,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS content_calendar (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      platform TEXT,
      content_type TEXT,
      publish_date TEXT,
      caption TEXT,
      creative_briefing TEXT,
      references TEXT,
      approval_status TEXT DEFAULT 'draft',
      attached_file TEXT,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS website_projects (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      domain TEXT,
      hosting_provider TEXT,
      cms_platform TEXT,
      admin_url TEXT,
      project_stage TEXT,
      seo_checklist TEXT DEFAULT '[]',
      performance_checklist TEXT DEFAULT '[]',
      security_checklist TEXT DEFAULT '[]',
      backup_status TEXT,
      maintenance_notes TEXT,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS metrics (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      month TEXT,
      followers INTEGER DEFAULT 0,
      reach INTEGER DEFAULT 0,
      impressions INTEGER DEFAULT 0,
      engagement INTEGER DEFAULT 0,
      clicks INTEGER DEFAULT 0,
      leads INTEGER DEFAULT 0,
      conversions INTEGER DEFAULT 0,
      website_traffic INTEGER DEFAULT 0,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS reports (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      month TEXT,
      summary TEXT,
      file_path TEXT,
      created_by INTEGER,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS payments (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      description TEXT,
      amount REAL,
      due_date TEXT,
      status TEXT DEFAULT 'pending',
      payment_type TEXT DEFAULT 'retainer',
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS approvals (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER,
      content_id INTEGER,
      status TEXT DEFAULT 'pending',
      notes TEXT,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS ai_logs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER,
      prompt TEXT,
      response TEXT,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS notifications (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER,
      title TEXT,
      message TEXT,
      read INTEGER DEFAULT 0,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
  `);

  const admin = db.prepare('SELECT * FROM users WHERE email = ?').get('admin@pacaembu.app');
  if (!admin) {
    const hash = bcrypt.hashSync('admin123', 10);
    db.prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)').run('Admin', 'admin@pacaembu.app', hash, 'admin');
  }
}

initDb();

const authRequired = (req, res, next) => {
  const header = req.headers.authorization;
  if (!header) return res.status(401).json({ error: 'Missing token' });
  try {
    const token = header.replace('Bearer ', '');
    req.user = jwt.verify(token, JWT_SECRET);
    next();
  } catch (e) {
    res.status(401).json({ error: 'Invalid token' });
  }
};

function roleAllowed(roles) {
  return (req, res, next) => {
    if (!roles.includes(req.user.role)) return res.status(403).json({ error: 'Forbidden' });
    next();
  };
}

app.post('/api/auth/register', (req, res) => {
  const { name, email, password, role = 'designer' } = req.body;
  if (!name || !email || !password) return res.status(400).json({ error: 'Missing required fields' });
  const exists = db.prepare('SELECT id FROM users WHERE email = ?').get(email);
  if (exists) return res.status(400).json({ error: 'Email already exists' });
  const hash = bcrypt.hashSync(password, 10);
  const result = db.prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)').run(name, email, hash, role);
  res.json({ id: result.lastInsertRowid });
});

app.post('/api/auth/login', (req, res) => {
  const { email, password } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);
  if (!user || !bcrypt.compareSync(password, user.password_hash)) return res.status(401).json({ error: 'Invalid credentials' });
  const token = jwt.sign({ id: user.id, role: user.role, name: user.name, email: user.email, client_id: user.client_id }, JWT_SECRET, { expiresIn: '7d' });
  res.json({ token, user: { id: user.id, name: user.name, email: user.email, role: user.role, client_id: user.client_id } });
});

app.get('/api/auth/me', authRequired, (req, res) => res.json(req.user));

const modules = ['clients', 'projects', 'tasks', 'subtasks', 'comments', 'content_calendar', 'website_projects', 'metrics', 'reports', 'payments', 'approvals', 'notifications'];

for (const module of modules) {
  app.get(`/api/${module}`, authRequired, (req, res) => {
    const allowedForClient = ['approvals', 'content_calendar'];
    if (req.user.role === 'client' && !allowedForClient.includes(module)) return res.status(403).json({ error: 'Client restricted' });

    const q = req.query.q;
    let rows;
    if (q) {
      const textCols = module === 'clients' ? ['company_name', 'contact_name', 'email', 'status'] : module === 'projects' ? ['title', 'category', 'status'] : ['title', 'description', 'status'];
      const where = textCols.map(c => `${c} LIKE ?`).join(' OR ');
      rows = db.prepare(`SELECT * FROM ${module} WHERE ${where} ORDER BY id DESC`).all(...textCols.map(() => `%${q}%`));
    } else {
      rows = db.prepare(`SELECT * FROM ${module} ORDER BY id DESC`).all();
    }
    res.json(rows);
  });

  app.post(`/api/${module}`, authRequired, (req, res) => {
    const data = req.body;
    const keys = Object.keys(data);
    if (!keys.length) return res.status(400).json({ error: 'No data' });
    const cols = keys.join(',');
    const placeholders = keys.map(() => '?').join(',');
    const values = keys.map(k => typeof data[k] === 'object' ? JSON.stringify(data[k]) : data[k]);
    const result = db.prepare(`INSERT INTO ${module} (${cols}) VALUES (${placeholders})`).run(...values);
    res.json({ id: result.lastInsertRowid });
  });

  app.put(`/api/${module}/:id`, authRequired, (req, res) => {
    const data = req.body;
    const keys = Object.keys(data);
    if (!keys.length) return res.status(400).json({ error: 'No data' });
    const set = keys.map(k => `${k} = ?`).join(',');
    const values = keys.map(k => typeof data[k] === 'object' ? JSON.stringify(data[k]) : data[k]);
    db.prepare(`UPDATE ${module} SET ${set} WHERE id = ?`).run(...values, req.params.id);
    res.json({ ok: true });
  });

  app.delete(`/api/${module}/:id`, authRequired, (req, res) => {
    db.prepare(`DELETE FROM ${module} WHERE id = ?`).run(req.params.id);
    res.json({ ok: true });
  });
}

app.get('/api/dashboard/summary', authRequired, (req, res) => {
  const today = new Date().toISOString().slice(0, 10);
  const activeClients = db.prepare("SELECT COUNT(*) c FROM clients WHERE status = 'active'").get().c;
  const activeProjects = db.prepare("SELECT COUNT(*) c FROM projects WHERE status IN ('planning','in progress','waiting approval','revision')").get().c;
  const dueToday = db.prepare('SELECT COUNT(*) c FROM tasks WHERE due_date = ?').get(today).c;
  const overdue = db.prepare("SELECT COUNT(*) c FROM tasks WHERE due_date < ? AND status != 'done'").get(today).c;
  const revenue = db.prepare("SELECT IFNULL(SUM(amount),0) t FROM payments WHERE status IN ('paid','pending')").get().t;
  const recent = db.prepare('SELECT entity_type, body, created_at FROM comments ORDER BY id DESC LIMIT 6').all();

  res.json({
    activeClients,
    activeProjects,
    dueToday,
    overdue,
    revenue,
    recent,
    aiSuggestions: [
      'Prioritize overdue tasks for top-paying active clients.',
      'Follow up approvals pending more than 3 days.',
      'Review low-engagement clients and propose new content mix.'
    ]
  });
});

app.post('/api/files/upload', authRequired, upload.single('file'), (req, res) => {
  const { client_id, project_id, notes } = req.body;
  const result = db.prepare('INSERT INTO files (client_id, project_id, original_name, file_path, notes, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)')
    .run(client_id || null, project_id || null, req.file.originalname, `/uploads/${req.file.filename}`, notes || '', req.user.id);
  res.json({ id: result.lastInsertRowid, file: `/uploads/${req.file.filename}` });
});

app.post('/api/ai/assist', authRequired, (req, res) => {
  const { prompt } = req.body;
  const overdue = db.prepare("SELECT title, due_date FROM tasks WHERE due_date < date('now') AND status != 'done' ORDER BY due_date ASC LIMIT 5").all();
  const topClients = db.prepare("SELECT company_name, monthly_fee FROM clients ORDER BY monthly_fee DESC LIMIT 3").all();
  const response = `Focus on ${overdue.length} overdue tasks first. Top revenue clients: ${topClients.map(c => `${c.company_name} ($${c.monthly_fee})`).join(', ') || 'none'}. Suggested action: run approval check and schedule content for the next 7 days.`;
  db.prepare('INSERT INTO ai_logs (user_id, prompt, response) VALUES (?, ?, ?)').run(req.user.id, prompt, response);
  res.json({ response });
});

app.post('/api/reports/:clientId/:month/pdf', authRequired, (req, res) => {
  const { clientId, month } = req.params;
  const client = db.prepare('SELECT * FROM clients WHERE id = ?').get(clientId);
  if (!client) return res.status(404).json({ error: 'Client not found' });
  const tasks = db.prepare('SELECT * FROM tasks WHERE client_id = ?').all(clientId);
  const metrics = db.prepare('SELECT * FROM metrics WHERE client_id = ? AND month = ?').get(clientId, month);

  res.setHeader('Content-Type', 'application/pdf');
  res.setHeader('Content-Disposition', `attachment; filename="report-${client.company_name}-${month}.pdf"`);
  const doc = new PDFDocument();
  doc.pipe(res);
  doc.fontSize(20).text(`Pacaembu Report - ${client.company_name}`);
  doc.fontSize(12).text(`Month: ${month}`);
  doc.moveDown().text(`Tasks completed: ${tasks.filter(t => t.status === 'done').length}/${tasks.length}`);
  if (metrics) {
    doc.moveDown().text(`Followers: ${metrics.followers}`)
      .text(`Reach: ${metrics.reach}`)
      .text(`Impressions: ${metrics.impressions}`)
      .text(`Engagement: ${metrics.engagement}`)
      .text(`Website traffic: ${metrics.website_traffic}`);
  }
  doc.moveDown().text('AI Summary: Performance is stable. Prioritize conversion-focused campaigns and optimize high-traffic landing pages.');
  doc.end();
});

app.get('*', (req, res) => res.sendFile(path.join(__dirname, 'public/index.html')));

app.listen(PORT, () => console.log(`Pacaembu OS running on http://localhost:${PORT}`));
