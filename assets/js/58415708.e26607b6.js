"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[8897],{7498:(e,t,s)=>{s.r(t),s.d(t,{assets:()=>a,contentTitle:()=>i,default:()=>l,frontMatter:()=>r,metadata:()=>c,toc:()=>d});var o=s(4848),n=s(8453);const r={},i="Custom Query",c={id:"meeting-search/custom-query",title:"Custom Query",description:"---",source:"@site/docs/meeting-search/custom-query.md",sourceDirName:"meeting-search",slug:"/meeting-search/custom-query",permalink:"/meeting-search/custom-query",draft:!1,unlisted:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/meeting-search/custom-query.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Voice Recognition Options",permalink:"/general/voice-recognition-options"},next:{title:"Grace Period",permalink:"/meeting-search/grace-period"}},a={},d=[];function u(e){const t={code:"code",h1:"h1",header:"header",hr:"hr",li:"li",ol:"ol",p:"p",...(0,n.R)(),...e.components};return(0,o.jsxs)(o.Fragment,{children:[(0,o.jsx)(t.header,{children:(0,o.jsx)(t.h1,{id:"custom-query",children:"Custom Query"})}),"\n",(0,o.jsx)(t.hr,{}),"\n",(0,o.jsx)(t.p,{children:"In some cases you may want use a custom BMLT query.  For example, if you have a small service body you may want to ignore the day of the week concept that is the default behavior in searches."}),"\n",(0,o.jsxs)(t.p,{children:["You can do this with the setting ",(0,o.jsx)(t.code,{children:"custom_query"}),".  This setting also supports the use of some magic variables."]}),"\n",(0,o.jsx)(t.p,{children:"For example say you want to always use the service body id for making queries, you could create the settings as follows:"}),"\n",(0,o.jsx)(t.p,{children:(0,o.jsx)(t.code,{children:'static $custom_query="&services[]={SETTING_SERVICE_BODY_ID}"'})}),"\n",(0,o.jsxs)(t.p,{children:["Because there is a setting called ",(0,o.jsx)(t.code,{children:"service_body_id"})," already and assuming you had overridden it, meeting searches will now send a query to the BMLT and return accordingly."]}),"\n",(0,o.jsx)(t.p,{children:"You could have also hardcoded it if you wanted.  Like any other variable, you can set this on the querystring as a session wide override."}),"\n",(0,o.jsxs)(t.p,{children:["In some cases you may need to combine this with the ",(0,o.jsx)(t.code,{children:"result_count_max"})," to increase the limit of how many results are returned.  You may also need to use ",(0,o.jsx)(t.code,{children:"sms_ask"}),", as many results could be returned."]}),"\n",(0,o.jsx)(t.p,{children:"There are a couple of other stock magic variables."}),"\n",(0,o.jsxs)(t.ol,{children:["\n",(0,o.jsxs)(t.li,{children:[(0,o.jsx)(t.code,{children:"{DAY}"})," - will use the day of today / tomorrow."]}),"\n",(0,o.jsxs)(t.li,{children:[(0,o.jsx)(t.code,{children:"{LATITUDE}"})," - the latitude of the lookup."]}),"\n",(0,o.jsxs)(t.li,{children:[(0,o.jsx)(t.code,{children:"{LONGITUDE}"})," - the longitude of the lookup."]}),"\n"]}),"\n",(0,o.jsxs)(t.p,{children:["If you do not have ",(0,o.jsx)(t.code,{children:"{LATITUDE}"})," or ",(0,o.jsx)(t.code,{children:"{LONGITUDE}"})," in your custom query, it will automatically skip the location gathering aspects of the meeting search menus and go directly to returning results."]})]})}function l(e={}){const{wrapper:t}={...(0,n.R)(),...e.components};return t?(0,o.jsx)(t,{...e,children:(0,o.jsx)(u,{...e})}):u(e)}},8453:(e,t,s)=>{s.d(t,{R:()=>i,x:()=>c});var o=s(6540);const n={},r=o.createContext(n);function i(e){const t=o.useContext(r);return o.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function c(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(n):e.components||n:i(e.components),o.createElement(r.Provider,{value:t},e.children)}}}]);