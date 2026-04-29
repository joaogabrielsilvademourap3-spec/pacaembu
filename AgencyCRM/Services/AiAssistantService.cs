using System.Net.Http;
using System.Text;
using System.Threading.Tasks;

namespace AgencyCRM.Services
{
    public class AiAssistantService
    {
        public async Task<string> AskAsync(string apiUrl, string apiKey, string prompt)
        {
            if (string.IsNullOrWhiteSpace(apiKey)) return "AI is not configured. Add an API key in Settings.";
            using (var client = new HttpClient())
            {
                client.DefaultRequestHeaders.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", apiKey);
                var body = "{\"model\":\"gpt-4o-mini\",\"messages\":[{\"role\":\"user\",\"content\":\"" + prompt.Replace("\"", "'") + "\"}]}";
                var response = await client.PostAsync(apiUrl, new StringContent(body, Encoding.UTF8, "application/json"));
                return await response.Content.ReadAsStringAsync();
            }
        }
    }
}
