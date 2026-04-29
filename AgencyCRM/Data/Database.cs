using System;
using System.Data;
using System.Data.SQLite;
using System.IO;

namespace AgencyCRM.Data
{
    public static class Database
    {
        public static string AppFolder => Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "AgencyCRM");
        public static string DbPath => Path.Combine(AppFolder, "agencycrm.db");
        public static string ConnectionString => $"Data Source={DbPath};Version=3;";

        public static void Initialize()
        {
            Directory.CreateDirectory(AppFolder);
            if (!File.Exists(DbPath)) SQLiteConnection.CreateFile(DbPath);
            var schema = File.ReadAllText(Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "schema.sql"));
            using (var cn = new SQLiteConnection(ConnectionString))
            {
                cn.Open();
                using (var cmd = new SQLiteCommand(schema, cn)) cmd.ExecuteNonQuery();
            }
            SeedDemo();
        }

        public static DataTable Query(string sql)
        {
            using (var cn = new SQLiteConnection(ConnectionString))
            using (var da = new SQLiteDataAdapter(sql, cn))
            {
                var dt = new DataTable();
                da.Fill(dt);
                return dt;
            }
        }

        public static int Execute(string sql, params SQLiteParameter[] parameters)
        {
            using (var cn = new SQLiteConnection(ConnectionString))
            using (var cmd = new SQLiteCommand(sql, cn))
            {
                cn.Open();
                if (parameters != null) cmd.Parameters.AddRange(parameters);
                return cmd.ExecuteNonQuery();
            }
        }

        private static void SeedDemo()
        {
            var count = Convert.ToInt32(Query("SELECT COUNT(*) c FROM Clients").Rows[0]["c"]);
            if (count > 0) return;
            Execute("INSERT INTO Clients(Name,CompanyName,Status,Email,Phone,Notes) VALUES('Ana Costa','Blue Pixel','active','ana@bluepixel.com','+55 11 90000-0000','Demo client')");
            Execute("INSERT INTO Leads(Name,Source,Stage,EstimatedValue,NextFollowUpDate) VALUES('Carlos Rocha','instagram','new',5000,date('now','+3 day'))");
            Execute("INSERT INTO Projects(ClientId,Title,ProjectType,Status,Budget,StartDate,Deadline) VALUES(1,'Blue Pixel Website','Website','in progress',6500,date('now'),date('now','+30 day'))");
        }
    }
}
