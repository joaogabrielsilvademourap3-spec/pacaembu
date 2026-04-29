using System.Data;
using AgencyCRM.Data;

namespace AgencyCRM.Services
{
    public class CrudService
    {
        public DataTable List(string table) => Database.Query($"SELECT * FROM {table} ORDER BY Id DESC");
        public void Delete(string table, int id) => Database.Execute($"DELETE FROM {table} WHERE Id=@id", new System.Data.SQLite.SQLiteParameter("@id", id));
    }
}
