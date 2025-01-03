"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[747],{1647:(e,n,i)=>{i.r(n),i.d(n,{assets:()=>l,contentTitle:()=>d,default:()=>h,frontMatter:()=>o,metadata:()=>t,toc:()=>a});const t=JSON.parse('{"id":"helpline/hidden-service-bodies","title":"Using Hidden Service Bodies For Helpline Lookups","description":"---","source":"@site/docs/helpline/hidden-service-bodies.md","sourceDirName":"helpline","slug":"/helpline/hidden-service-bodies","permalink":"/helpline/hidden-service-bodies","draft":false,"unlisted":false,"editUrl":"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/helpline/hidden-service-bodies.md","tags":[],"version":"current","frontMatter":{},"sidebar":"tutorialSidebar","previous":{"title":"Helpline Search Radius","permalink":"/helpline/helpline-search-radius"},"next":{"title":"Music On Hold","permalink":"/helpline/music-on-hold"}}');var s=i(4848),r=i(8453);const o={},d="Using Hidden Service Bodies For Helpline Lookups",l={},a=[];function c(e){const n={code:"code",h1:"h1",header:"header",hr:"hr",p:"p",pre:"pre",strong:"strong",...(0,r.R)(),...e.components};return(0,s.jsxs)(s.Fragment,{children:[(0,s.jsx)(n.header,{children:(0,s.jsx)(n.h1,{id:"using-hidden-service-bodies-for-helpline-lookups",children:"Using Hidden Service Bodies For Helpline Lookups"})}),"\n",(0,s.jsx)(n.hr,{}),"\n",(0,s.jsx)(n.p,{children:"It is possible to create a service body with an unpublished group in order create additional routing for service bodies that may not exist in a given root server."}),"\n",(0,s.jsx)(n.p,{children:"Once those service bodies have been populated and the unpublished meetings are added, you can make use of the helpline field to route calls."}),"\n",(0,s.jsx)(n.p,{children:"You will also need to add to the config.php three additional variables.  This allows yap to authenticate to the root server and retrieve the unpublished meetings.  This is required as a BMLT root server by design will not return unpublished meetings in the semantic interface."}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:'static $helpline_search_unpublished = true;\nstatic $bmlt_username = "";\nstatic $bmlt_password = "";\n'})}),"\n",(0,s.jsx)(n.p,{children:"You will need to also ensure that PHP has write access to write to this folder, in order to store the authentication cookie from the BMLT root server."}),"\n",(0,s.jsx)(n.p,{children:(0,s.jsx)(n.strong,{children:"NOTE: This will not work for the Aggregator server, because there is no concept of authentication."})})]})}function h(e={}){const{wrapper:n}={...(0,r.R)(),...e.components};return n?(0,s.jsx)(n,{...e,children:(0,s.jsx)(c,{...e})}):c(e)}},8453:(e,n,i)=>{i.d(n,{R:()=>o,x:()=>d});var t=i(6540);const s={},r=t.createContext(s);function o(e){const n=t.useContext(r);return t.useMemo((function(){return"function"==typeof e?e(n):{...n,...e}}),[n,e])}function d(e){let n;return n=e.disableParentContext?"function"==typeof e.components?e.components(s):e.components||s:o(e.components),t.createElement(r.Provider,{value:n},e.children)}}}]);