"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[6263],{3905:(e,t,n)=>{n.d(t,{Zo:()=>s,kt:()=>m});var r=n(7294);function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function i(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function l(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?i(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):i(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function a(e,t){if(null==e)return{};var n,r,o=function(e,t){if(null==e)return{};var n,r,o={},i=Object.keys(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||(o[n]=e[n]);return o}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(o[n]=e[n])}return o}var u=r.createContext({}),c=function(e){var t=r.useContext(u),n=t;return e&&(n="function"==typeof e?e(t):l(l({},t),e)),n},s=function(e){var t=c(e.components);return r.createElement(u.Provider,{value:t},e.children)},p="mdxType",d={inlineCode:"code",wrapper:function(e){var t=e.children;return r.createElement(r.Fragment,{},t)}},h=r.forwardRef((function(e,t){var n=e.components,o=e.mdxType,i=e.originalType,u=e.parentName,s=a(e,["components","mdxType","originalType","parentName"]),p=c(n),h=o,m=p["".concat(u,".").concat(h)]||p[h]||d[h]||i;return n?r.createElement(m,l(l({ref:t},s),{},{components:n})):r.createElement(m,l({ref:t},s))}));function m(e,t){var n=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var i=n.length,l=new Array(i);l[0]=h;var a={};for(var u in t)hasOwnProperty.call(t,u)&&(a[u]=t[u]);a.originalType=e,a[p]="string"==typeof e?e:o,l[1]=a;for(var c=2;c<i;c++)l[c]=n[c];return r.createElement.apply(null,l)}return r.createElement.apply(null,n)}h.displayName="MDXCreateElement"},4746:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>u,contentTitle:()=>l,default:()=>d,frontMatter:()=>i,metadata:()=>a,toc:()=>c});var r=n(7462),o=(n(7294),n(3905));const i={},l="Volunteer Routing",a={unversionedId:"helpline/volunteer-routing",id:"helpline/volunteer-routing",title:"Volunteer Routing",description:"---",source:"@site/docs/helpline/volunteer-routing.md",sourceDirName:"helpline",slug:"/helpline/volunteer-routing",permalink:"/helpline/volunteer-routing",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/helpline/volunteer-routing.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Volunteer Auto Answer",permalink:"/helpline/volunteer-auto-answer"},next:{title:"Connectors",permalink:"/miscellaneous/connectors"}},u={},c=[],s={toc:c},p="wrapper";function d(e){let{components:t,...n}=e;return(0,o.kt)(p,(0,r.Z)({},s,n,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"volunteer-routing"},"Volunteer Routing"),(0,o.kt)("hr",null),(0,o.kt)("p",null,"Incompatible with Yap 1.x Volunteer Dialers, you will have reconfigure your setup."),(0,o.kt)("p",null,"1) You will need to ensure that the following ",(0,o.kt)("inlineCode",{parentName:"p"},"config.php"),' parameters are set.  They should be a service body admin that will be responsible for reading and writing data back to your BMLT.  This will not work with the "Server Administrator" account.'),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $bmlt_username = "";\nstatic $bmlt_password = "";\n')),(0,o.kt)("p",null,"2) You will need to specify Twilio API parameters.  You can find this on your account dashboard when you login into Twilio."),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $twilio_account_sid = "";\nstatic $twilio_auth_token = "";\n')),(0,o.kt)("p",null,'3) Head over to your admin login page.  https://your-yap-instance/admin.\n4) Login with any credentials from your BMLT server.\n5) Go to the Service Bodies tab and click "Configure".  From there you should see a check box to enable Volunteer Routing.  Check it off and save.\n6) Go to Volunteers, and you should see that service body in the dropdown, and select it.\n7) Click Add Volunteer.  Fill out the Name field, and then click the "+" to expand out the rest of the details.  You should be able to start populating the number and shift information.  You will also have to click "Enable" in the bottom right.  Once you are done, click "Save Volunteers".\n8) You can also sort the sequence by dragging and dropping the volunteer cards.\n9) Go to Schedules to preview your changes.  Select your service body from the dropdown, and it should render onto the calendar.\n10) You can now test to see if things are working.'),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre"},'* Volunteer Routing Redirect: You do this by setting in the Service Body Call Handling the Volunteer Routing mechanism to "Volunteers Redirect" and specifying the respective Service Body Id in the "Volunteers Redirect Id" field.\n* Forced Caller Id: This setting changes the outgoing display caller id.\n* Call Timeout: This is the number of seconds before trying the next number for volunteer routing.\n')))}d.isMDXComponent=!0}}]);