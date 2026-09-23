import{e as Ce,n as Le,r as d,j as t,L as _e}from"./react-vendor-B0BCRCBo.js";import{bC as Re,ag as Ne,d as Ee,V as $e}from"./index-CPzbNxf6.js";import{S as De,b as Oe,a as Fe}from"./SiteSeo-DTrIMla-.js";import{D as Pe,a as Ue,b as He,G as pe,C as Ae,A as ze,P as ge,S as he,e as ue,N as Be,d as Me,c as We}from"./devToolSeo-CEIHEI_R.js";import{c as qe}from"./jsonTools-BhNxooT-.js";import"./runtime-p6VWrWG8.js";import"./state-Vkj5r2ui.js";import"./motion-LsesSZEK.js";const P=Pe["/playground"],Ie=[{id:"390",label:"Phone"},{id:"768",label:"Tablet"},{id:"1100",label:"Desktop"},{id:"full",label:"Fill"}],Ge=[{id:"ltr",label:"LTR"},{id:"rtl",label:"RTL"}],Ve=[{id:"light",label:"Light"},{id:"dark",label:"Dark"}],ye=`<!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pricing cards</title>
<style>
  :root {
    --ink: #0f172a; --muted: #64748b; --line: #e2e8f0;
    --surface: #ffffff; --canvas: #f8fafc; --accent: #0ea5e9;
  }
  [data-theme="dark"] {
    --ink: #e2e8f0; --muted: #94a3b8; --line: #1e293b;
    --surface: #0f172a; --canvas: #020617; --accent: #38bdf8;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; padding: 32px; background: var(--canvas); color: var(--ink);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Arabic", sans-serif;
  }
  .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
  .card {
    background: var(--surface); border: 1px solid var(--line); border-radius: 16px;
    padding: 24px; display: flex; flex-direction: column; gap: 12px;
  }
  .card[data-featured] { border-color: var(--accent); box-shadow: 0 12px 30px rgb(14 165 233 / .12); }
  .name { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
  .price { font-size: 34px; font-weight: 800; }
  .price span { font-size: 14px; font-weight: 500; color: var(--muted); }
  ul { margin: 0; padding-inline-start: 18px; color: var(--muted); font-size: 14px; line-height: 1.9; }
  button {
    margin-block-start: auto; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--line);
    background: transparent; color: inherit; font: inherit; font-weight: 600; cursor: pointer;
  }
  .card[data-featured] button { background: var(--accent); border-color: var(--accent); color: #fff; }
</style>
</head>
<body>
  <div class="grid">
    <article class="card">
      <p class="name">Starter</p>
      <p class="price">$0 <span>/ month</span></p>
      <ul><li>One project</li><li>Community support</li></ul>
      <button type="button">Choose Starter</button>
    </article>
    <article class="card" data-featured>
      <p class="name">Team</p>
      <p class="price">$24 <span>/ month</span></p>
      <ul><li>Ten projects</li><li>Priority support</li><li>Usage reports</li></ul>
      <button type="button">Choose Team</button>
    </article>
    <article class="card">
      <p class="name">Scale</p>
      <p class="price">$96 <span>/ month</span></p>
      <ul><li>Unlimited projects</li><li>SSO and audit log</li></ul>
      <button type="button">Talk to sales</button>
    </article>
  </div>
</body>
</html>`;function nt(){const e=Ce.c(95),[oe]=Le(),[o,U]=d.useState(ye),[m,we]=d.useState("full"),[n,ve]=d.useState("ltr"),[s,ke]=d.useState("light"),[Se,fe]=d.useState(!1),[H,be]=d.useState(null),xe=d.useRef(null);let A;e[0]!==oe?(A=oe.get("slug"),e[0]=oe,e[1]=A):A=e[1];const i=A;let z,B;e[2]!==i?(z=()=>{if(!i)return;let a=!1;return fe(!0),be(null),fetch(`/components/${i}/template.html`).then(Ye).then(l=>{a||U(l)}).catch(l=>{a||be(`${l.message} You can still paste markup below.`)}).finally(()=>{a||fe(!1)}),()=>{a=!0}},B=[i],e[2]=i,e[3]=z,e[4]=B):(z=e[3],B=e[4]),d.useEffect(z,B);let c=o;if(/<html[^>]*>/i.test(c)){let a;if(e[5]!==n||e[6]!==c||e[7]!==s){let l;e[9]===Symbol.for("react.memo_cache_sentinel")?(l=/<html([^>]*)>/i,e[9]=l):l=e[9];let r;e[10]!==n||e[11]!==s?(r=(Je,Te)=>`<html${Te.replace(/\sdir="[^"]*"/i,"").replace(/\sdata-theme="[^"]*"/i,"")} dir="${n}" data-theme="${s}">`,e[10]=n,e[11]=s,e[12]=r):r=e[12],a=c.replace(l,r),e[5]=n,e[6]=c,e[7]=s,e[8]=a}else a=e[8];c=a}else c=`<!doctype html><html dir="${n}" data-theme="${s}"><body>${c}</body></html>`;const p=c;let M;e[13]===Symbol.for("react.memo_cache_sentinel")?(M=a=>{if(!a)return;const l=new FileReader;l.onload=()=>U(String(l.result||"")),l.readAsText(a)},e[13]=M):M=e[13];const je=M;let W;e[14]!==i||e[15]!==o?(W=()=>{const a=new Blob([o],{type:"text/html;charset=utf-8"}),l=URL.createObjectURL(a),r=document.createElement("a");r.href=l,r.download=`${i||"component"}.html`,document.body.appendChild(r),r.click(),r.remove(),requestAnimationFrame(()=>URL.revokeObjectURL(l))},e[14]=i,e[15]=o,e[16]=W):W=e[16];const ne=W;let q;e[17]!==p?(q=()=>{const a=new Blob([p],{type:"text/html"}),l=URL.createObjectURL(a);window.open(l,"_blank","noopener"),setTimeout(()=>URL.revokeObjectURL(l),3e4)},e[17]=p,e[18]=q):q=e[18];const se=q;let I;e[19]!==o?(I=o.split(`
`),e[19]=o,e[20]=I):I=e[20];const re=I.length;let G;e[21]===Symbol.for("react.memo_cache_sentinel")?(G=t.jsx(De,{title:"HTML component playground - edit and preview a single-file component",description:"Edit a self-contained HTML component and see it render immediately, at phone, tablet or desktop width, in light or dark and in either text direction. Runs entirely in your browser.",path:"/playground",altPath:"/ar/playground",breadcrumbs:[{name:"Home",path:"/"},{name:"Developer tools",path:"/tools"},{name:"Component playground",path:"/playground"}],schemas:[Oe({name:P.name,description:P.description,path:"/playground",category:P.category,features:P.features}),Fe(P.faq)]}),e[21]=G):G=e[21];let V;e[22]===Symbol.for("react.memo_cache_sentinel")?(V=[{label:"Home",to:"/"},{label:"Developer tools",to:"/tools"},{label:"Component playground"}],e[22]=V):V=e[22];let Y,J;e[23]===Symbol.for("react.memo_cache_sentinel")?(J=t.jsx(Ue,{icon:Re,trail:V,title:"Component playground",lede:"Edit a self-contained HTML component and watch it render as you type - at phone, tablet or desktop width, in light or dark, and in either text direction. Every template in the component library is one file, which is exactly what this page renders.",facts:[{label:"Widths",value:"Phone, tablet, desktop"},{label:"Checks",value:"Dark mode and RTL"},{label:"Sandbox",value:"Scripts only, no access out"}]}),Y=t.jsx(He,{current:"/playground"}),e[23]=Y,e[24]=J):(Y=e[23],J=e[24]);const ie=Se?"Loading the component…":"One self-contained HTML document: markup, styles and any script.";let K;e[25]===Symbol.for("react.memo_cache_sentinel")?(K=t.jsx(pe,{onClick:()=>xe.current?.click(),children:"Open"}),e[25]=K):K=e[25];let Q;e[26]===Symbol.for("react.memo_cache_sentinel")?(Q=t.jsxs(t.Fragment,{children:[K,t.jsx(pe,{icon:Ne,onClick:()=>U(ye),children:"Sample"})]}),e[26]=Q):Q=e[26];let h;e[27]!==o?(h=qe(new Blob([o]).size),e[27]=o,e[28]=h):h=e[28];let u;e[29]!==re||e[30]!==h?(u=t.jsxs("span",{className:"text-[11px] tabular-nums text-secondary-text",children:[re," lines · ",h]}),e[29]=re,e[30]=h,e[31]=u):u=e[31];let f;e[32]!==o?(f=t.jsx(Ae,{text:o}),e[32]=o,e[33]=f):f=e[33];let b;e[34]!==ne?(b=t.jsx(ze,{icon:Ee,onClick:ne,children:"Download"}),e[34]=ne,e[35]=b):b=e[35];let x;e[36]!==f||e[37]!==b?(x=t.jsxs("div",{className:"flex gap-2",children:[f,b]}),e[36]=f,e[37]=b,e[38]=x):x=e[38];let g;e[39]!==u||e[40]!==x?(g=t.jsxs(t.Fragment,{children:[u,x]}),e[39]=u,e[40]=x,e[41]=g):g=e[41];let X;e[42]===Symbol.for("react.memo_cache_sentinel")?(X=t.jsx("input",{ref:xe,type:"file",accept:".html,.htm,text/html",className:"hidden",onChange:a=>je(a.target.files?.[0])}),e[42]=X):X=e[42];let y;e[43]!==H?(y=H?t.jsx("p",{className:"mb-3 rounded-xl bg-amber-500/10 p-3 text-[11.5px] text-amber-600",children:H}):null,e[43]=H,e[44]=y):y=e[44];let Z;e[45]===Symbol.for("react.memo_cache_sentinel")?(Z=a=>U(a.target.value),e[45]=Z):Z=e[45];let w;e[46]!==o?(w=t.jsx("textarea",{value:o,onChange:Z,spellCheck:"false","aria-label":"Component source",className:"h-[32rem] w-full resize-y rounded-xl border border-main-border bg-background-color p-3 font-mono text-[11.5px] leading-relaxed text-primary-text outline-none transition-colors focus:border-fg-accent"}),e[46]=o,e[47]=w):w=e[47];let v;e[48]!==ie||e[49]!==g||e[50]!==y||e[51]!==w?(v=t.jsxs(ge,{title:"The file",subtitle:ie,actions:Q,footer:g,children:[X,y,w]}),e[48]=ie,e[49]=g,e[50]=y,e[51]=w,e[52]=v):v=e[52];let k;e[53]!==se?(k=t.jsx(pe,{icon:$e,onClick:se,children:"Open in a tab"}),e[53]=se,e[54]=k):k=e[54];let S;e[55]!==m?(S=t.jsx(he,{options:Ie,value:m,onChange:we,ariaLabel:"Preview width"}),e[55]=m,e[56]=S):S=e[56];let j;e[57]!==n?(j=t.jsx(he,{options:Ge,value:n,onChange:ve,ariaLabel:"Text direction"}),e[57]=n,e[58]=j):j=e[58];let T;e[59]!==s?(T=t.jsx(he,{options:Ve,value:s,onChange:ke,ariaLabel:"Theme"}),e[59]=s,e[60]=T):T=e[60];let C;e[61]!==S||e[62]!==j||e[63]!==T?(C=t.jsxs("div",{className:"flex flex-wrap gap-2",children:[S,j,T]}),e[61]=S,e[62]=j,e[63]=T,e[64]=C):C=e[64];const ce=m==="full"?"100%":`${m}px`;let L;e[65]!==ce?(L={width:ce},e[65]=ce,e[66]=L):L=e[66];let _;e[67]!==p||e[68]!==L?(_=t.jsx("div",{className:"mt-3 overflow-x-auto rounded-xl border border-main-border bg-background-color p-3",children:t.jsx("iframe",{title:"Component preview",srcDoc:p,sandbox:"allow-scripts",className:"mx-auto block h-[32rem] rounded-lg border border-main-border bg-white",style:L})}),e[67]=p,e[68]=L,e[69]=_):_=e[69];const de=m==="full"?"Fill":`${m}px`;let R;e[70]!==de?(R=t.jsx(ue,{label:"Width",value:de}),e[70]=de,e[71]=R):R=e[71];let N;e[72]!==n?(N=n.toUpperCase(),e[72]=n,e[73]=N):N=e[73];let E;e[74]!==N?(E=t.jsx(ue,{label:"Direction",value:N}),e[74]=N,e[75]=E):E=e[75];const me=s==="dark"?"Dark":"Light";let $;e[76]!==me?($=t.jsx(ue,{label:"Theme",value:me}),e[76]=me,e[77]=$):$=e[77];let D;e[78]!==R||e[79]!==E||e[80]!==$?(D=t.jsxs("div",{className:"mt-3 grid grid-cols-3 gap-2.5",children:[R,E,$]}),e[78]=R,e[79]=E,e[80]=$,e[81]=D):D=e[81];let ee;e[82]===Symbol.for("react.memo_cache_sentinel")?(ee=t.jsxs("p",{className:"mt-3 text-[11.5px] leading-relaxed text-secondary-text",children:["Start from a real one: open any template in the"," ",t.jsx(_e,{to:"/components",className:"text-fg-accent hover:underline",children:"component library"})," ","and it arrives here with its markup loaded."]}),e[82]=ee):ee=e[82];let O;e[83]!==k||e[84]!==C||e[85]!==_||e[86]!==D?(O=t.jsxs(ge,{title:"Preview",subtitle:"Rendered in a sandboxed frame; it can run its own scripts and reach nothing here.",actions:k,children:[C,_,D,ee]}),e[83]=k,e[84]=C,e[85]=_,e[86]=D,e[87]=O):O=e[87];let F;e[88]!==v||e[89]!==O?(F=t.jsxs("div",{className:"mt-5 grid gap-4 lg:grid-cols-2 lg:items-start",children:[v,O]}),e[88]=v,e[89]=O,e[90]=F):F=e[90];let te,ae;e[91]===Symbol.for("react.memo_cache_sentinel")?(te=t.jsx(Be,{cards:[{title:"Why single-file components",body:"A component that is one HTML file can be previewed by an iframe, read in a sitting, and pasted into any stack - no build step, no dependency graph, nothing fetched at runtime. That is the whole premise of the library this page edits."},{title:"What the sandbox allows",body:"The frame runs scripts so a component's own behaviour works, and it is denied same-origin access, forms, popups and navigation. A pasted component cannot read this page, its storage or your cookies."},{title:"Check dark and RTL before shipping",body:"Both switches rewrite the document's own attributes rather than forcing styles from outside, so what you see is what the file does on its own. A layout that only mirrors correctly because the parent page helped it is a layout that will break elsewhere."}]}),ae=t.jsx(Me,{items:P.faq}),e[91]=te,e[92]=ae):(te=e[91],ae=e[92]);let le;return e[93]!==F?(le=t.jsxs(t.Fragment,{children:[G,t.jsxs(We,{children:[J,Y,F,te,ae]})]}),e[93]=F,e[94]=le):le=e[94],le}function Ye(e){if(!e.ok)throw new Error(`That component returned ${e.status}.`);return e.text()}export{nt as default};
