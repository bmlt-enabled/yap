"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[4001],{9495:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>l,contentTitle:()=>a,default:()=>u,frontMatter:()=>i,metadata:()=>o,toc:()=>c});const o=JSON.parse('{"id":"general/stateprovince-lookup","title":"State/Province Lookup","description":"---","source":"@site/docs/general/stateprovince-lookup.md","sourceDirName":"general","slug":"/general/stateprovince-lookup","permalink":"/general/stateprovince-lookup","draft":false,"unlisted":false,"editUrl":"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/general/stateprovince-lookup.md","tags":[],"version":"current","frontMatter":{},"sidebar":"tutorialSidebar","previous":{"title":"Skipping Helpline Call Routing","permalink":"/general/skipping-helpline-call-routing"},"next":{"title":"Tollfree Province Bias","permalink":"/general/tollfree-province-bias"}}');var r=n(4848),s=n(8453);const i={},a="State/Province Lookup",l={},c=[];function p(e){const t={code:"code",h1:"h1",header:"header",hr:"hr",p:"p",pre:"pre",...(0,s.R)(),...e.components};return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(t.header,{children:(0,r.jsx)(t.h1,{id:"stateprovince-lookup",children:"State/Province Lookup"})}),"\n",(0,r.jsx)(t.hr,{}),"\n",(0,r.jsxs)(t.p,{children:["It may be that your instance needs to search multiple states. By default searches will be biased towards the local number state (unless it's tollfree).  To enable province lookup set the ",(0,r.jsx)(t.code,{children:"$province_lookup"}),", variable to ",(0,r.jsx)(t.code,{children:"true"})," in the ",(0,r.jsx)(t.code,{children:"config.php"})," file."]}),"\n",(0,r.jsx)(t.pre,{children:(0,r.jsx)(t.code,{className:"language-php",children:"static $province_lookup = true;\n"})}),"\n",(0,r.jsx)(t.p,{children:"You can also specify a predetermined list of provinces / states. If you use this setting, then a speech gathering will be replaced with a numbered menu of states. Currently it would support up to 9 states in the list. To enable this do the following for example (the order in the list and position is the number that will be said to be pressed in the menu):"}),"\n",(0,r.jsx)(t.pre,{children:(0,r.jsx)(t.code,{className:"language-php",children:'static $province_lookup = true;\nstatic $province_lookup_list = ["North Carolina", "South Carolina"];\n'})})]})}function u(e={}){const{wrapper:t}={...(0,s.R)(),...e.components};return t?(0,r.jsx)(t,{...e,children:(0,r.jsx)(p,{...e})}):p(e)}},8453:(e,t,n)=>{n.d(t,{R:()=>i,x:()=>a});var o=n(6540);const r={},s=o.createContext(r);function i(e){const t=o.useContext(s);return o.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function a(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(r):e.components||r:i(e.components),o.createElement(s.Provider,{value:t},e.children)}}}]);