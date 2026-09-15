let db, session;
function load(){try{db=JSON.parse(localStorage.getItem(KEY)||'null')}catch(e){db=null} if(!db) db=seed(); save()}
function save(){localStorage.setItem(KEY,JSON.stringify(db))}
function user(){return db.users.find(u=>u.id===session)||null}
function deptName(id){return (db.departments.find(d=>d.id===id)||{}).name||'\u2014'}
function empById(id){return db.employees.find(e=>e.id===id)}
function can(p){
  const u=user(); if(!u) return false;
  if(u.role==='admin') return true;
  if(u.role==='hr') return ['employees','departments','leaves','attendance','approve'].includes(p);
  if(u.role==='dept_manager') return ['employees','leaves','attendance','approve'].includes(p);
  if(u.role==='employee') return ['leaves','attendance','self'].includes(p);
  return false;
}
function visibleEmps(){
  const u=user();
  if(u.role==='admin'||u.role==='hr') return db.employees;
  if(u.role==='dept_manager') return db.employees.filter(e=>e.deptId===u.deptId);
  return db.employees.filter(e=>e.id===u.empId);
}
function flash(msg,ok=true){return '<div class="alert '+(ok?'ok':'err')+'">'+esc(msg)+'</div>'}
function loginAs(id){
  session=id; localStorage.setItem(KEY+'-session',id);
  document.getElementById('loginView').classList.add('hidden');
  document.getElementById('appView').classList.remove('hidden');
  const u=user();
  document.getElementById('whoName').textContent=u.name;
  document.getElementById('whoRole').textContent=ROLES[u.role];
  document.getElementById('av').textContent=u.name.slice(0,1);
  renderNav(); go('home');
}
function logout(){
  session=null; localStorage.removeItem(KEY+'-session');
  document.getElementById('appView').classList.add('hidden');
  document.getElementById('loginView').classList.remove('hidden');
  document.getElementById('sidebar').classList.remove('open');
}
function renderNav(){
  const items=[['home','\u0644\u0648\u062d\u0629 \u0627\u0644\u062a\u062d\u0643\u0645']];
  if(can('employees')||can('self')) items.push(['employees','\u0627\u0644\u0645\u0648\u0638\u0641\u0648\u0646']);
  if(can('departments')||user().role==='dept_manager') items.push(['departments','\u0627\u0644\u0623\u0642\u0633\u0627\u0645']);
  if(can('leaves')) items.push(['leaves','\u0627\u0644\u0625\u062c\u0627\u0632\u0627\u062a']);
  if(can('attendance')) items.push(['attendance','\u0627\u0644\u062d\u0636\u0648\u0631']);
  if(user().role==='admin') items.push(['users','\u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u0648\u0646']);
  items.push(['profile','\u062d\u0633\u0627\u0628\u064a']);
  document.getElementById('nav').innerHTML=items.map(function(it){return '<a data-k="'+it[0]+'" onclick="go(\''+it[0]+'\')">'+it[1]+'</a>'}).join('');
}
function mark(k){document.querySelectorAll('.nav a').forEach(function(a){a.classList.toggle('on',a.dataset.k===k)})}
function go(k){
  document.getElementById('sidebar').classList.remove('open');
  mark(k);
  const titles={home:'\u0644\u0648\u062d\u0629 \u0627\u0644\u062a\u062d\u0643\u0645',employees:'\u0627\u0644\u0645\u0648\u0638\u0641\u0648\u0646',departments:'\u0627\u0644\u0623\u0642\u0633\u0627\u0645',leaves:'\u0627\u0644\u0625\u062c\u0627\u0632\u0627\u062a',attendance:'\u0627\u0644\u062d\u0636\u0648\u0631',users:'\u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u0648\u0646',profile:'\u062d\u0633\u0627\u0628\u064a'};
  document.getElementById('pageTitle').textContent=titles[k]||'\u0645\u0648\u0627\u0631\u062f';
  const fn={home:viewHome,employees:viewEmps,departments:viewDeps,leaves:viewLeaves,attendance:viewAtt,users:viewUsers,profile:viewProfile}[k];
  document.getElementById('page').innerHTML=fn();
}
function viewHome(){
  const emps=visibleEmps().filter(function(e){return e.status==='active'});
  const leaves=db.leaves.filter(function(l){return l.status==='pending' && visibleEmps().some(function(e){return e.id===l.empId})});
  const att=db.attendance.filter(function(a){return a.date===today() && a.inn && visibleEmps().some(function(e){return e.id===a.empId})});
  return '<div class="grid stats"><div class="card"><div class="muted">\u0627\u0644\u0645\u0648\u0638\u0641\u0648\u0646</div><div class="stat">'+emps.length+'</div></div><div class="card"><div class="muted">\u0625\u062c\u0627\u0632\u0627\u062a \u0642\u064a\u062f \u0627\u0644\u0627\u0646\u062a\u0638\u0627\u0631</div><div class="stat">'+leaves.length+'</div></div><div class="card"><div class="muted">\u062d\u0636\u0648\u0631 \u0627\u0644\u064a\u0648\u0645</div><div class="stat">'+att.length+'</div></div></div><div class="card" style="margin-top:16px"><h3 style="margin-top:0">\u0645\u0631\u062d\u0628\u0627\u064b '+esc(user().name)+'</h3><p class="muted">\u0627\u0644\u062f\u0648\u0631: '+ROLES[user().role]+'</p></div>';
}
function viewEmps(){
  const rows=visibleEmps();
  const canEdit=user().role==='admin'||user().role==='hr';
  var html=(canEdit?'<div class="row" style="margin-bottom:12px"><button class="btn" onclick="empForm()">\u0645\u0648\u0638\u0641 \u062c\u062f\u064a\u062f</button></div>':'');
  html+='<div class="card wrap"><table><thead><tr><th>\u0627\u0644\u0631\u0642\u0645</th><th>\u0627\u0644\u0627\u0633\u0645</th><th>\u0627\u0644\u0642\u0633\u0645</th><th>\u0627\u0644\u0645\u0633\u0645\u0649</th><th>\u0627\u0644\u062c\u0648\u0627\u0644</th><th>\u0627\u0644\u062d\u0627\u0644\u0629</th>'+(canEdit?'<th></th>':'')+'</tr></thead><tbody>';
  rows.forEach(function(e){
    html+='<tr><td>'+esc(e.no)+'</td><td>'+esc(e.name)+'</td><td>'+esc(deptName(e.deptId))+'</td><td>'+esc(e.title)+'</td><td dir="ltr">'+esc(e.phone)+'</td><td><span class="badge '+e.status+'">'+(e.status==='active'?'\u0646\u0634\u0637':'\u0645\u0648\u0642\u0648\u0641')+'</span></td>';
    if(canEdit) html+='<td class="row"><button class="btn sm sec" onclick="empForm(\''+e.id+'\')">\u062a\u0639\u062f\u064a\u0644</button> <button class="btn sm danger" onclick="delEmp(\''+e.id+'\')">\u062d\u0630\u0641</button></td>';
    html+='</tr>';
  });
  return html+'</tbody></table></div>';
}
function empForm(id){
  const e=id?empById(id):{no:'',name:'',deptId:db.departments[0].id,title:'',email:'',phone:'',hired:today(),status:'active',balance:21};
  var opts=db.departments.map(function(d){return '<option value="'+d.id+'" '+(d.id===e.deptId?'selected':'')+'>'+esc(d.name)+'</option>'}).join('');
  document.getElementById('page').innerHTML='<div class="card"><h3>'+(id?'\u062a\u0639\u062f\u064a\u0644 \u0645\u0648\u0638\u0641':'\u0645\u0648\u0638\u0641 \u062c\u062f\u064a\u062f')+'</h3><div class="formgrid"><label>\u0627\u0644\u0627\u0633\u0645<input id="f_name" value="'+esc(e.name)+'"></label><label>\u0627\u0644\u0631\u0642\u0645 \u0627\u0644\u0648\u0638\u064a\u0641\u064a<input id="f_no" value="'+esc(e.no)+'"></label><label>\u0627\u0644\u0642\u0633\u0645<select id="f_dept">'+opts+'</select></label><label>\u0627\u0644\u0645\u0633\u0645\u0649<input id="f_title" value="'+esc(e.title)+'"></label><label>\u0627\u0644\u0628\u0631\u064a\u062f<input id="f_email" value="'+esc(e.email)+'" dir="ltr"></label><label>\u0627\u0644\u062c\u0648\u0627\u0644<input id="f_phone" value="'+esc(e.phone)+'" dir="ltr"></label><label>\u062a\u0627\u0631\u064a\u062e \u0627\u0644\u062a\u0639\u064a\u064a\u0646<input id="f_hired" type="date" value="'+esc(e.hired)+'"></label><label>\u0627\u0644\u062d\u0627\u0644\u0629<select id="f_status"><option value="active" '+(e.status==='active'?'selected':'')+'>\u0646\u0634\u0637</option><option value="inactive" '+(e.status==='inactive'?'selected':'')+'>\u0645\u0648\u0642\u0648\u0641</option></select></label><label>\u0631\u0635\u064a\u062f \u0627\u0644\u0625\u062c\u0627\u0632\u0629<input id="f_bal" type="number" value="'+(e.balance||21)+'"></label></div><div class="row"><button class="btn" onclick="saveEmp(\''+(id||'')+'\')">\u062d\u0641\u0638</button><button class="btn sec" onclick="go(\'employees\')">\u0625\u0644\u063a\u0627\u0621</button></div></div>';
}
function saveEmp(id){
  if(!(user().role==='admin'||user().role==='hr')) return;
  const rec={no:f_no.value.trim(),name:f_name.value.trim(),deptId:f_dept.value,title:f_title.value.trim(),email:f_email.value.trim(),phone:f_phone.value.trim(),hired:f_hired.value,status:f_status.value,balance:+f_bal.value||0};
  if(!rec.no||!rec.name||!rec.title){document.getElementById('page').insertAdjacentHTML('afterbegin',flash('\u0623\u0643\u0645\u0644 \u0627\u0644\u062d\u0642\u0648\u0644 \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629',false));return}
  if(id){const i=db.employees.findIndex(function(e){return e.id===id}); db.employees[i]=Object.assign({},db.employees[i],rec)}
  else db.employees.push(Object.assign({id:uid(),userId:null},rec));
  save(); go('employees');
}
function delEmp(id){
  if(!(user().role==='admin'||user().role==='hr')) return;
  if(!confirm('\u062d\u0630\u0641 \u0627\u0644\u0645\u0648\u0638\u0641\u061f')) return;
  db.employees=db.employees.filter(function(e){return e.id!==id});
  db.leaves=db.leaves.filter(function(l){return l.empId!==id});
  db.attendance=db.attendance.filter(function(a){return a.empId!==id});
  save(); go('employees');
}
function viewDeps(){
  const canEdit=can('departments');
  var html=(canEdit?'<div class="row" style="margin-bottom:12px"><button class="btn" onclick="depForm()">\u0642\u0633\u0645 \u062c\u062f\u064a\u062f</button></div>':'');
  html+='<div class="card wrap"><table><thead><tr><th>\u0627\u0644\u0642\u0633\u0645</th><th>\u0627\u0644\u0648\u0635\u0641</th><th>\u0627\u0644\u0645\u062f\u064a\u0631</th><th>\u0627\u0644\u0645\u0648\u0638\u0641\u0648\u0646</th>'+(canEdit?'<th></th>':'')+'</tr></thead><tbody>';
  db.departments.forEach(function(d){
    const mgr=empById(d.manager); const n=db.employees.filter(function(e){return e.deptId===d.id}).length;
    html+='<tr><td>'+esc(d.name)+'</td><td>'+esc(d.desc)+'</td><td>'+esc(mgr?mgr.name:'\u2014')+'</td><td>'+n+'</td>';
    if(canEdit) html+='<td class="row"><button class="btn sm sec" onclick="depForm(\''+d.id+'\')">\u062a\u0639\u062f\u064a\u0644</button> <button class="btn sm danger" onclick="delDep(\''+d.id+'\')">\u062d\u0630\u0641</button></td>';
    html+='</tr>';
  });
  return html+'</tbody></table></div>';
}
function depForm(id){
  const d=id?db.departments.find(function(x){return x.id===id}):{name:'',desc:'',manager:''};
  var opts='<option value="">\u0628\u062f\u0648\u0646</option>'+db.employees.map(function(e){return '<option value="'+e.id+'" '+(e.id===d.manager?'selected':'')+'>'+esc(e.name)+'</option>'}).join('');
  document.getElementById('page').innerHTML='<div class="card"><h3>'+(id?'\u062a\u0639\u062f\u064a\u0644 \u0642\u0633\u0645':'\u0642\u0633\u0645 \u062c\u062f\u064a\u062f')+'</h3><label>\u0627\u0633\u0645 \u0627\u0644\u0642\u0633\u0645<input id="d_name" value="'+esc(d.name)+'"></label><label>\u0648\u0635\u0641 \u0645\u062e\u062a\u0635\u0631<textarea id="d_desc">'+esc(d.desc)+'</textarea></label><label>\u0627\u0644\u0645\u062f\u064a\u0631<select id="d_mgr">'+opts+'</select></label><div class="row"><button class="btn" onclick="saveDep(\''+(id||'')+'\')">\u062d\u0641\u0638</button><button class="btn sec" onclick="go(\'departments\')">\u0625\u0644\u063a\u0627\u0621</button></div></div>';
}
function saveDep(id){
  if(!can('departments')) return;
  const rec={name:d_name.value.trim(),desc:d_desc.value.trim(),manager:d_mgr.value||null};
  if(!rec.name) return;
  if(id){const i=db.departments.findIndex(function(d){return d.id===id}); db.departments[i]=Object.assign({},db.departments[i],rec)}
  else db.departments.push(Object.assign({id:uid()},rec));
  save(); go('departments');
}
function delDep(id){
  if(!can('departments')) return;
  if(db.employees.some(function(e){return e.deptId===id})){alert('\u0644\u0627 \u064a\u0645\u0643\u0646 \u062d\u0630\u0641 \u0642\u0633\u0645 \u0645\u0631\u062a\u0628\u0637 \u0628\u0645\u0648\u0638\u0641\u064a\u0646');return}
  db.departments=db.departments.filter(function(d){return d.id!==id}); save(); go('departments');
}
function viewLeaves(){
  const list=db.leaves.filter(function(l){return visibleEmps().some(function(e){return e.id===l.empId})}).slice().reverse();
  var html='<div class="row" style="margin-bottom:12px"><button class="btn" onclick="leaveForm()">\u0637\u0644\u0628 \u0625\u062c\u0627\u0632\u0629</button></div><div class="card wrap"><table><thead><tr><th>\u0627\u0644\u0645\u0648\u0638\u0641</th><th>\u0627\u0644\u0642\u0633\u0645</th><th>\u0627\u0644\u0646\u0648\u0639</th><th>\u0645\u0646</th><th>\u0625\u0644\u0649</th><th>\u0627\u0644\u0623\u064a\u0627\u0645</th><th>\u0627\u0644\u062d\u0627\u0644\u0629</th><th></th></tr></thead><tbody>';
  list.forEach(function(l){
    const e=empById(l.empId)||{};
    var btns=(l.status==='pending'&&can('approve'))?'<button class="btn sm" onclick="reviewLeave(\''+l.id+'\',\'approved\')">\u0627\u0639\u062a\u0645\u0627\u062f</button> <button class="btn sm danger" onclick="reviewLeave(\''+l.id+'\',\'rejected\')">\u0631\u0641\u0636</button>':'';
    html+='<tr><td>'+esc(e.name)+'</td><td>'+esc(deptName(e.deptId))+'</td><td>'+LTYPE[l.type]+'</td><td>'+fmt(l.from)+'</td><td>'+fmt(l.to)+'</td><td>'+l.days+'</td><td><span class="badge '+l.status+'">'+LSTAT[l.status]+'</span></td><td class="row">'+btns+'</td></tr>';
  });
  return html+'</tbody></table></div>';
}
function leaveForm(){
  const mine=user().role==='employee';
  const opts=visibleEmps().filter(function(e){return e.status==='active'}).map(function(e){return '<option value="'+e.id+'">'+esc(e.name)+'</option>'}).join('');
  document.getElementById('page').innerHTML='<div class="card"><h3>\u0637\u0644\u0628 \u0625\u062c\u0627\u0632\u0629</h3>'+(mine?'':'<label>\u0627\u0644\u0645\u0648\u0638\u0641<select id="lv_emp">'+opts+'</select></label>')+'<div class="formgrid"><label>\u0627\u0644\u0646\u0648\u0639<select id="lv_type"><option value="annual">\u0633\u0646\u0648\u064a\u0629</option><option value="sick">\u0645\u0631\u0636\u064a\u0629</option><option value="emergency">\u0637\u0627\u0631\u0626\u0629</option></select></label><label>\u0645\u0646<input id="lv_from" type="date" value="'+today()+'"></label><label>\u0625\u0644\u0649<input id="lv_to" type="date" value="'+today()+'"></label><label>\u0627\u0644\u0633\u0628\u0628<input id="lv_reason"></label></div><div class="row"><button class="btn" onclick="saveLeave()">\u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u0637\u0644\u0628</button><button class="btn sec" onclick="go(\'leaves\')">\u0625\u0644\u063a\u0627\u0621</button></div></div>';
}
function saveLeave(){
  const u=user();
  const empId=u.role==='employee'?u.empId:(document.getElementById('lv_emp')||{}).value;
  if(!empId){alert('\u0644\u0627 \u064a\u0648\u062c\u062f \u0645\u0644\u0641 \u0645\u0648\u0638\u0641 \u0645\u0631\u062a\u0628\u0637 \u0628\u062d\u0633\u0627\u0628\u0643');return}
  const from=lv_from.value,to=lv_to.value;
  if(!from||!to||to<from){alert('\u062a\u062d\u0642\u0642 \u0645\u0646 \u0627\u0644\u062a\u0648\u0627\u0631\u064a\u062e');return}
  db.leaves.push({id:uid(),empId:empId,type:lv_type.value,from:from,to:to,days:days(from,to),reason:lv_reason.value,status:'pending'});
  save(); go('leaves');
}
function reviewLeave(id,status){
  if(!can('approve')) return;
  const l=db.leaves.find(function(x){return x.id===id}); if(!l||l.status!=='pending') return;
  l.status=status;
  if(status==='approved'&&l.type==='annual'){const e=empById(l.empId); if(e) e.balance=Math.max(0,(e.balance||0)-l.days)}
  save(); go('leaves');
}
function viewAtt(){
  const date=(window._attDate||today());
  const rows=db.attendance.filter(function(a){return a.date===date && visibleEmps().some(function(e){return e.id===a.empId})});
  const canRec=can('attendance')&&user().role!=='employee';
  var html='<div class="row" style="margin-bottom:12px"><label style="margin:0">\u0627\u0644\u064a\u0648\u0645<input type="date" value="'+date+'" onchange="window._attDate=this.value;go(\'attendance\')"></label></div>';
  if(canRec){
    const opts=visibleEmps().filter(function(e){return e.status==='active'}).map(function(e){return '<option value="'+e.id+'">'+esc(e.name)+'</option>'}).join('');
    html+='<div class="card" style="margin-bottom:14px"><h3>\u062a\u0633\u062c\u064a\u0644 \u062d\u0636\u0648\u0631 / \u0627\u0646\u0635\u0631\u0627\u0641</h3><div class="formgrid"><label>\u0627\u0644\u0645\u0648\u0638\u0641<select id="at_emp">'+opts+'</select></label><label>\u0627\u0644\u062a\u0627\u0631\u064a\u062e<input id="at_date" type="date" value="'+date+'"></label><label>\u0627\u0644\u062d\u0636\u0648\u0631<input id="at_in" type="time" value="08:00"></label><label>\u0627\u0644\u0627\u0646\u0635\u0631\u0627\u0641<input id="at_out" type="time"></label></div><button class="btn" onclick="saveAtt()">\u062d\u0641\u0638 \u0627\u0644\u0633\u062c\u0644</button></div>';
  }
  html+='<div class="card wrap"><table><thead><tr><th>\u0627\u0644\u0645\u0648\u0638\u0641</th><th>\u0627\u0644\u0642\u0633\u0645</th><th>\u0627\u0644\u062d\u0636\u0648\u0631</th><th>\u0627\u0644\u0627\u0646\u0635\u0631\u0627\u0641</th></tr></thead><tbody>';
  if(!rows.length) html+='<tr><td colspan="4">\u0644\u0627 \u062a\u0648\u062c\u062f \u0633\u062c\u0644\u0627\u062a \u0644\u0647\u0630\u0627 \u0627\u0644\u064a\u0648\u0645.</td></tr>';
  rows.forEach(function(a){const e=empById(a.empId)||{}; html+='<tr><td>'+esc(e.name)+'</td><td>'+esc(deptName(e.deptId))+'</td><td>'+esc(a.inn||'\u2014')+'</td><td>'+esc(a.out||'\u2014')+'</td></tr>'});
  return html+'</tbody></table></div>';
}
function saveAtt(){
  if(user().role==='employee') return;
  const empId=at_emp.value,date=at_date.value,inn=at_in.value,out=at_out.value;
  const i=db.attendance.findIndex(function(a){return a.empId===empId&&a.date===date});
  const rec={empId:empId,date:date,inn:inn,out:out,note:''};
  if(i>=0) db.attendance[i]=Object.assign({},db.attendance[i],rec); else db.attendance.push(Object.assign({id:uid()},rec));
  window._attDate=date; save(); go('attendance');
}
function viewUsers(){
  if(user().role!=='admin') return flash('\u063a\u064a\u0631 \u0645\u0635\u0631\u062d',false);
  return '<div class="card wrap"><table><thead><tr><th>\u0627\u0644\u0627\u0633\u0645</th><th>\u0627\u0644\u0628\u0631\u064a\u062f</th><th>\u0627\u0644\u062f\u0648\u0631</th></tr></thead><tbody>'+db.users.map(function(u){return '<tr><td>'+esc(u.name)+'</td><td dir="ltr">'+esc(u.email)+'</td><td>'+ROLES[u.role]+'</td></tr>'}).join('')+'</tbody></table></div>';
}
function viewProfile(){
  const u=user(); const e=u.empId?empById(u.empId):null;
  var extra=e?'<p>\u0627\u0644\u0631\u0642\u0645 \u0627\u0644\u0648\u0638\u064a\u0641\u064a: '+esc(e.no)+'</p><p>\u0627\u0644\u0642\u0633\u0645: '+esc(deptName(e.deptId))+'</p><p>\u0627\u0644\u0645\u0633\u0645\u0649: '+esc(e.title)+'</p><p>\u0631\u0635\u064a\u062f \u0627\u0644\u0625\u062c\u0627\u0632\u0629 \u0627\u0644\u0633\u0646\u0648\u064a\u0629: '+e.balance+' \u064a\u0648\u0645</p>':'';
  return '<div class="card"><h3>'+esc(u.name)+'</h3><p>\u0627\u0644\u0628\u0631\u064a\u062f: <span dir="ltr">'+esc(u.email)+'</span></p><p>\u0627\u0644\u062f\u0648\u0631: '+ROLES[u.role]+'</p>'+extra+'<p><button class="btn sec" onclick="if(confirm(\'\u0625\u0639\u0627\u062f\u0629 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u062a\u062c\u0631\u064a\u0628\u064a\u0629\u061f\')){localStorage.removeItem(KEY);location.reload()}">\u0625\u0639\u0627\u062f\u0629 \u0636\u0628\u0637 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a</button></p></div>';
}
function boot(){
  load();
  document.getElementById('quickAccounts').innerHTML=db.users.map(function(u){return '<div class="acc"><div><strong>'+esc(u.name)+'</strong><div class="muted">'+esc(u.email)+' \u2014 '+ROLES[u.role]+'</div></div><button class="btn sm" onclick="loginAs(\''+u.id+'\')">\u062f\u062e\u0648\u0644</button></div>'}).join('');
  const prev=localStorage.getItem(KEY+'-session');
  if(prev && db.users.some(function(u){return u.id===prev})) loginAs(prev);
}
boot();
