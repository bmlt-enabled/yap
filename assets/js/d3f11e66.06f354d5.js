"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[2589],{8381:(e,n,i)=>{i.r(n),i.d(n,{assets:()=>l,contentTitle:()=>c,default:()=>h,frontMatter:()=>r,metadata:()=>s,toc:()=>a});var t=i(5893),o=i(1151);const r={},c="Force Dialing",s={id:"helpline/force-dialing",title:"Force Dialing",description:"---",source:"@site/docs/helpline/force-dialing.md",sourceDirName:"helpline",slug:"/helpline/force-dialing",permalink:"/helpline/force-dialing",draft:!1,unlisted:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/helpline/force-dialing.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Service Body Direct Dial",permalink:"/helpline/extension-dial"},next:{title:"Force Helpline Routing",permalink:"/helpline/force-helpline-routing"}},l={},a=[];function d(e){const n={code:"code",h1:"h1",hr:"hr",p:"p",...(0,o.a)(),...e.components};return(0,t.jsxs)(t.Fragment,{children:[(0,t.jsx)(n.h1,{id:"force-dialing",children:"Force Dialing"}),"\n",(0,t.jsx)(n.hr,{}),"\n",(0,t.jsx)(n.p,{children:"You might want to force a particular Twilio number to just call another number.  Just use the following webhook."}),"\n",(0,t.jsxs)(n.p,{children:[(0,t.jsx)(n.code,{children:"/helpline-search.php?ForceNumber=8885551212"})," or for extension dialing ",(0,t.jsx)(n.code,{children:"/helpline-search.php?ForceNumber=8885551212%7Cwwww700"}),".  Each ",(0,t.jsx)(n.code,{children:"w"})," is a 1 second pause."]}),"\n",(0,t.jsx)(n.p,{children:"In some cases, when using 1 second pauses you may want to indicate that there is something happening to the end user as there will be a delay."}),"\n",(0,t.jsx)(n.p,{children:"If you would like there to be a CAPTCHA to prevent robocalls + fax machines, you can add this to your query."}),"\n",(0,t.jsx)(n.p,{children:(0,t.jsx)(n.code,{children:"&Captcha=1"})}),"\n",(0,t.jsx)(n.p,{children:"And/or, if you would like to have a basic waiting message, but no CAPTCHA use."}),"\n",(0,t.jsx)(n.p,{children:(0,t.jsx)(n.code,{children:"&WaitingMessage=1"})}),"\n",(0,t.jsx)(n.p,{children:"These options can be combined."})]})}function h(e={}){const{wrapper:n}={...(0,o.a)(),...e.components};return n?(0,t.jsx)(n,{...e,children:(0,t.jsx)(d,{...e})}):d(e)}},1151:(e,n,i)=>{i.d(n,{Z:()=>s,a:()=>c});var t=i(7294);const o={},r=t.createContext(o);function c(e){const n=t.useContext(r);return t.useMemo((function(){return"function"==typeof e?e(n):{...n,...e}}),[n,e])}function s(e){let n;return n=e.disableParentContext?"function"==typeof e.components?e.components(o):e.components||o:c(e.components),t.createElement(r.Provider,{value:n},e.children)}}}]);