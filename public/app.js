const state = { token: localStorage.getItem('token'), user: JSON.parse(localStorage.getItem('user')||'null'), module:'dashboard', data:{} };
const api = async (url, opts={}) => {
  const headers = { 'Content-Type':'application/json', ...(opts.headers||{}) };
  if (state.token) headers.Authorization = `Bearer ${state.token}`;
  const res = await fetch(url,{...opts,headers});
  if (!res.ok) throw new Error((await res.json()).error || 'Request failed');
  return res.headers.get('content-type')?.includes('application/pdf') ? res.blob() : res.json();
};

const modules = ['dashboard','clients','projects','tasks','content_calendar','website_projects','metrics','payments','files','approvals','reports','ai'];
const app = document.getElementById('app');

function toast(msg){alert(msg)}
function statusColor(p){return p==='high'?'high':p==='low'?'low':'medium'}

function authView(){
  app.innerHTML=`<div class='auth-wrap'><div class='card auth-card'><h2>Pacaembu OS</h2><p>Agency operations hub</p>
  <div class='grid2'><button id='showLogin'>Login</button><button id='showReg'>Register</button></div>
  <form id='authForm' class='panel'>
    <input name='name' placeholder='Name (register only)' />
    <input name='email' type='email' placeholder='Email' required />
    <input name='password' type='password' placeholder='Password' required />
    <select name='role'><option>admin</option><option>designer</option><option>social media manager</option><option>copywriter</option><option>developer</option><option>client</option></select>
    <button type='submit'>Continue</button>
  </form><small>Demo admin: admin@pacaembu.app / admin123</small></div></div>`;
  let mode='login';
  authForm.name.style.display='none';authForm.role.style.display='none';
  showLogin.onclick=()=>{mode='login';authForm.name.style.display='none';authForm.role.style.display='none'};
  showReg.onclick=()=>{mode='register';authForm.name.style.display='block';authForm.role.style.display='block'};
  authForm.onsubmit=async(e)=>{e.preventDefault();const f=new FormData(authForm);const body=Object.fromEntries(f.entries());
    try{if(mode==='register') await api('/api/auth/register',{method:'POST',body:JSON.stringify(body)});
      const r=await api('/api/auth/login',{method:'POST',body:JSON.stringify({email:body.email,password:body.password})});
      state.token=r.token;state.user=r.user;localStorage.setItem('token',r.token);localStorage.setItem('user',JSON.stringify(r.user)); render();
    }catch(err){toast(err.message)}
  }
}

function layout(content){
  app.innerHTML=`<div class='layout'><aside class='sidebar'><div class='brand'>Pacaembu OS</div><div class='nav'>
  ${modules.map(m=>`<button data-m='${m}'>${m.replace('_',' ')}</button>`).join('')}
  <button id='logout'>Logout</button></div></aside>
  <section><div class='topbar'><input id='globalSearch' placeholder='Global search...' /><div>${state.user.name} (${state.user.role})</div></div>
  <main class='main'>${content}</main></section></div>`;
  document.querySelectorAll('[data-m]').forEach(b=>b.onclick=()=>{state.module=b.dataset.m;render()});
  logout.onclick=()=>{localStorage.clear();state.token=null;state.user=null;render()};
}

async function dashboard(){
  const s = await api('/api/dashboard/summary');
  return `<div class='tiles'>
    <div class='tile'><h3>${s.activeClients}</h3><small>Active Clients</small></div>
    <div class='tile'><h3>${s.activeProjects}</h3><small>Active Projects</small></div>
    <div class='tile'><h3>${s.dueToday}</h3><small>Due Today</small></div>
    <div class='tile'><h3>${s.overdue}</h3><small>Overdue</small></div>
    <div class='tile'><h3>$${Number(s.revenue).toFixed(2)}</h3><small>Revenue Estimate</small></div>
  </div>
  <div class='card panel'><h3>AI Daily Suggestions</h3><ul>${s.aiSuggestions.map(x=>`<li>${x}</li>`).join('')}</ul></div>`;
}

async function crudModule(name, fields){
  const rows = await api(`/api/${name}`);
  return `<div class='card'><h3>${name.replace('_',' ')}</h3>
    <form id='f-${name}' class='grid2'>${fields.map(f=>`<input name='${f}' placeholder='${f}'/>`).join('')}<button>Add</button></form>
    <div style='overflow:auto'><table><thead><tr>${['id',...fields,'actions'].map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>
      ${rows.map(r=>`<tr>${['id',...fields].map(k=>`<td>${r[k]??''}</td>`).join('')}<td><button onclick="del('${name}',${r.id})">Delete</button></td></tr>`).join('')}
    </tbody></table></div></div>`;
}

window.del = async (name,id)=>{await api(`/api/${name}/${id}`,{method:'DELETE'});render()}

