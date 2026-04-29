using System.Collections.Generic;
using System.Data;
using System.Data.SQLite;
using AgencyCRM.Data;

namespace AgencyCRM.Services
{
    public class CrudService
    {
        public DataTable List(string table) => Database.Query($"SELECT * FROM {table} ORDER BY Id DESC");

        public int Insert(string table, Dictionary<string, string> values)
        {
            var columns = string.Join(",", values.Keys);
            var placeholders = "@" + string.Join(",@", values.Keys);
            var sql = $"INSERT INTO {table}({columns}) VALUES({placeholders})";
            var parameters = new List<SQLiteParameter>();
            foreach (var kv in values) parameters.Add(new SQLiteParameter("@" + kv.Key, kv.Value));
            return Database.Execute(sql, parameters.ToArray());
        }

        public int Update(string table, int id, Dictionary<string, string> values)
        {
            var sets = new List<string>();
            var parameters = new List<SQLiteParameter>();
            foreach (var kv in values)
            {
                sets.Add(kv.Key + "=@" + kv.Key);
                parameters.Add(new SQLiteParameter("@" + kv.Key, kv.Value));
            }
            parameters.Add(new SQLiteParameter("@Id", id));
            var sql = $"UPDATE {table} SET {string.Join(",", sets)} WHERE Id=@Id";
            return Database.Execute(sql, parameters.ToArray());
        }

        public void Delete(string table, int id) => Database.Execute($"DELETE FROM {table} WHERE Id=@id", new SQLiteParameter("@id", id));
    }
}
