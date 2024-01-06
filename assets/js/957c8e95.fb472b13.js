"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[8093],{3024:(e,i,t)=>{t.r(i),t.d(i,{assets:()=>l,contentTitle:()=>a,default:()=>m,frontMatter:()=>s,metadata:()=>r,toc:()=>c});var o=t(5893),n=t(1151);const s={},a="Voicemail",r={id:"helpline/voicemail",title:"Voicemail",description:"---",source:"@site/docs/helpline/voicemail.md",sourceDirName:"helpline",slug:"/helpline/voicemail",permalink:"/helpline/voicemail",draft:!1,unlisted:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/helpline/voicemail.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Aggregator Helpline Routing",permalink:"/helpline/tomato-helpline-routing"},next:{title:"Volunteer Auto Answer",permalink:"/helpline/volunteer-auto-answer"}},l={},c=[{value:"Voicemail Recordings",id:"voicemail-recordings",level:3},{value:"Voicemail Notifications to SMS",id:"voicemail-notifications-to-sms",level:3},{value:"Voicemail Notifications to Email",id:"voicemail-notifications-to-email",level:3}];function d(e){const i={code:"code",h1:"h1",h3:"h3",hr:"hr",p:"p",pre:"pre",...(0,n.a)(),...e.components};return(0,o.jsxs)(o.Fragment,{children:[(0,o.jsx)(i.h1,{id:"voicemail",children:"Voicemail"}),"\n",(0,o.jsx)(i.hr,{}),"\n",(0,o.jsx)(i.p,{children:"This is configured through service body call handling, through your call strategy setting."}),"\n",(0,o.jsx)(i.h3,{id:"voicemail-recordings",children:"Voicemail Recordings"}),"\n",(0,o.jsx)(i.p,{children:'Recordings are now available in the admin portal under "Service Bodies > Call Records" for each respective area.'}),"\n",(0,o.jsx)(i.h3,{id:"voicemail-notifications-to-sms",children:"Voicemail Notifications to SMS"}),"\n",(0,o.jsx)(i.p,{children:'You can set up any volunteer to receive voicemail notifications.  Within the volunteer setting, set the dropdown Responder to "Enabled".'}),"\n",(0,o.jsx)(i.p,{children:"If you specify a Primary Contact Number, it will SMS a link to the recording that person when a voicemail is left.  You can also comma separate the values if you want it to go to more than one person."}),"\n",(0,o.jsx)(i.h3,{id:"voicemail-notifications-to-email",children:"Voicemail Notifications to Email"}),"\n",(0,o.jsx)(i.p,{children:"You can also optionally use email.  You will have to enable this by adding an email address under the Primary Contact Email.  You can optionally supply a list of comma separated emails for multiple recipients."}),"\n",(0,o.jsxs)(i.p,{children:["You will also need to ensure that the following settings are in your ",(0,o.jsx)(i.code,{children:"config.php"}),"."]}),"\n",(0,o.jsx)(i.pre,{children:(0,o.jsx)(i.code,{className:"language-php",children:"static $smtp_host = '';             // the smtp server\nstatic $smtp_username = '';         // the smtp username\nstatic $smtp_password = '';         // the smtp password\nstatic $smtp_secure = '';           // either ssl (port 486) or more securely tls (port 587)\nstatic $smtp_from_address = '';     // the address where the email will be sent from\nstatic $smtp_from_name = '';        // the label name on the from address\n"})}),"\n",(0,o.jsx)(i.p,{children:"If you need to, for some reason, to override the port here is another optional setting."}),"\n",(0,o.jsx)(i.pre,{children:(0,o.jsx)(i.code,{className:"language-php",children:"static $smtp_alt_port = '';         // enter the integer for the respective to use\n"})}),"\n",(0,o.jsx)(i.p,{children:"If you do not receive an email, check your server logs.  There should be some good information there.  Also the upgrade advisor should give you some information about what might be missing as long as $smtp_host is set."})]})}function m(e={}){const{wrapper:i}={...(0,n.a)(),...e.components};return i?(0,o.jsx)(i,{...e,children:(0,o.jsx)(d,{...e})}):d(e)}},1151:(e,i,t)=>{t.d(i,{Z:()=>r,a:()=>a});var o=t(7294);const n={},s=o.createContext(n);function a(e){const i=o.useContext(s);return o.useMemo((function(){return"function"==typeof e?e(i):{...i,...e}}),[i,e])}function r(e){let i;return i=e.disableParentContext?"function"==typeof e.components?e.components(n):e.components||n:a(e.components),o.createElement(s.Provider,{value:i},e.children)}}}]);