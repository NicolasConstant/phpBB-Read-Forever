using System;
using System.IO;
using System.Linq;
using System.Net;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using MySql.Data.MySqlClient;
using Newtonsoft.Json;

namespace ReadForeverTests
{
    [TestClass]
    public class RfApiTests
    {
        private const string ConnectionString = "SERVER=127.0.0.1; DATABASE=read_forever; UID=root; PASSWORD=";
        private const string EndpointAdress = "http://localhost/api/";
        private const string UserEndpoint = EndpointAdress + "rf-authentication.php";
        private const string ListEndpoint = EndpointAdress + "rf-blacklist.php";
        
        [ClassInitialize()]
        public static void ClassInit(TestContext context)
        {
            var script = File.ReadAllText("database_test.sql").Replace("\r\n", string.Empty);
            var instructions = script.Split(';').Where(x => !string.IsNullOrWhiteSpace(x)).Select(x => x);

            foreach (var instruction in instructions)
            {
                var connection = new MySqlConnection(ConnectionString);

                connection.Open();
                var cmd = connection.CreateCommand();
                cmd.CommandText = instruction;
                cmd.ExecuteNonQuery();
                connection.Close();
            }
        }

        [TestMethod]
        public void CreateNewUser_NameAvailable()
        {
            var endpointCall = UserEndpoint + "?username=TestingGuy";
            var result = PostData(endpointCall, "");
            Assert.IsNotNull(result);
            Assert.IsTrue(result.Contains("{\"apikey\":"));
        }

        [TestMethod]
        [ExpectedException(typeof(WebException))]
        public void CreateNewUser_NameUnavailable()
        {
            var endpointCall = UserEndpoint + "?username=dada";
            PostData(endpointCall, "");
        }

        [TestMethod]
        public void Connect_ValidUser_ValidApiKey()
        {
            var endpointCall = UserEndpoint + "?username=dada&apikey=A1Z2e3r4t5";
            var result = GetData(endpointCall);
            Assert.AreEqual("{\"isAuthenticated\":true}", result);
        }

        [TestMethod]
        public void Connect_UnvalidUser_ValidApiKey()
        {
            var endpointCall = UserEndpoint + "?username=unknown&apikey=A1Z2e3r4t5";
            var result = GetData(endpointCall);
            Assert.AreEqual("{\"isAuthenticated\":false}", result);
        }

        [TestMethod]
        public void Connect_ValidUser_UnvalidApiKey()
        {
            var endpointCall = UserEndpoint + "?username=DaDa&apikey=blablabla";
            var result = GetData(endpointCall);
            Assert.AreEqual("{\"isAuthenticated\":false}", result);
        }

        [TestMethod]
        public void GetList_ValidUser_ValidApiKey()
        {
            var endpointCall = ListEndpoint + "?username=DadA&apikey=A1Z2e3r4t5";
            var result = GetData(endpointCall);
            Assert.AreEqual("{\"blacklist\":[\"Mon premier topic\",\"Mon deuxième topic\",\"Mon troisième topic\"]}", result);
        }

        [TestMethod]
        [ExpectedException(typeof(WebException))]
        public void GetList_UnvalidUser_ValidApiKey()
        {
            var endpointCall = ListEndpoint + "?username=456789&apikey=777";
            GetData(endpointCall);
        }

        [TestMethod]
        [ExpectedException(typeof(WebException))]
        public void GetList_ValidUser_UnvalidApiKey()
        {
            var endpointCall = ListEndpoint + "?username=dada&apikey=dsqds";
            GetData(endpointCall);
        }

