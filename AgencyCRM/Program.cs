using System;
using System.Windows.Forms;
using AgencyCRM.Data;
using AgencyCRM.UI;

namespace AgencyCRM
{
    internal static class Program
    {
        [STAThread]
        private static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Database.Initialize();
            Application.Run(new MainForm());
        }
    }
}
