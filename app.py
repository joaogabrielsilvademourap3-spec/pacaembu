import os
from datetime import datetime, date
from functools import wraps
from pathlib import Path

from flask import Flask, render_template, request, redirect, url_for, flash, send_file
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from werkzeug.security import generate_password_hash, check_password_hash
from werkzeug.utils import secure_filename
from fpdf import FPDF

BASE_DIR = Path(__file__).resolve().parent
INSTANCE_DIR = BASE_DIR / "instance"
UPLOAD_DIR = BASE_DIR / "uploads"
INSTALL_LOCK = INSTANCE_DIR / "install.lock"
ENV_FILE = BASE_DIR / ".env"

INSTANCE_DIR.mkdir(exist_ok=True)
UPLOAD_DIR.mkdir(exist_ok=True)


def db_uri_from_env():
    # Hosting-friendly: supports SQLite by default and MySQL/Postgres via DATABASE_URL.
    return os.getenv("DATABASE_URL", f"sqlite:///{(INSTANCE_DIR / 'pacaembu.db').as_posix()}")


app = Flask(__name__)
app.config["SECRET_KEY"] = os.getenv("SECRET_KEY", "change-me-in-installer")
app.config["SQLALCHEMY_DATABASE_URI"] = db_uri_from_env()
app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False
app.config["UPLOAD_FOLDER"] = str(UPLOAD_DIR)

db = SQLAlchemy(app)
login_manager = LoginManager(app)
login_manager.login_view = "login"


class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    role = db.Column(db.String(40), default="Admin")