        [TestMethod]
        public void AddToList_ValidUser_ValidApiKey()
        {
            var body = "{\"topics\":[\"Added 1\",\"Added 2\",\"Added Topac Français éèà\"]}";
            var endpointCall = ListEndpoint + "?username=dada&apikey=A1Z2e3r4t5&operation=add";
            PostData(endpointCall, body);

            var endpointCall2 = ListEndpoint + "?username=dada&apikey=A1Z2e3r4t5";
            var result = GetData(endpointCall2);
            Assert.AreEqual("{\"blacklist\":[\"Mon premier topic\",\"Mon deuxième topic\",\"Mon troisième topic\",\"Added 1\",\"Added 2\",\"Added Topac Français éèà\"]}", result);
        }

        [TestMethod]
        public void AddToList_ValidUser_ValidApiKey_TopicAlreadyPresent()
        {
            var body = "{\"topics\":[\"Already present 1\"]}";
            var endpointCall = ListEndpoint + "?username=John&apikey=123&operation=add";
            PostData(endpointCall, body);

            var endpointCall2 = ListEndpoint + "?username=John&apikey=123";
            var result = GetData(endpointCall2);
            Assert.AreEqual("{\"blacklist\":[\"Already present 1\"]}", result);
        }

        [TestMethod]
        public void RemoveFromList_ValidUser_ValidApiKey()
        {
            var body = "{\"topics\":[\"Mon topic\",\"Mon topic 2\"]}";
            var endpointCall = ListEndpoint + "?username=Bob&apikey=123456&operation=remove";
            PostData(endpointCall, body);

            var endpointCall2 = ListEndpoint + "?username=Bob&apikey=123456";
            var result = GetData(endpointCall2);
            Assert.AreEqual("{\"blacklist\":[\"Mon troisième topic\",\"Mon topic 3\"]}", result);
        }

        [TestMethod]
        public void CompleteScenario()
        {
            //Create new user
            var pseudo = "NewGuy";
            var createNewUserCall = UserEndpoint + "?username=" + pseudo;
            var apiKeyResponse = PostData(createNewUserCall, "");
            var apikey = JsonConvert.DeserializeObject<ApiKeyResponse>(apiKeyResponse).apikey;

            //Add topics
            var addbody = "{\"topics\":[\"Added 32\",\"Added 33\",\"Added 34\"]}";
            var addCall = ListEndpoint + $"?username={pseudo}&apikey={apikey}&operation=add";
            PostData(addCall, addbody);

            //Remove topics
            var removebody = "{\"topics\":[\"Added 33\",\"Added 34\"]}";
            var removeCall = ListEndpoint + $"?username={pseudo}&apikey={apikey}&operation=remove";
            PostData(removeCall, removebody);

            //Get all data
            var getListcall = ListEndpoint + $"?username={pseudo}&apikey={apikey}";
            var result = GetData(getListcall);
            Assert.AreEqual("{\"blacklist\":[\"Added 32\"]}", result);
        }

        private class ApiKeyResponse
        {
            public string apikey { get; set; }
        }

        private string PostData(string endpoint, string json)
        {
            var httpWebRequest = (HttpWebRequest)WebRequest.Create(endpoint);
            httpWebRequest.ContentType = "application/json";
            httpWebRequest.Method = "POST";

            using (var streamWriter = new StreamWriter(httpWebRequest.GetRequestStream()))
            {
                streamWriter.Write(json);
                streamWriter.Flush();
                streamWriter.Close();
            }

            var httpResponse = (HttpWebResponse)httpWebRequest.GetResponse();
            using (var streamReader = new StreamReader(httpResponse.GetResponseStream()))
            {
                var result = streamReader.ReadToEnd();
                return result;
            }
        }

        private string GetData(string endpoint)
        {
            var httpWebRequest = (HttpWebRequest)WebRequest.Create(endpoint);
            httpWebRequest.ContentType = "application/json";
            httpWebRequest.Method = "GET";

            var httpResponse = (HttpWebResponse)httpWebRequest.GetResponse();
            using (var streamReader = new StreamReader(httpResponse.GetResponseStream()))
            {
                var result = streamReader.ReadToEnd();
                return result;
            }
        }
    }
}
