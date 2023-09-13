"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[8093],{3905:(e,t,o)=>{o.d(t,{Zo:()=>p,kt:()=>h});var n=o(7294);function r(e,t,o){return t in e?Object.defineProperty(e,t,{value:o,enumerable:!0,configurable:!0,writable:!0}):e[t]=o,e}function i(e,t){var o=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),o.push.apply(o,n)}return o}function a(e){for(var t=1;t<arguments.length;t++){var o=null!=arguments[t]?arguments[t]:{};t%2?i(Object(o),!0).forEach((function(t){r(e,t,o[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(o)):i(Object(o)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(o,t))}))}return e}function l(e,t){if(null==e)return{};var o,n,r=function(e,t){if(null==e)return{};var o,n,r={},i=Object.keys(e);for(n=0;n<i.length;n++)o=i[n],t.indexOf(o)>=0||(r[o]=e[o]);return r}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(n=0;n<i.length;n++)o=i[n],t.indexOf(o)>=0||Object.prototype.propertyIsEnumerable.call(e,o)&&(r[o]=e[o])}return r}var s=n.createContext({}),c=function(e){var t=n.useContext(s),o=t;return e&&(o="function"==typeof e?e(t):a(a({},t),e)),o},p=function(e){var t=c(e.components);return n.createElement(s.Provider,{value:t},e.children)},m="mdxType",u={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},d=n.forwardRef((function(e,t){var o=e.components,r=e.mdxType,i=e.originalType,s=e.parentName,p=l(e,["components","mdxType","originalType","parentName"]),m=c(o),d=r,h=m["".concat(s,".").concat(d)]||m[d]||u[d]||i;return o?n.createElement(h,a(a({ref:t},p),{},{components:o})):n.createElement(h,a({ref:t},p))}));function h(e,t){var o=arguments,r=t&&t.mdxType;if("string"==typeof e||r){var i=o.length,a=new Array(i);a[0]=d;var l={};for(var s in t)hasOwnProperty.call(t,s)&&(l[s]=t[s]);l.originalType=e,l[m]="string"==typeof e?e:r,a[1]=l;for(var c=2;c<i;c++)a[c]=o[c];return n.createElement.apply(null,a)}return n.createElement.apply(null,o)}d.displayName="MDXCreateElement"},4210:(e,t,o)=>{o.r(t),o.d(t,{assets:()=>s,contentTitle:()=>a,default:()=>u,frontMatter:()=>i,metadata:()=>l,toc:()=>c});var n=o(7462),r=(o(7294),o(3905));const i={},a="Voicemail",l={unversionedId:"helpline/voicemail",id:"helpline/voicemail",title:"Voicemail",description:"---",source:"@site/docs/helpline/voicemail.md",sourceDirName:"helpline",slug:"/helpline/voicemail",permalink:"/helpline/voicemail",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/helpline/voicemail.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Aggregator Helpline Routing",permalink:"/helpline/tomato-helpline-routing"},next:{title:"Volunteer Auto Answer",permalink:"/helpline/volunteer-auto-answer"}},s={},c=[{value:"Voicemail Recordings",id:"voicemail-recordings",level:3},{value:"Voicemail Notifications to SMS",id:"voicemail-notifications-to-sms",level:3},{value:"Voicemail Notifications to Email",id:"voicemail-notifications-to-email",level:3}],p={toc:c},m="wrapper";function u(e){let{components:t,...o}=e;return(0,r.kt)(m,(0,n.Z)({},p,o,{components:t,mdxType:"MDXLayout"}),(0,r.kt)("h1",{id:"voicemail"},"Voicemail"),(0,r.kt)("hr",null),(0,r.kt)("p",null,"This is configured through service body call handling, through your call strategy setting."),(0,r.kt)("h3",{id:"voicemail-recordings"},"Voicemail Recordings"),(0,r.kt)("p",null,'Recordings are now available in the admin portal under "Service Bodies > Call Records" for each respective area.'),(0,r.kt)("h3",{id:"voicemail-notifications-to-sms"},"Voicemail Notifications to SMS"),(0,r.kt)("p",null,'You can set up any volunteer to receive voicemail notifications.  Within the volunteer setting, set the dropdown Responder to "Enabled".'),(0,r.kt)("p",null,"If you specify a Primary Contact Number, it will SMS a link to the recording that person when a voicemail is left.  You can also comma separate the values if you want it to go to more than one person."),(0,r.kt)("h3",{id:"voicemail-notifications-to-email"},"Voicemail Notifications to Email"),(0,r.kt)("p",null,"You can also optionally use email.  You will have to enable this by adding an email address under the Primary Contact Email.  You can optionally supply a list of comma separated emails for multiple recipients."),(0,r.kt)("p",null,"You will also need to ensure that the following settings are in your ",(0,r.kt)("inlineCode",{parentName:"p"},"config.php"),"."),(0,r.kt)("pre",null,(0,r.kt)("code",{parentName:"pre",className:"language-php"},"static $smtp_host = '';             // the smtp server\nstatic $smtp_username = '';         // the smtp username\nstatic $smtp_password = '';         // the smtp password\nstatic $smtp_secure = '';           // either ssl (port 486) or more securely tls (port 587)\nstatic $smtp_from_address = '';     // the address where the email will be sent from\nstatic $smtp_from_name = '';        // the label name on the from address\n")),(0,r.kt)("p",null,"If you need to, for some reason, to override the port here is another optional setting."),(0,r.kt)("pre",null,(0,r.kt)("code",{parentName:"pre",className:"language-php"},"static $smtp_alt_port = '';         // enter the integer for the respective to use\n")),(0,r.kt)("p",null,"If you do not receive an email, check your server logs.  There should be some good information there.  Also the upgrade advisor should give you some information about what might be missing as long as $smtp_host is set."))}u.isMDXComponent=!0}}]);