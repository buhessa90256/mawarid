const KEY = 'mawarid-ready-v1';
const PASS = 'Admin@123';
const ROLES = {admin:'مدير النظام',hr:'مدير موارد بشرية',dept_manager:'مدير قسم',employee:'موظف'};
const LTYPE = {annual:'سنوية',sick:'مرضية',emergency:'طارئة'};
const LSTAT = {pending:'قيد الانتظار',approved:'موافق',rejected:'مرفوض'};

function today(){return new Date().toISOString().slice(0,10)}
function fmt(d){if(!d)return '—'; const p=d.split('-'); return p.length===3?p[2]+'/'+p[1]+'/'+p[0]:d}
function uid(){return Date.now().toString(36)+Math.random().toString(36).slice(2,6)}
function days(a,b){return Math.round((new Date(b)-new Date(a))/86400000)+1}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&','<':'<','>':'>','"':'"',"'":'&#39;'}[c]))}

function seed(){
  return {
    users:[
      {id:'u1',name:'مدير النظام',email:'admin@mawarid.local',role:'admin',deptId:null,empId:null},
      {id:'u2',name:'منيرة الدوسري',email:'hr@mawarid.local',role:'hr',deptId:'d2',empId:'e5'},
      {id:'u3',name:'خالد العتيبي',email:'manager@mawarid.local',role:'dept_manager',deptId:'d1',empId:'e1'},
      {id:'u4',name:'نورة السالم',email:'staff@mawarid.local',role:'employee',deptId:'d1',empId:'e2'}
    ],
    departments:[
      {id:'d1',name:'تقنية المعلومات',desc:'تطوير الأنظمة والدعم الفني',manager:'e1'},
      {id:'d2',name:'الموارد البشرية',desc:'شؤون الموظفين والإجازات',manager:'e5'},
      {id:'d3',name:'المالية',desc:'الحسابات والمصروفات الداخلية',manager:'e7'}
    ],
    employees:[
      {id:'e1',no:'EMP-1001',name:'خالد العتيبي',deptId:'d1',title:'مدير تقنية المعلومات',email:'manager@mawarid.local',phone:'0501111001',hired:'2020-03-12',status:'active',balance:18,userId:'u3'},
      {id:'e2',no:'EMP-1002',name:'نورة السالم',deptId:'d1',title:'مطورة أنظمة',email:'staff@mawarid.local',phone:'0501111002',hired:'2022-06-01',status:'active',balance:21,userId:'u4'},
      {id:'e3',no:'EMP-1003',name:'سعد القحطاني',deptId:'d1',title:'مهندس شبكات',email:'saad@mawarid.local',phone:'0501111003',hired:'2021-01-15',status:'active',balance:16,userId:null},
      {id:'e4',no:'EMP-1004',name:'عبدالعزيز المطيري',deptId:'d1',title:'أخصائي دعم فني',email:'aziz@mawarid.local',phone:'0501111004',hired:'2023-09-10',status:'active',balance:21,userId:null},
      {id:'e5',no:'EMP-2001',name:'منيرة الدوسري',deptId:'d2',title:'مديرة الموارد البشرية',email:'hr@mawarid.local',phone:'0502222001',hired:'2019-11-03',status:'active',balance:12,userId:'u2'},
      {id:'e6',no:'EMP-2002',name:'هند العلي',deptId:'d2',title:'منسقة موارد بشرية',email:'hind@mawarid.local',phone:'0502222002',hired:'2024-02-18',status:'active',balance:21,userId:null},
      {id:'e7',no:'EMP-3001',name:'فهد الشمري',deptId:'d3',title:'محاسب',email:'fahad@mawarid.local',phone:'0503333001',hired:'2020-08-20',status:'active',balance:19,userId:null},
      {id:'e8',no:'EMP-3002',name:'ريم الحربي',deptId:'d3',title:'موظفة مالية',email:'reem@mawarid.local',phone:'0503333002',hired:'2023-04-04',status:'inactive',balance:21,userId:null}
    ],
    leaves:[
      {id:'l1',empId:'e2',type:'annual',from:shift(3),to:shift(5),days:3,reason:'إجازة سنوية قصيرة',status:'pending'},
      {id:'l2',empId:'e3',type:'sick',from:shift(-7),to:shift(-6),days:2,reason:'مراجعة طبية',status:'approved'}
    ],
    attendance:[
      {id:'a1',empId:'e1',date:today(),inn:'08:01',out:'',note:''},
      {id:'a2',empId:'e2',date:today(),inn:'08:12',out:'',note:''},
      {id:'a3',empId:'e5',date:today(),inn:'07:55',out:'16:02',note:''},
      {id:'a4',empId:'e3',date:shift(-1),inn:'08:20',out:'16:10',note:''}
    ]
  };
}
function shift(n){const d=new Date();d.setDate(d.getDate()+n);return d.toISOString().slice(0,10)}
