using System;
using System.Collections.Generic;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Windows.Forms;
using AgencyCRM.Data;
using AgencyCRM.Services;

namespace AgencyCRM.UI
{
    public class MainForm : Form
    {
        private readonly CrudService _crud = new CrudService();
        private readonly Dictionary<string, DataGridView> _grids = new Dictionary<string, DataGridView>();
        private readonly TextBox _aiPrompt = new TextBox { Multiline = true, Dock = DockStyle.Fill };
        private readonly TextBox _aiOutput = new TextBox { Multiline = true, Dock = DockStyle.Fill, ReadOnly = true };

        public MainForm()
        {
            Text = "Agency CRM - Offline Edition";
            Width = 1400;
            Height = 850;
            BackColor = Color.FromArgb(230, 236, 245);
            var tabs = new TabControl { Dock = DockStyle.Fill };
            Controls.Add(tabs);

            AddDashboard(tabs);
            AddCrudTab(tabs, "Clients", new[] { "Name", "CompanyName", "ContactPerson", "Phone", "WhatsApp", "Email", "Website", "SocialMediaLinks", "Address", "Notes", "Status" });
            AddCrudTab(tabs, "Leads", new[] { "Name", "Source", "Stage", "EstimatedValue", "NextFollowUpDate", "Notes" });
            AddCrudTab(tabs, "Projects", new[] { "ClientId", "Title", "Description", "ProjectType", "StartDate", "Deadline", "Status", "Budget", "InternalNotes", "AttachmentPaths" });
            AddCrudTab(tabs, "Tasks", new[] { "ClientId", "ProjectId", "Title", "Description", "Priority", "Status", "DueDate", "AssignedPerson", "ChecklistJson" });
            AddCrudTab(tabs, "CalendarEvents", new[] { "ClientId", "ProjectId", "Title", "EventDate", "EventType", "Notes" });
            AddCrudTab(tabs, "Proposals", new[] { "ClientId", "LeadId", "Title", "ServicesIncluded", "Description", "Price", "Discount", "TotalValue", "Status" });
            AddCrudTab(tabs, "Contracts", new[] { "ClientId", "ProjectId", "Title", "StartDate", "EndDate", "MonthlyValue", "Status", "RenewalReminderDate", "FilePath" });
            AddCrudTab(tabs, "FinancialEntries", new[] { "ClientId", "ProjectId", "Type", "Description", "Amount", "PaymentStatus", "DueDate", "PaymentMethod", "RecurringMonthly" });
            AddCrudTab(tabs, "SocialMediaPosts", new[] { "ClientId", "Platform", "PostTitle", "Caption", "Hashtags", "PostStatus", "ScheduledDate", "ApprovalStatus", "Notes" });
            AddCrudTab(tabs, "WebsiteMaintenance", new[] { "ClientId", "Domain", "HostingProvider", "CmsUsed", "WordpressLoginUrl", "RenewalDate", "SslExpirationDate", "MaintenanceNotes", "BackupDate", "UpdateNotes" });
            AddReportsTab(tabs);
            AddSearchTab(tabs);
            AddSettingsTab(tabs);
            AddAiTab(tabs);
        }
        private void AddDashboard(TabControl tabs){ var page=new TabPage("Dashboard"); var g=Database.Query("SELECT (SELECT COUNT(*) FROM Clients WHERE Status='active') ActiveClients,(SELECT COUNT(*) FROM Projects WHERE Status IN ('planning','in progress')) ActiveProjects,(SELECT COUNT(*) FROM Tasks WHERE Status!='completed') PendingTasks,(SELECT IFNULL(SUM(Amount),0) FROM FinancialEntries WHERE Type='income') Revenue"); var lbl=new Label{Dock=DockStyle.Fill,Font=new Font("Segoe UI",12),Text=$"Active Clients: {g.Rows[0][0]}\nActive Projects: {g.Rows[0][1]}\nPending Tasks: {g.Rows[0][2]}\nRevenue: {g.Rows[0][3]}"}; page.Controls.Add(lbl); tabs.TabPages.Add(page);}        
        private void AddCrudTab(TabControl tabs,string table,string[] cols){var page=new TabPage(table);var grid=new DataGridView{Dock=DockStyle.Fill,ReadOnly=true,AutoSizeColumnsMode=DataGridViewAutoSizeColumnsMode.Fill};_grids[table]=grid;var top=new FlowLayoutPanel{Dock=DockStyle.Top,Height=40};var add=new Button{Text="Add"};var del=new Button{Text="Delete"};var refb=new Button{Text="Refresh"};add.Click+=(s,e)=>{if(new RecordEditForm(table,cols).ShowDialog()==DialogResult.OK)LoadTable(table);};del.Click+=(s,e)=>{if(grid.CurrentRow==null)return; if(MessageBox.Show("Delete selected?","Confirm",MessageBoxButtons.YesNo)==DialogResult.Yes){_crud.Delete(table,Convert.ToInt32(grid.CurrentRow.Cells["Id"].Value));LoadTable(table);}};refb.Click+=(s,e)=>LoadTable(table);top.Controls.Add(add);top.Controls.Add(del);top.Controls.Add(refb);page.Controls.Add(grid);page.Controls.Add(top);tabs.TabPages.Add(page);LoadTable(table);}        
        private void AddReportsTab(TabControl tabs){var p=new TabPage("Reports");var box=new TextBox{Dock=DockStyle.Fill,Multiline=true};var b=new Button{Text="Generate",Dock=DockStyle.Top};b.Click+=(s,e)=>{var dt=Database.Query("SELECT Status,COUNT(*) Total FROM Projects GROUP BY Status");box.Text="Project Status Report\r\n"+string.Join("\r\n",dt.Rows.Cast<DataRow>().Select(r=>$"{r[0]}: {r[1]}"));};p.Controls.Add(box);p.Controls.Add(b);tabs.TabPages.Add(p);}        
        private void AddSearchTab(TabControl tabs){var p=new TabPage("Search");var q=new TextBox{Dock=DockStyle.Top};var g=new DataGridView{Dock=DockStyle.Fill};var b=new Button{Text="Search",Dock=DockStyle.Top};b.Click+=(s,e)=>g.DataSource=Database.Query($"SELECT 'Client' Type,Name,Notes FROM Clients WHERE Name LIKE '%{q.Text.Replace("'","''")}% ' UNION ALL SELECT 'Lead',Name,Notes FROM Leads WHERE Name LIKE '%{q.Text.Replace("'","''")}%'");p.Controls.Add(g);p.Controls.Add(b);p.Controls.Add(q);tabs.TabPages.Add(p);}        
        private void AddSettingsTab(TabControl tabs){var p=new TabPage("Settings");var t=new TextBox{Dock=DockStyle.Top,Text=GetSetting("OpenAiApiKey")};var b=new Button{Text="Save API Key",Dock=DockStyle.Top};b.Click+=(s,e)=>{Database.Execute("INSERT OR REPLACE INTO Settings(Key,Value) VALUES('OpenAiApiKey',@v)",new System.Data.SQLite.SQLiteParameter("@v",t.Text));MessageBox.Show("Saved");};p.Controls.Add(b);p.Controls.Add(t);tabs.TabPages.Add(p);}        
        private void AddAiTab(TabControl tabs){var p=new TabPage("AI Assistant");var split=new SplitContainer{Dock=DockStyle.Fill,Orientation=Orientation.Horizontal};var run=new Button{Text="Generate proposal text",Dock=DockStyle.Top};run.Click+=async(s,e)=>{var svc=new AiAssistantService();_aiOutput.Text=await svc.AskAsync("https://api.openai.com/v1/chat/completions",GetSetting("OpenAiApiKey"),_aiPrompt.Text);};split.Panel1.Controls.Add(_aiPrompt);split.Panel1.Controls.Add(run);split.Panel2.Controls.Add(_aiOutput);p.Controls.Add(split);tabs.TabPages.Add(p);}        
        private string GetSetting(string key){var dt=Database.Query($"SELECT Value FROM Settings WHERE Key='{key}'");return dt.Rows.Count==0?"":dt.Rows[0][0].ToString();}
        private void LoadTable(string table){_grids[table].DataSource=_crud.List(table);}    }
}
