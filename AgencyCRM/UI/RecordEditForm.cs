using System;
using System.Collections.Generic;
using System.Data.SQLite;
using System.Windows.Forms;
using AgencyCRM.Data;

namespace AgencyCRM.UI
{
    public class RecordEditForm : Form
    {
        private readonly string _table;
        private readonly Dictionary<string, TextBox> _fields = new Dictionary<string, TextBox>();

        public RecordEditForm(string table, string[] columns)
        {
            _table = table;
            Text = "Add " + table;
            Width = 600;
            Height = 500;
            var panel = new TableLayoutPanel { Dock = DockStyle.Fill, AutoScroll = true, ColumnCount = 2 };
            foreach (var col in columns)
            {
                panel.Controls.Add(new Label { Text = col, AutoSize = true });
                var txt = new TextBox { Width = 350 };
                _fields[col] = txt;
                panel.Controls.Add(txt);
            }
            var btn = new Button { Text = "Save", Dock = DockStyle.Bottom };
            btn.Click += SaveClick;
            Controls.Add(panel);
            Controls.Add(btn);
        }

        private void SaveClick(object sender, EventArgs e)
        {
            var cols = string.Join(",", _fields.Keys);
            var pars = string.Join(",", _fields.Keys);
            var sql = $"INSERT INTO {_table}({cols}) VALUES(@{pars.Replace(",",",@")})";
            var sqliteParams = new List<SQLiteParameter>();
            foreach (var kv in _fields) sqliteParams.Add(new SQLiteParameter("@" + kv.Key, kv.Value.Text));
            Database.Execute(sql, sqliteParams.ToArray());
            DialogResult = DialogResult.OK;
            Close();
        }
    }
}
