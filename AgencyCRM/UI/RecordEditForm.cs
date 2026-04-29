using System;
using System.Collections.Generic;
using System.Windows.Forms;
using AgencyCRM.Services;

namespace AgencyCRM.UI
{
    public class RecordEditForm : Form
    {
        private readonly string _table;
        private readonly int? _id;
        private readonly CrudService _crud = new CrudService();
        private readonly Dictionary<string, TextBox> _fields = new Dictionary<string, TextBox>();

        public RecordEditForm(string table, string[] columns, Dictionary<string, string> initialValues = null)
        {
            _table = table;
            _id = initialValues != null && initialValues.ContainsKey("Id") ? int.Parse(initialValues["Id"]) : (int?)null;
            Text = (_id.HasValue ? "Edit " : "Add ") + table;
            Width = 640;
            Height = 560;

            var panel = new TableLayoutPanel { Dock = DockStyle.Fill, AutoScroll = true, ColumnCount = 2 };
            panel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 30));
            panel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 70));
            foreach (var col in columns)
            {
                panel.Controls.Add(new Label { Text = col, AutoSize = true, Padding = new Padding(0, 8, 0, 0) });
                var txt = new TextBox { Width = 380, Text = initialValues != null && initialValues.ContainsKey(col) ? initialValues[col] : string.Empty };
                _fields[col] = txt;
                panel.Controls.Add(txt);
            }

            var btn = new Button { Text = "Save", Dock = DockStyle.Bottom, Height = 36 };
            btn.Click += SaveClick;
            Controls.Add(panel);
            Controls.Add(btn);
        }

        private void SaveClick(object sender, EventArgs e)
        {
            if (_fields.ContainsKey("Name") && string.IsNullOrWhiteSpace(_fields["Name"].Text))
            {
                MessageBox.Show("Name is required.");
                return;
            }

            var values = new Dictionary<string, string>();
            foreach (var kv in _fields) values[kv.Key] = kv.Value.Text.Trim();

            if (_id.HasValue) _crud.Update(_table, _id.Value, values);
            else _crud.Insert(_table, values);

            DialogResult = DialogResult.OK;
            Close();
        }
    }
}