class Client(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    company_name = db.Column(db.String(120), nullable=False)
    contact_name = db.Column(db.String(120))
    whatsapp = db.Column(db.String(40))
    email = db.Column(db.String(120))
    website = db.Column(db.String(120))
    instagram = db.Column(db.String(120))
    facebook = db.Column(db.String(120))
    tiktok = db.Column(db.String(120))
    linkedin = db.Column(db.String(120))
    service_plan = db.Column(db.String(80))
    monthly_fee = db.Column(db.Float, default=0)
    status = db.Column(db.String(30), default="active")
    notes = db.Column(db.Text, default="")


class Project(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    category = db.Column(db.String(40), nullable=False)
    status = db.Column(db.String(40), default="planning")
    priority = db.Column(db.String(20), default="medium")
    deadline = db.Column(db.Date)
    responsible = db.Column(db.String(120))
    comments = db.Column(db.Text, default="")
    checklist = db.Column(db.Text, default="")
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    client = db.relationship("Client")


class Task(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(120), nullable=False)
    description = db.Column(db.Text, default="")
    status = db.Column(db.String(40), default="todo")
    priority = db.Column(db.String(20), default="medium")
    responsible = db.Column(db.String(120))
    due_date = db.Column(db.Date)
    tags = db.Column(db.String(200), default="")
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"))
    project_id = db.Column(db.Integer, db.ForeignKey("project.id"))
    client = db.relationship("Client")
    project = db.relationship("Project")


class Subtask(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(120), nullable=False)
    done = db.Column(db.Boolean, default=False)
    task_id = db.Column(db.Integer, db.ForeignKey("task.id"), nullable=False)


class Comment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    content = db.Column(db.Text, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    user = db.Column(db.String(120))
    task_id = db.Column(db.Integer, db.ForeignKey("task.id"))
    project_id = db.Column(db.Integer, db.ForeignKey("project.id"))


class File(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    filename = db.Column(db.String(255), nullable=False)
    filepath = db.Column(db.String(255), nullable=False)
    notes = db.Column(db.Text, default="")
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"))
    project_id = db.Column(db.Integer, db.ForeignKey("project.id"))


class ContentCalendar(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    platform = db.Column(db.String(40), nullable=False)
    content_type = db.Column(db.String(80), nullable=False)
    publish_date = db.Column(db.Date, nullable=False)
    caption = db.Column(db.Text, default="")
    creative_briefing = db.Column(db.Text, default="")
    references = db.Column(db.Text, default="")
    approval_status = db.Column(db.String(40), default="draft")
    attached_file = db.Column(db.String(255), default="")
    client = db.relationship("Client")


class WebsiteProject(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    domain = db.Column(db.String(120), nullable=False)
    hosting_provider = db.Column(db.String(120), default="")
    cms_platform = db.Column(db.String(80), default="")
    admin_url = db.Column(db.String(255), default="")
    project_stage = db.Column(db.String(80), default="planning")
    seo_checklist = db.Column(db.Text, default="")
    performance_checklist = db.Column(db.Text, default="")
    security_checklist = db.Column(db.Text, default="")
    backup_status = db.Column(db.String(80), default="pending")
    maintenance_notes = db.Column(db.Text, default="")
    client = db.relationship("Client")


class Metric(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    month = db.Column(db.String(7), nullable=False)
    followers = db.Column(db.Integer, default=0)
    reach = db.Column(db.Integer, default=0)
    impressions = db.Column(db.Integer, default=0)
    engagement = db.Column(db.Integer, default=0)
    clicks = db.Column(db.Integer, default=0)
    leads = db.Column(db.Integer, default=0)
    conversions = db.Column(db.Integer, default=0)
    website_traffic = db.Column(db.Integer, default=0)
    client = db.relationship("Client")


class Report(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    month = db.Column(db.String(7), nullable=False)
    summary = db.Column(db.Text, default="")
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    client = db.relationship("Client")


class Payment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    kind = db.Column(db.String(40), nullable=False)
    amount = db.Column(db.Float, nullable=False)
    due_date = db.Column(db.Date)
    status = db.Column(db.String(20), default="pending")
    notes = db.Column(db.Text, default="")
    client = db.relationship("Client")


class Approval(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    item_type = db.Column(db.String(40), nullable=False)
    item_id = db.Column(db.Integer, nullable=False)
    status = db.Column(db.String(40), default="sent to client")
    notes = db.Column(db.Text, default="")


class AILog(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"))
    prompt = db.Column(db.Text, nullable=False)
    response = db.Column(db.Text, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class Notification(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    text = db.Column(db.Text, nullable=False)
    read = db.Column(db.Boolean, default=False)


@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))


def parse_date(value):
    if not value:
        return None
    return datetime.strptime(value, "%Y-%m-%d").date()


def seed_once():
    if Client.query.count() > 0:
        return
    c = Client(company_name="Blue Harbor Dental", contact_name="Amanda", email="client@example.com", monthly_fee=2500, status="active", service_plan="Growth")
    db.session.add(c)
    db.session.flush()
    p = Project(name="Instagram April", category="social media", status="in progress", priority="high", deadline=date.today(), client_id=c.id)
    t = Task(title="Schedule posts", status="doing", priority="high", due_date=date.today(), client_id=c.id)
    pay = Payment(client_id=c.id, kind="retainer", amount=2500, status="pending", due_date=date.today())
    db.session.add_all([p, t, pay])
    db.session.commit()


def is_installed():
    return INSTALL_LOCK.exists()


def append_env(secret_key, database_url):
    lines = [
        f"SECRET_KEY={secret_key}",
        f"DATABASE_URL={database_url}",
    ]
    ENV_FILE.write_text("\n".join(lines) + "\n", encoding="utf-8")


@app.before_request
def guard_installation():
    public_routes = {"installer", "static", "login", "register"}
    if not is_installed() and request.endpoint not in public_routes:
        return redirect(url_for("installer"))


@app.route("/install", methods=["GET", "POST"])
def installer():
    if is_installed():
        return redirect(url_for("login"))

    if request.method == "POST":
        secret_key = request.form["secret_key"].strip()
        database_url = request.form["database_url"].strip() or db_uri_from_env()
        admin_name = request.form["admin_name"].strip()
        admin_email = request.form["admin_email"].strip().lower()
        admin_password = request.form["admin_password"]
        seed_demo = request.form.get("seed_demo") == "on"

        if not all([secret_key, database_url, admin_name, admin_email, admin_password]):
            flash("Please fill all required installer fields.", "danger")
            return redirect(url_for("installer"))

        append_env(secret_key, database_url)
        app.config["SECRET_KEY"] = secret_key
        app.config["SQLALCHEMY_DATABASE_URI"] = database_url

        with app.app_context():
            db.drop_all()
            db.create_all()
            admin = User(name=admin_name, email=admin_email, role="Admin", password_hash=generate_password_hash(admin_password))
            db.session.add(admin)
            db.session.commit()
            if seed_demo:
                seed_once()

        INSTALL_LOCK.write_text(datetime.utcnow().isoformat(), encoding="utf-8")
        flash("Installation completed. Login with your admin account.", "success")
        return redirect(url_for("login"))

    return render_template("installer.html", default_db=db_uri_from_env())


@app.route("/")
def root():
    return redirect(url_for("dashboard")) if current_user.is_authenticated else redirect(url_for("login"))


@app.route("/register", methods=["GET", "POST"])
def register():
    if request.method == "POST":
        email = request.form["email"].strip().lower()
        if User.query.filter_by(email=email).first():
            flash("Email already registered", "warning")
            return redirect(url_for("register"))
        u = User(name=request.form["name"], email=email, role=request.form.get("role", "Admin"), password_hash=generate_password_hash(request.form["password"]))
        db.session.add(u)
        db.session.commit()
        login_user(u)
        return redirect(url_for("dashboard"))
    return render_template("auth.html", mode="register")


@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        user = User.query.filter_by(email=request.form["email"].strip().lower()).first()
        if not user or not check_password_hash(user.password_hash, request.form["password"]):
            flash("Invalid credentials", "danger")
            return redirect(url_for("login"))
        login_user(user, remember=True)
        return redirect(url_for("dashboard"))
    return render_template("auth.html", mode="login")


@app.route("/logout")
@login_required
def logout():
    logout_user()
    return redirect(url_for("login"))


@app.route("/dashboard")
@login_required
def dashboard():
    today = date.today()
    data = {
        "active_clients": Client.query.filter_by(status="active").count(),
        "active_projects": Project.query.filter(Project.status.in_(["planning", "in progress", "revision", "waiting approval"])).count(),
        "today_tasks": Task.query.filter(Task.due_date == today).count(),
        "overdue": Task.query.filter(Task.due_date < today, Task.status != "done").count(),
        "revenue_estimate": sum(p.amount for p in Payment.query.filter(Payment.status != "overdue")),
        "recent_tasks": Task.query.order_by(Task.id.desc()).limit(5).all(),
        "upcoming_projects": Project.query.filter(Project.deadline >= today).order_by(Project.deadline.asc()).limit(5).all(),
    }
    ai_daily = [
        "Focus on overdue tasks before noon.",
        "Request approvals for content scheduled in the next 72 hours.",
        "Review pending payments to protect monthly cash flow.",
    ]
    return render_template("dashboard.html", data=data, ai_daily=ai_daily)


def role_required(*roles):
    def wrapper(fn):
        @wraps(fn)
        def inner(*args, **kwargs):
            if current_user.role not in roles:
                flash("Permission denied", "danger")
                return redirect(url_for("dashboard"))
            return fn(*args, **kwargs)
        return inner
    return wrapper


@app.route("/clients", methods=["GET", "POST"])
@login_required
def clients():
    if request.method == "POST":
        c = Client(
            company_name=request.form["company_name"],
            contact_name=request.form.get("contact_name"),
            whatsapp=request.form.get("whatsapp"),
            email=request.form.get("email"),
            website=request.form.get("website"),
            instagram=request.form.get("instagram"),
            facebook=request.form.get("facebook"),
            tiktok=request.form.get("tiktok"),
            linkedin=request.form.get("linkedin"),
            service_plan=request.form.get("service_plan"),
            monthly_fee=float(request.form.get("monthly_fee") or 0),
            status=request.form.get("status", "active"),
            notes=request.form.get("notes", ""),
        )
        db.session.add(c)
        db.session.commit()
        flash("Client created", "success")
    q = request.args.get("q", "")
    status = request.args.get("status", "")
    query = Client.query
    if q:
        query = query.filter(Client.company_name.ilike(f"%{q}%"))
    if status:
        query = query.filter_by(status=status)
    return render_template("clients.html", clients=query.order_by(Client.id.desc()).all())


@app.route("/clients/<int:cid>/delete", methods=["POST"])
@login_required
@role_required("Admin", "Designer", "Social media manager", "Developer")
def delete_client(cid):
    db.session.delete(Client.query.get_or_404(cid))
    db.session.commit()
    return redirect(url_for("clients"))


@app.route("/projects", methods=["GET", "POST"])
@login_required
def projects():
    if request.method == "POST":
        p = Project(name=request.form["name"], category=request.form["category"], status=request.form["status"], priority=request.form["priority"], deadline=parse_date(request.form.get("deadline")), responsible=request.form.get("responsible"), comments=request.form.get("comments"), checklist=request.form.get("checklist"), client_id=int(request.form["client_id"]))
        db.session.add(p)
        db.session.commit()
    return render_template("projects.html", projects=Project.query.order_by(Project.id.desc()).all(), clients=Client.query.all())


@app.route("/tasks", methods=["GET", "POST"])
@login_required
def tasks():
    if request.method == "POST":
        t = Task(title=request.form["title"], description=request.form.get("description"), status=request.form["status"], priority=request.form["priority"], responsible=request.form.get("responsible"), due_date=parse_date(request.form.get("due_date")), tags=request.form.get("tags"), client_id=int(request.form["client_id"]) if request.form.get("client_id") else None, project_id=int(request.form["project_id"]) if request.form.get("project_id") else None)
        db.session.add(t)
        db.session.commit()
    view = request.args.get("view", "table")
    return render_template("tasks.html", tasks=Task.query.order_by(Task.id.desc()).all(), clients=Client.query.all(), projects=Project.query.all(), view=view)


@app.route("/content", methods=["GET", "POST"])
@login_required
def content_calendar():
    if request.method == "POST":
        row = ContentCalendar(client_id=int(request.form["client_id"]), platform=request.form["platform"], content_type=request.form["content_type"], publish_date=parse_date(request.form["publish_date"]), caption=request.form.get("caption"), creative_briefing=request.form.get("creative_briefing"), references=request.form.get("references"), approval_status=request.form["approval_status"])
        db.session.add(row)
        db.session.commit()
    return render_template("content.html", items=ContentCalendar.query.order_by(ContentCalendar.publish_date.asc()).all(), clients=Client.query.all())


@app.route("/website", methods=["GET", "POST"])
@login_required
def website_manager():
    if request.method == "POST":
        w = WebsiteProject(client_id=int(request.form["client_id"]), domain=request.form["domain"], hosting_provider=request.form.get("hosting_provider"), cms_platform=request.form.get("cms_platform"), admin_url=request.form.get("admin_url"), project_stage=request.form.get("project_stage"), seo_checklist=request.form.get("seo_checklist"), performance_checklist=request.form.get("performance_checklist"), security_checklist=request.form.get("security_checklist"), backup_status=request.form.get("backup_status"), maintenance_notes=request.form.get("maintenance_notes"))
        db.session.add(w)
        db.session.commit()
    return render_template("website.html", items=WebsiteProject.query.order_by(WebsiteProject.id.desc()).all(), clients=Client.query.all())


@app.route("/metrics", methods=["GET", "POST"])
@login_required
def metrics():
    if request.method == "POST":
        m = Metric(client_id=int(request.form["client_id"]), month=request.form["month"], followers=int(request.form.get("followers") or 0), reach=int(request.form.get("reach") or 0), impressions=int(request.form.get("impressions") or 0), engagement=int(request.form.get("engagement") or 0), clicks=int(request.form.get("clicks") or 0), leads=int(request.form.get("leads") or 0), conversions=int(request.form.get("conversions") or 0), website_traffic=int(request.form.get("website_traffic") or 0))
        db.session.add(m)
        db.session.commit()
    rows = Metric.query.order_by(Metric.month.desc()).all()
    insight = "Post consistency should improve where reach rose but conversions stayed flat."
    return render_template("metrics.html", rows=rows, clients=Client.query.all(), insight=insight)


@app.route("/finance", methods=["GET", "POST"])
@login_required
def finance():
    if request.method == "POST":
        row = Payment(client_id=int(request.form["client_id"]), kind=request.form["kind"], amount=float(request.form["amount"]), status=request.form["status"], due_date=parse_date(request.form.get("due_date")), notes=request.form.get("notes"))
        db.session.add(row)
        db.session.commit()
    payments = Payment.query.order_by(Payment.id.desc()).all()
    return render_template("finance.html", payments=payments, clients=Client.query.all())


@app.route("/files", methods=["GET", "POST"])
@login_required
def files_page():
    if request.method == "POST":
        file = request.files.get("file")
        if file and file.filename:
            filename = secure_filename(file.filename)
            path = os.path.join(app.config["UPLOAD_FOLDER"], filename)
            file.save(path)
            row = File(filename=filename, filepath=path, notes=request.form.get("notes", ""), client_id=int(request.form["client_id"]) if request.form.get("client_id") else None, project_id=int(request.form["project_id"]) if request.form.get("project_id") else None)
            db.session.add(row)
            db.session.commit()
    return render_template("files.html", files=File.query.order_by(File.id.desc()).all(), clients=Client.query.all(), projects=Project.query.all())


@app.route("/reports", methods=["GET", "POST"])
@login_required
def reports():
    if request.method == "POST":
        cid = int(request.form["client_id"])
        month = request.form["month"]
        completed_tasks = Task.query.filter_by(client_id=cid, status="done").count()
        published = ContentCalendar.query.filter_by(client_id=cid, approval_status="published").count()
        metric = Metric.query.filter_by(client_id=cid, month=month).first()
        summary = f"Completed tasks: {completed_tasks}. Published content: {published}. Conversions: {metric.conversions if metric else 0}."
        rep = Report(client_id=cid, month=month, summary=summary)
        db.session.add(rep)
        db.session.commit()
    return render_template("reports.html", reports=Report.query.order_by(Report.id.desc()).all(), clients=Client.query.all())


@app.route("/reports/<int:rid>/pdf")
@login_required
def report_pdf(rid):
    report = Report.query.get_or_404(rid)
    pdf = FPDF()
    pdf.add_page()
    pdf.set_font("Helvetica", size=12)
    pdf.multi_cell(0, 8, f"Pacaembu OS Report\nClient: {report.client.company_name}\nMonth: {report.month}\n\nSummary:\n{report.summary}")
    path = f"/tmp/report-{rid}.pdf"
    pdf.output(path)
    return send_file(path, as_attachment=True, download_name=f"report-{rid}.pdf")


@app.route("/approvals", methods=["GET", "POST"])
@login_required
def approvals():
    if request.method == "POST":
        row = Approval(client_id=int(request.form["client_id"]), item_type=request.form["item_type"], item_id=int(request.form["item_id"]), status=request.form["status"], notes=request.form.get("notes"))
        db.session.add(row)
        db.session.commit()
    rows = Approval.query.order_by(Approval.id.desc()).all()
    if current_user.role == "Client":
        rows = [r for r in rows if r.client_id == 1]
    return render_template("approvals.html", rows=rows, clients=Client.query.all())


@app.route("/assistant", methods=["GET", "POST"])
@login_required
def assistant():
    answer = None
    if request.method == "POST":
        prompt = request.form["prompt"]
        overdue = Task.query.filter(Task.due_date < date.today(), Task.status != "done").count()
        pending_payments = Payment.query.filter_by(status="pending").count()
        answer = f"Priority now: resolve {overdue} overdue tasks and follow up {pending_payments} pending payments. Suggested content theme: client success stories + before/after visuals."
        db.session.add(AILog(user_id=current_user.id, prompt=prompt, response=answer))
        db.session.commit()
    logs = AILog.query.order_by(AILog.id.desc()).limit(10).all()
    return render_template("assistant.html", answer=answer, logs=logs)


@app.route("/search")
@login_required
def global_search():
    q = request.args.get("q", "").strip()
    results = {
        "clients": Client.query.filter(Client.company_name.ilike(f"%{q}%")).all() if q else [],
        "projects": Project.query.filter(Project.name.ilike(f"%{q}%")).all() if q else [],
        "tasks": Task.query.filter(Task.title.ilike(f"%{q}%")).all() if q else [],
    }
    return render_template("search.html", q=q, results=results)


if __name__ == "__main__":
    with app.app_context():
        db.create_all()
        if not is_installed():
            INSTALL_LOCK.write_text("dev-mode", encoding="utf-8")
            if User.query.count() == 0:
                db.session.add(User(name="Admin", email="admin@local", role="Admin", password_hash=generate_password_hash("admin123")))
                db.session.commit()
            seed_once()
    app.run(debug=True, host="0.0.0.0", port=5000)
