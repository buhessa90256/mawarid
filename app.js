const K='mawarid-v3';
const R={admin:'مدير النظام',hr:'موارد بشرية',dept_manager:'مدير قسم',employee:'موظف'};
const LT={annual:'سنوية',sick:'مرضية',emergency:'طارئة'};
const LS={pending:'معلق',approved:'معتمد',rejected:'مرفوض'};
const P='Admin@123';
const today=()=>new Date().toISOString().slice(0,10);
const now=()=>new Date().toTimeString().slice(0,5);
const fmt=d=>{if(!d)return '\u2014';const p=String(d).split('-');return p.length===3?p[2]+'/'+p[1]+'/'+p[0]:d};
const uid=()=>Math.random().toString(36).slice(2,8);
const days=(a,b)=>Math.round((new Date(b)-new Date(a))/86400000)+1;
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&','<':'<','>':'>','"':'"',"'":'&#39;'}[c]));
const shift=n=>{const d=new Date();d.setDate(d.getDate()+n);return d.toISOString().slice(0,10)};
function seed(){
  return {
    users:[
      {id:'u1',name:'فهد القحطاني',email:'admin@mawarid.local',pass:P,role:'admin',deptId:null,empId:null},
      {id:'u2',name:'منيرة الدوسري',email:'hr@mawarid.local',pass:P,role:'hr',deptId:'d2',empId:'e5'},
      {id:'u3',name:'خالد العتيبي',email:'manager@mawarid.local',pass:P,role:'dept_manager',deptId:'d1',empId:'e1'},
      {id:'u4',name:'نورة السالم',email:'staff@mawarid.local',pass:P,role:'employee',deptId:'d1',empId:'e2'}
    ],
    departments:[
      {id:'d1',name:'تقنية المعلومات',desc:'الأنظمة والدعم',manager:'e1'},
      {id:'d2',name:'الموارد البشرية',desc:'شؤون الموظفين',manager:'e5'},
      {id:'d3',name:'المالية',desc:'الحسابات',manager:'e7'},
      {id:'d4',name:'خدمة العملاء',desc:'الاستقبال والمتابعة',manager:'e9'}
    ],
    employees:[
      {id:'e1',no:'1001',name:'خالد العتيبي',deptId:'d1',title:'مدير تقنية',email:'manager@mawarid.local',phone:'0501111001',hired:'2020-03-12',status:'active',balance:18,userId:'u3'},
      {id:'e2',no:'1002',name:'نورة السالم',deptId:'d1',title:'مطورة',email:'staff@mawarid.local',phone:'0501111002',hired:'2022-06-01',status:'active',balance:21,userId:'u4'},
      {id:'e3',no:'1003',name:'سعد القحطاني',deptId:'d1',title:'مهندس شبكات',email:'saad@mawarid.local',phone:'0501111003',hired:'2021-01-15',status:'active',balance:16,userId:null},
      {id:'e4',no:'1004',name:'عبدالعزيز المطيري',deptId:'d1',title:'دعم فني',email:'aziz@mawarid.local',phone:'0501111004',hired:'2023-09-10',status:'active',balance:21,userId:null},
      {id:'e5',no:'2001',name:'منيرة الدوسري',deptId:'d2',title:'مديرة موارد بشرية',email:'hr@mawarid.local',phone:'0502222001',hired:'2019-11-03',status:'active',balance:12,userId:'u2'},
      {id:'e6',no:'2002',name:'هند العلي',deptId:'d2',title:'منسقة',email:'hind@mawarid.local',phone:'0502222002',hired:'2024-02-18',status:'active',balance:21,userId:null},
      {id:'e7',no:'3001',name:'فهد الشمري',deptId:'d3',title:'محاسب',email:'fahad@mawarid.local',phone:'0503333001',hired:'2020-08-20',status:'active',balance:19,userId:null},
      {id:'e8',no:'3002',name:'ريم الحربي',deptId:'d3',title:'موظفة مالية',email:'reem@mawarid.local',phone:'0503333002',hired:'2023-04-04',status:'inactive',balance:21,userId:null},
      {id:'e9',no:'4001',name:'ماجد الشهري',deptId:'d4',title:'مشرف خدمة عملاء',email:'majed@mawarid.local',phone:'0504444001',hired:'2021-07-07',status:'active',balance:17,userId:null},
      {id:'e10',no:'4002',name:'لينا الغامدي',deptId:'d4',title:'أخصائية عملاء',email:'lina@mawarid.local',phone:'0504444002',hired:'2025-01-12',status:'active',balance:21,userId:null}
    ],
    leaves:[
      {id:'l1',empId:'e2',type:'annual',from:shift(3),to:shift(5),days:3,reason:'سفر عائلي',status:'pending'},
      {id:'l2',empId:'e3',type:'sick',from:shift(-7),to:shift(-6),days:2,reason:'مراجعة طبية',status:'approved'},
      {id:'l3',empId:'e10',type:'emergency',from:shift(1),to:shift(1),days:1,reason:'ظرف طارئ',status:'pending'}
    ],
    attendance:[
      {id:'a1',empId:'e1',date:today(),inn:'08:01',out:''},
      {id:'a2',empId:'e2',date:today(),inn:'08:12',out:''},
      {id:'a3',empId:'e5',date:today(),inn:'07:55',out:'16:02'},
      {id:'a4',empId:'e9',date:today(),inn:'08:04',out:''}
    ]
  };
}
let db,sid;
function load(){try{db=JSON.parse(localStorage.getItem(K)||'null')}catch(e){db=null} if(!db||!db.users) db=seed(); save()}
function save(){localStorage.setItem(K,JSON.stringify(db))}
function me(){return db.users.find(u=>u.id===sid)}
function dn(id){return (db.departments.find(d=>d.id===id)||{}).name||'\u2014'}
function emp(id){return db.employees.find(e=>e.id===id)}
function can(p){
  const u=me(); if(!u) return false;
  if(u.role==='admin') return true;
  if(u.role==='hr') return ['employees','departments','leaves','attendance','approve'].includes(p);
  if(u.role==='dept_manager') return ['employees','leaves','attendance','approve'].includes(p);
  return ['leaves','attendance','self'].includes(p);
}
function vis(){
  const u=me();
  if(u.role==='admin'||u.role==='hr') return db.employees.slice();
  if(u.role==='dept_manager') return db.employees.filter(e=>e.deptId===u.deptId);
  return db.employees.filter(e=>e.id===u.empId);
}
function quick(id){enter(id)}
function doLogin(e){
  e.preventDefault();
  const em=document.getElementById('email').value.trim().toLowerCase();
  const pw=document.getElementById('pass').value;
  const box=document.getElementById('err');
  if(!em||!pw){box.textContent='أدخل البريد وكلمة المرور';box.classList.remove('hidden');return false}
  box.classList.add('hidden');
  const u=db.users.find(x=>x.email.toLowerCase()===em);
  if(u){enter(u.id);return false}
  const eid=uid(), uid2=uid(), name=em.split('@')[0];
  db.employees.push({id:eid,no:String(5000+db.employees.length),name,deptId:'d1',title:'موظف',email:em,phone:'',hired:today(),status:'active',balance:21,userId:uid2});
  db.users.push({id:uid2,name,email:em,pass:pw,role:'employee',deptId:'d1',empId:eid});
  save(); enter(uid2); return false;
}
function enter(id){
  sid=id; localStorage.setItem(K+'-s',id);
  document.getElementById('login').classList.add('hidden');
  document.getElementById('app').classList.remove('hidden');
  const u=me();
  document.getElementById('nm').textContent=u.name;
  document.getElementById('rl').textContent=R[u.role];
  menu(); go('home');
}
function out(){
  sid=null; localStorage.removeItem(K+'-s');
  document.getElementById('app').classList.add('hidden');
  document.getElementById('login').classList.remove('hidden');
  sb.classList.remove('open');
}
function menu(){
  const a=[['home','لوحة التحكم']];
  if(can('employees')||can('self')) a.push(['emps','الموظفون']);
  a.push(['deps','الأقسام']);
  if(can('leaves')) a.push(['leaves','الإجازات']);
  if(can('attendance')) a.push(['att','الحضور']);
  if(me().role==='admin') a.push(['users','المستخدمون']);
  a.push(['me','حسابي']);
  nav.innerHTML=a.map(([k,l])=>'<a href="#" data-k="'+k+'" onclick="go(\''+k+'\');return false">'+l+'</a>').join('');
}
function go(k){
  sb.classList.remove('open');
  [...nav.querySelectorAll('a')].forEach(x=>x.classList.toggle('on',x.dataset.k===k));
  const t={home:'لوحة التحكم',emps:'الموظفون',deps:'الأقسام',leaves:'الإجازات',att:'الحضور',users:'المستخدمون',me:'حسابي'};
  tt.textContent=t[k]||'موارد';
  pg.innerHTML={home:viewHome,emps:viewEmps,deps:viewDeps,leaves:viewLeaves,att:viewAtt,users:viewUsers,me:viewMe}[k]();
}
function viewHome(){
  const v=vis(), act=v.filter(e=>e.status==='active');
  const pend=db.leaves.filter(l=>l.status==='pending'&&v.some(e=>e.id===l.empId));
  const attn=db.attendance.filter(a=>a.date===today()&&a.inn&&v.some(e=>e.id===a.empId));
  const last=db.leaves.filter(l=>v.some(e=>e.id===l.empId)).slice(-5).reverse();
  return '<div class="kpis"><div class="card"><div class="m">الموظفون</div><div class="n">'+act.length+'</div></div><div class="card"><div class="m">إجازات معلقة</div><div class="n">'+pend.length+'</div></div><div class="card"><div class="m">حضور اليوم</div><div class="n">'+attn.length+'</div></div><div class="card"><div class="m">أقسام</div><div class="n">'+db.departments.length+'</div></div></div><div class="card"><h3>آخر الطلبات</h3><div class="scroll"><table><thead><tr><th>الموظف</th><th>النوع</th><th>المدة</th><th>الحالة</th></tr></thead><tbody>'+(last.map(l=>'<tr><td>'+esc((emp(l.empId)||{}).name)+'</td><td>'+LT[l.type]+'</td><td>'+fmt(l.from)+' - '+fmt(l.to)+'</td><td><span class="pill '+(l.status==='pending'?'p':l.status==='approved'?'o':'x')+'">'+LS[l.status]+'</span></td></tr>').join('')||'<tr><td colspan="4">لا يوجد</td></tr>')+'</tbody></table></div></div>';
}
function viewEmps(){
  const edit=me().role==='admin'||me().role==='hr';
  const q=(window.q||'').toLowerCase();
  let rows=vis();
  if(q) rows=rows.filter(e=>(e.name+e.no+e.title).toLowerCase().includes(q));
  return '<div class="bar"><input placeholder="بحث" value="'+esc(window.q||'')+'" oninput="window.q=this.value;go(\'emps\')">'+(edit?'<button class="btn" type="button" onclick="empForm()">موظف جديد</button>':'')+'</div><div class="card scroll"><table><thead><tr><th>#</th><th>الاسم</th><th>القسم</th><th>المسمى</th><th>الجوال</th><th>الرصيد</th><th></th>'+(edit?'<th></th>':'')+'</tr></thead><tbody>'+rows.map(e=>'<tr><td>'+esc(e.no)+'</td><td>'+esc(e.name)+'</td><td>'+esc(dn(e.deptId))+'</td><td>'+esc(e.title)+'</td><td dir="ltr">'+esc(e.phone)+'</td><td>'+e.balance+'</td><td><span class="pill '+(e.status==='active'?'o':'x')+'">'+(e.status==='active'?'نشط':'موقوف')+'</span></td>'+(edit?'<td><button class="btn g" type="button" onclick="empForm(\''+e.id+'\')">تعديل</button> <button class="btn r" type="button" onclick="delEmp(\''+e.id+'\')">حذف</button></td>':'')+'</tr>').join('')+'</tbody></table></div>';
}
function empForm(id){
  if(!(me().role==='admin'||me().role==='hr')) return;
  const e=id?emp(id):{no:String(1000+db.employees.length+1),name:'',deptId:'d1',title:'',email:'',phone:'',hired:today(),status:'active',balance:21};
  pg.innerHTML='<div class="card" style="max-width:640px"><h3>'+(id?'تعديل موظف':'موظف جديد')+'</h3><div class="fg"><label>الاسم<input id="f1" value="'+esc(e.name)+'"></label><label>الرقم<input id="f2" value="'+esc(e.no)+'"></label><label>القسم<select id="f3">'+db.departments.map(d=>'<option value="'+d.id+'" '+(d.id===e.deptId?'selected':'')+'>'+esc(d.name)+'</option>').join('')+'</select></label><label>المسمى<input id="f4" value="'+esc(e.title)+'"></label><label>البريد<input id="f5" dir="ltr" value="'+esc(e.email)+'"></label><label>الجوال<input id="f6" dir="ltr" value="'+esc(e.phone)+'"></label><label>التعيين<input id="f7" type="date" value="'+esc(e.hired)+'"></label><label>الحالة<select id="f8"><option value="active" '+(e.status==='active'?'selected':'')+'>نشط</option><option value="inactive">موقوف</option></select></label><label>الرصيد<input id="f9" type="number" value="'+e.balance+'"></label></div><div class="row" style="margin-top:8px"><button class="btn" type="button" onclick="saveEmp(\''+(id||'')+'\')">حفظ</button><button class="btn g" type="button" onclick="go(\'emps\')">رجوع</button></div></div>';
}
function saveEmp(id){
  const rec={name:f1.value.trim(),no:f2.value.trim(),deptId:f3.value,title:f4.value.trim(),email:f5.value.trim(),phone:f6.value.trim(),hired:f7.value,status:f8.value,balance:+f9.value||0};
  if(!rec.name||!rec.no||!rec.title) return;
  if(id){const i=db.employees.findIndex(e=>e.id===id);db.employees[i]=Object.assign({},db.employees[i],rec)}
  else db.employees.push(Object.assign({id:uid(),userId:null},rec));
  save(); go('emps');
}
function delEmp(id){
  if(!confirm('حذف الموظف؟')) return;
  db.employees=db.employees.filter(e=>e.id!==id);
  db.leaves=db.leaves.filter(l=>l.empId!==id);
  db.attendance=db.attendance.filter(a=>a.empId!==id);
  save(); go('emps');
}
function viewDeps(){
  const edit=me().role==='admin'||me().role==='hr';
  return (edit?'<div class="bar"><button class="btn" type="button" onclick="depForm()">قسم جديد</button></div>':'')+'<div class="depts">'+db.departments.map(d=>{const m=emp(d.manager); const n=db.employees.filter(e=>e.deptId===d.id).length; return '<div class="card"><h3>'+esc(d.name)+'</h3><div class="m">'+esc(d.desc)+'</div><p style="margin:8px 0 0">المدير: '+esc(m?m.name:'\u2014')+'</p><p class="m">'+n+' موظف</p>'+(edit?'<div class="row"><button class="btn g" type="button" onclick="depForm(\''+d.id+'\')">تعديل</button> <button class="btn r" type="button" onclick="delDep(\''+d.id+'\')">حذف</button></div>':'')+'</div>'}).join('')+'</div>';
}
function depForm(id){
  const d=id?db.departments.find(x=>x.id===id):{name:'',desc:'',manager:''};
  pg.innerHTML='<div class="card" style="max-width:480px"><h3>'+(id?'تعديل قسم':'قسم جديد')+'</h3><label>الاسم<input id="d1" value="'+esc(d.name)+'"></label><label>الوصف<input id="d2" value="'+esc(d.desc)+'"></label><label>المدير<select id="d3"><option value="">\u2014</option>'+db.employees.map(e=>'<option value="'+e.id+'" '+(e.id===d.manager?'selected':'')+'>'+esc(e.name)+'</option>').join('')+'</select></label><div class="row"><button class="btn" type="button" onclick="saveDep(\''+(id||'')+'\')">حفظ</button><button class="btn g" type="button" onclick="go(\'deps\')">رجوع</button></div></div>';
}
function saveDep(id){
  const rec={name:d1.value.trim(),desc:d2.value.trim(),manager:d3.value||null};
  if(!rec.name) return;
  if(id){const i=db.departments.findIndex(d=>d.id===id);db.departments[i]=Object.assign({},db.departments[i],rec)}
  else db.departments.push(Object.assign({id:uid()},rec));
  save(); go('deps');
}
function delDep(id){
  if(db.employees.some(e=>e.deptId===id)){alert('القسم مرتبط بموظفين');return}
  db.departments=db.departments.filter(d=>d.id!==id); save(); go('deps');
}
function viewLeaves(){
  const list=db.leaves.filter(l=>vis().some(e=>e.id===l.empId)).slice().reverse();
  return '<div class="bar"><button class="btn" type="button" onclick="leaveForm()">طلب إجازة</button></div><div class="card scroll"><table><thead><tr><th>الموظف</th><th>النوع</th><th>من</th><th>إلى</th><th>أيام</th><th>الحالة</th><th></th></tr></thead><tbody>'+(list.map(l=>{const e=emp(l.empId)||{}; const act=l.status==='pending'&&can('approve')?'<button class="btn" type="button" onclick="rev(\''+l.id+'\',\'approved\')">اعتماد</button> <button class="btn r" type="button" onclick="rev(\''+l.id+'\',\'rejected\')">رفض</button>':''; return '<tr><td>'+esc(e.name)+'</td><td>'+LT[l.type]+'</td><td>'+fmt(l.from)+'</td><td>'+fmt(l.to)+'</td><td>'+l.days+'</td><td><span class="pill '+(l.status==='pending'?'p':l.status==='approved'?'o':'x')+'">'+LS[l.status]+'</span></td><td>'+act+'</td></tr>'}).join('')||'<tr><td colspan="7">لا يوجد</td></tr>')+'</tbody></table></div>';
}
function leaveForm(){
  const mine=me().role==='employee';
  const opts=vis().filter(e=>e.status==='active');
  pg.innerHTML='<div class="card" style="max-width:480px"><h3>طلب إجازة</h3>'+(mine?'':'<label>الموظف<select id="le">'+opts.map(e=>'<option value="'+e.id+'">'+esc(e.name)+'</option>').join('')+'</select></label>')+'<div class="fg"><label>النوع<select id="lt"><option value="annual">سنوية</option><option value="sick">مرضية</option><option value="emergency">طارئة</option></select></label><label>من<input id="lf" type="date" value="'+today()+'"></label><label>إلى<input id="lt2" type="date" value="'+today()+'"></label><label>السبب<input id="lr"></label></div><div class="row"><button class="btn" type="button" onclick="saveLeave()">إرسال</button><button class="btn g" type="button" onclick="go(\'leaves\')">رجوع</button></div></div>';
}
function saveLeave(){
  const empId=me().role==='employee'?me().empId:(document.getElementById('le')||{}).value;
  if(!empId) return;
  const a=lf.value,b=document.getElementById('lt2').value;
  if(!a||!b||b<a) return;
  db.leaves.push({id:uid(),empId:empId,type:lt.value,from:a,to:b,days:days(a,b),reason:lr.value.trim(),status:'pending'});
  save(); go('leaves');
}
function rev(id,st){
  const l=db.leaves.find(x=>x.id===id); if(!l||l.status!=='pending') return;
  l.status=st;
  if(st==='approved'&&l.type==='annual'){const e=emp(l.empId); if(e) e.balance=Math.max(0,e.balance-l.days)}
  save(); go('leaves');
}
function viewAtt(){
  const day=window.ad||today();
  const rows=db.attendance.filter(a=>a.date===day&&vis().some(e=>e.id===a.empId));
  const self=me().empId;
  return (self?'<div class="card" style="margin-bottom:10px"><div class="row"><button class="btn" type="button" onclick="punch(\'in\')">حضور الآن</button> <button class="btn n" type="button" onclick="punch(\'out\')">انصراف الآن</button></div></div>':'')+'<div class="bar"><input type="date" value="'+day+'" onchange="window.ad=this.value;go(\'att\')"></div>'+(me().role!=='employee'?'<div class="card" style="margin-bottom:10px"><div class="fg"><label>موظف<select id="ae">'+vis().filter(e=>e.status==='active').map(e=>'<option value="'+e.id+'">'+esc(e.name)+'</option>').join('')+'</select></label><label>اليوم<input id="aday" type="date" value="'+day+'"></label><label>حضور<input id="ai" type="time" value="08:00"></label><label>انصراف<input id="ao" type="time"></label></div><button class="btn" type="button" onclick="saveAtt()">حفظ</button></div>':'')+'<div class="card scroll"><table><thead><tr><th>الموظف</th><th>القسم</th><th>حضور</th><th>انصراف</th></tr></thead><tbody>'+(rows.map(a=>{const e=emp(a.empId)||{};return '<tr><td>'+esc(e.name)+'</td><td>'+esc(dn(e.deptId))+'</td><td>'+esc(a.inn||'\u2014')+'</td><td>'+esc(a.out||'\u2014')+'</td></tr>'}).join('')||'<tr><td colspan="4">لا يوجد</td></tr>')+'</tbody></table></div>';
}
function punch(k){
  const id=me().empId; if(!id) return;
  let r=db.attendance.find(a=>a.empId===id&&a.date===today());
  if(!r){r={id:uid(),empId:id,date:today(),inn:'',out:''};db.attendance.push(r)}
  if(k==='in') r.inn=r.inn||now(); else r.out=now();
  save(); go('att');
}
function saveAtt(){
  const rec={empId:ae.value,date:aday.value,inn:ai.value,out:ao.value};
  const i=db.attendance.findIndex(a=>a.empId===rec.empId&&a.date===rec.date);
  if(i>=0) db.attendance[i]=Object.assign({},db.attendance[i],rec); else db.attendance.push(Object.assign({id:uid()},rec));
  window.ad=rec.date; save(); go('att');
}
function viewUsers(){
  if(me().role!=='admin') return '<div class="card">غير مصرح</div>';
  return '<div class="card scroll"><table><thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th></tr></thead><tbody>'+db.users.map(u=>'<tr><td>'+esc(u.name)+'</td><td dir="ltr">'+esc(u.email)+'</td><td>'+R[u.role]+'</td></tr>').join('')+'</tbody></table></div>';
}
function viewMe(){
  const u=me(), e=u.empId?emp(u.empId):null;
  return '<div class="card" style="max-width:420px"><p>'+esc(u.name)+'</p><p class="m" dir="ltr">'+esc(u.email)+'</p><p>'+R[u.role]+'</p>'+(e?'<p>'+esc(e.title)+' \u2014 '+esc(dn(e.deptId))+'</p><p>الرصيد: '+e.balance+' يوم</p>':'')+'</div>';
}
load();
const prev=localStorage.getItem(K+'-s');
if(prev && db.users.some(u=>u.id===prev)) enter(prev);
