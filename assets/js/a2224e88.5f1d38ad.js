"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[1465],{7497:(e,n,i)=>{i.r(n),i.d(n,{assets:()=>l,contentTitle:()=>r,default:()=>h,frontMatter:()=>o,metadata:()=>d,toc:()=>a});var t=i(5893),s=i(1151);const o={},r="Using Hidden Service Bodies For Helpline Lookups",d={id:"helpline/hidden-service-bodies",title:"Using Hidden Service Bodies For Helpline Lookups",description:"---",source:"@site/docs/helpline/hidden-service-bodies.md",sourceDirName:"helpline",slug:"/helpline/hidden-service-bodies",permalink:"/helpline/hidden-service-bodies",draft:!1,unlisted:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/helpline/hidden-service-bodies.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Helpline Search Radius",permalink:"/helpline/helpline-search-radius"},next:{title:"Music On Hold",permalink:"/helpline/music-on-hold"}},l={},a=[];function c(e){const n={code:"code",h1:"h1",hr:"hr",p:"p",pre:"pre",strong:"strong",...(0,s.a)(),...e.components};return(0,t.jsxs)(t.Fragment,{children:[(0,t.jsx)(n.h1,{id:"using-hidden-service-bodies-for-helpline-lookups",children:"Using Hidden Service Bodies For Helpline Lookups"}),"\n",(0,t.jsx)(n.hr,{}),"\n",(0,t.jsx)(n.p,{children:"It is possible to create a service body with an unpublished group in order create additional routing for service bodies that may not exist in a given root server."}),"\n",(0,t.jsx)(n.p,{children:"Once those service bodies have been populated and the unpublished meetings are added, you can make use of the helpline field to route calls."}),"\n",(0,t.jsx)(n.p,{children:"You will also need to add to the config.php three additional variables.  This allows yap to authenticate to the root server and retrieve the unpublished meetings.  This is required as a BMLT root server by design will not return unpublished meetings in the semantic interface."}),"\n",(0,t.jsx)(n.pre,{children:(0,t.jsx)(n.code,{className:"language-php",children:'static $helpline_search_unpublished = true;\nstatic $bmlt_username = "";\nstatic $bmlt_password = "";\n'})}),"\n",(0,t.jsx)(n.p,{children:"You will need to also ensure that PHP has write access to write to this folder, in order to store the authentication cookie from the BMLT root server."}),"\n",(0,t.jsx)(n.p,{children:(0,t.jsx)(n.strong,{children:"NOTE: This will not work for the Aggregator server, because there is no concept of authentication."})})]})}function h(e={}){const{wrapper:n}={...(0,s.a)(),...e.components};return n?(0,t.jsx)(n,{...e,children:(0,t.jsx)(c,{...e})}):c(e)}},1151:(e,n,i)=>{i.d(n,{Z:()=>d,a:()=>r});var t=i(7294);const s={},o=t.createContext(s);function r(e){const n=t.useContext(o);return t.useMemo((function(){return"function"==typeof e?e(n):{...n,...e}}),[n,e])}function d(e){let n;return n=e.disableParentContext?"function"==typeof e.components?e.components(s):e.components||s:r(e.components),t.createElement(o.Provider,{value:n},e.children)}}}]);