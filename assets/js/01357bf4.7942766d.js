"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[9639],{9948:(e,n,t)=>{t.r(n),t.d(n,{assets:()=>c,contentTitle:()=>s,default:()=>h,frontMatter:()=>l,metadata:()=>o,toc:()=>a});const o=JSON.parse('{"id":"helpline/volunteer-routing","title":"Volunteer Routing","description":"---","source":"@site/docs/helpline/volunteer-routing.md","sourceDirName":"helpline","slug":"/helpline/volunteer-routing","permalink":"/helpline/volunteer-routing","draft":false,"unlisted":false,"editUrl":"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/helpline/volunteer-routing.md","tags":[],"version":"current","frontMatter":{},"sidebar":"tutorialSidebar","previous":{"title":"Volunteer Auto Answer","permalink":"/helpline/volunteer-auto-answer"},"next":{"title":"Connectors","permalink":"/miscellaneous/connectors"}}');var i=t(4848),r=t(8453);const l={},s="Volunteer Routing",c={},a=[];function d(e){const n={a:"a",code:"code",h1:"h1",header:"header",hr:"hr",li:"li",ol:"ol",p:"p",pre:"pre",ul:"ul",...(0,r.R)(),...e.components};return(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(n.header,{children:(0,i.jsx)(n.h1,{id:"volunteer-routing",children:"Volunteer Routing"})}),"\n",(0,i.jsx)(n.hr,{}),"\n",(0,i.jsx)(n.p,{children:"Incompatible with Yap 1.x Volunteer Dialers, you will have reconfigure your setup."}),"\n",(0,i.jsxs)(n.ol,{children:["\n",(0,i.jsxs)(n.li,{children:["You will need to ensure that the following ",(0,i.jsx)(n.code,{children:"config.php"}),' parameters are set.  They should be a service body admin that will be responsible for reading and writing data back to your BMLT.  This will not work with the "Server Administrator" account.']}),"\n"]}),"\n",(0,i.jsx)(n.pre,{children:(0,i.jsx)(n.code,{className:"language-php",children:'static $bmlt_username = "";\nstatic $bmlt_password = "";\n'})}),"\n",(0,i.jsxs)(n.ol,{start:"2",children:["\n",(0,i.jsx)(n.li,{children:"You will need to specify Twilio API parameters.  You can find this on your account dashboard when you login into Twilio."}),"\n"]}),"\n",(0,i.jsx)(n.pre,{children:(0,i.jsx)(n.code,{className:"language-php",children:'static $twilio_account_sid = "";\nstatic $twilio_auth_token = "";\n'})}),"\n",(0,i.jsxs)(n.ol,{start:"3",children:["\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsxs)(n.p,{children:["Head over to your admin login page.  ",(0,i.jsx)(n.a,{href:"https://your-yap-instance/admin",children:"https://your-yap-instance/admin"}),"."]}),"\n"]}),"\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsx)(n.p,{children:"Login with any credentials from your BMLT server."}),"\n"]}),"\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsx)(n.p,{children:'Go to the Service Bodies tab and click "Configure".  From there you should see a check box to enable Volunteer Routing.  Check it off and save.'}),"\n"]}),"\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsx)(n.p,{children:"Go to Volunteers, and you should see that service body in the dropdown, and select it."}),"\n"]}),"\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsx)(n.p,{children:'Click Add Volunteer.  Fill out the Name field, and then click the "+" to expand out the rest of the details.  You should be able to start populating the number and shift information.  You will also have to click "Enable" in the bottom right.  Once you are done, click "Save Volunteers".'}),"\n"]}),"\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsx)(n.p,{children:"You can also sort the sequence by dragging and dropping the volunteer cards."}),"\n"]}),"\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsx)(n.p,{children:"Go to Schedules to preview your changes.  Select your service body from the dropdown, and it should render onto the calendar."}),"\n"]}),"\n",(0,i.jsxs)(n.li,{children:["\n",(0,i.jsx)(n.p,{children:"You can now test to see if things are working."}),"\n",(0,i.jsxs)(n.ul,{children:["\n",(0,i.jsx)(n.li,{children:'Volunteer Routing Redirect: You do this by setting in the Service Body Call Handling the Volunteer Routing mechanism to "Volunteers Redirect" and specifying the respective Service Body Id in the "Volunteers Redirect Id" field.'}),"\n",(0,i.jsx)(n.li,{children:"Forced Caller Id: This setting changes the outgoing display caller id."}),"\n",(0,i.jsx)(n.li,{children:"Call Timeout: This is the number of seconds before trying the next number for volunteer routing."}),"\n"]}),"\n"]}),"\n"]})]})}function h(e={}){const{wrapper:n}={...(0,r.R)(),...e.components};return n?(0,i.jsx)(n,{...e,children:(0,i.jsx)(d,{...e})}):d(e)}},8453:(e,n,t)=>{t.d(n,{R:()=>l,x:()=>s});var o=t(6540);const i={},r=o.createContext(i);function l(e){const n=o.useContext(r);return o.useMemo((function(){return"function"==typeof e?e(n):{...n,...e}}),[n,e])}function s(e){let n;return n=e.disableParentContext?"function"==typeof e.components?e.components(i):e.components||i:l(e.components),o.createElement(r.Provider,{value:n},e.children)}}}]);