async function tasksModule(){
  const rows = await api('/api/tasks');
  const table=`<table><thead><tr><th>title</th><th>status</th><th>priority</th><th>due_date</th><th>actions</th></tr></thead><tbody>
    ${rows.map(t=>`<tr><td>${t.title}</td><td>${t.status}</td><td><span class='badge ${statusColor(t.priority)}'>${t.priority}</span></td><td>${t.due_date||''}</td><td><button onclick='del("tasks",${t.id})'>Delete</button></td></tr>`).join('')}
  </tbody></table>`;
  const cols=['todo','in progress','waiting approval','done'];
  const kanban=`<div class='kanban'>${cols.map(c=>`<div class='column'><h4>${c}</h4>${rows.filter(r=>r.status===c).map(r=>`<div class='tile'><b>${r.title}</b><br/><small>${r.due_date||''}</small></div>`).join('')}</div>`).join('')}</div>`;
  return `<div class='card'><h3>Tasks</h3><form id='f-tasks' class='grid2'>
    <input name='title' placeholder='title' required/><input name='description' placeholder='description'/>
    <input name='status' placeholder='status'/><input name='priority' placeholder='priority'/>
    <input name='due_date' type='date'/><button>Add task</button></form>
    <div class='view-switch'><button onclick='switchView("table")'>Table</button><button onclick='switchView("kanban")'>Kanban</button><button onclick='switchView("calendar")'>Calendar</button><button onclick='switchView("timeline")'>Timeline</button></div>
    <div id='taskView'>${table}</div>
  </div>
  <script>window.switchView=(v)=>{const el=document.getElementById('taskView'); if(v==='table') el.innerHTML=${JSON.stringify(table)}; if(v==='kanban') el.innerHTML=${JSON.stringify(kanban)}; if(v==='calendar') el.innerHTML='<div class="card">Calendar-style list by due date</div>'+${JSON.stringify(table)}; if(v==='timeline') el.innerHTML='<div class="card">Timeline (sorted due dates)</div>'+${JSON.stringify(table)};}</script>`;
}

async function filesModule(){
  const rows=await api('/api/files');
  return `<div class='card'><h3>File Manager</h3>
    <form id='fileUp' enctype='multipart/form-data' class='flex'><input type='file' name='file' required/><input name='client_id' placeholder='client id'/><input name='project_id' placeholder='project id'/><input name='notes' placeholder='notes'/><button>Upload</button></form>
    <table><thead><tr><th>file</th><th>notes</th><th>preview</th></tr></thead><tbody>${rows.map(f=>`<tr><td>${f.original_name||f.file_url}</td><td>${f.notes||''}</td><td>${f.file_path?`<a href='${f.file_path}' target='_blank'>Open</a>`:'-'}</td></tr>`).join('')}</tbody></table>
  </div>`;
}

async function reportsModule(){
  const clients = await api('/api/clients');
  return `<div class='card'><h3>Monthly Reports</h3><form id='reportGen' class='grid2'><select name='client'>${clients.map(c=>`<option value='${c.id}'>${c.company_name}</option>`)}</select><input name='month' placeholder='YYYY-MM' value='${new Date().toISOString().slice(0,7)}'/><button>Export PDF</button></form></div>`;
}

async function aiModule(){
  return `<div class='card'><h3>AI Assistant</h3><form id='aiForm'><textarea name='prompt' placeholder='What should I focus on today?'></textarea><button>Ask AI</button></form><pre id='aiOut'></pre></div>`;
}

async function render(){
  if(!state.token) return authView();
  const map={
    dashboard:()=>dashboard(),
    clients:()=>crudModule('clients',['company_name','contact_name','whatsapp','email','website','instagram','facebook','tiktok','linkedin','service_plan','monthly_fee','status','internal_notes']),
    projects:()=>crudModule('projects',['client_id','title','category','status','priority','deadline','responsible_user_id','comments']),
    tasks:()=>tasksModule(),
    content_calendar:()=>crudModule('content_calendar',['client_id','platform','content_type','publish_date','caption','creative_briefing','references','approval_status']),
    website_projects:()=>crudModule('website_projects',['client_id','domain','hosting_provider','cms_platform','admin_url','project_stage','backup_status','maintenance_notes']),
    metrics:()=>crudModule('metrics',['client_id','month','followers','reach','impressions','engagement','clicks','leads','conversions','website_traffic']),
    payments:()=>crudModule('payments',['client_id','description','amount','due_date','status','payment_type']),
    files:()=>filesModule(),
    approvals:()=>crudModule('approvals',['client_id','content_id','status','notes']),
    reports:()=>reportsModule(),
    ai:()=>aiModule()
  }
  let content='';
  try{content=await map[state.module]();}catch(e){content=`<div class='card'>${e.message}</div>`}
  layout(content);

  document.querySelectorAll("form[id^='f-']").forEach(form=>form.onsubmit=async e=>{e.preventDefault();const module=form.id.replace('f-','');const data=Object.fromEntries(new FormData(form).entries());await api(`/api/${module}`,{method:'POST',body:JSON.stringify(data)});render();});

  const fileUp=document.getElementById('fileUp');
  if(fileUp) fileUp.onsubmit=async e=>{e.preventDefault();const fd=new FormData(fileUp);const headers={};if(state.token)headers.Authorization=`Bearer ${state.token}`;await fetch('/api/files/upload',{method:'POST',headers,body:fd});render();}

  const aiForm=document.getElementById('aiForm');
  if(aiForm) aiForm.onsubmit=async e=>{e.preventDefault();const prompt=new FormData(aiForm).get('prompt');const r=await api('/api/ai/assist',{method:'POST',body:JSON.stringify({prompt})});document.getElementById('aiOut').textContent=r.response;}

  const reportGen=document.getElementById('reportGen');
  if(reportGen) reportGen.onsubmit=async e=>{e.preventDefault();const fd=new FormData(reportGen);const client=fd.get('client');const month=fd.get('month');const headers={};if(state.token)headers.Authorization=`Bearer ${state.token}`;const res=await fetch(`/api/reports/${client}/${month}/pdf`,{method:'POST',headers});const blob=await res.blob();const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=`report-${month}.pdf`;a.click();}
}

render();
