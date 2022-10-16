"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[6263],{3905:(e,t,n)=>{n.d(t,{Zo:()=>s,kt:()=>h});var r=n(7294);function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function i(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function a(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?i(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):i(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function l(e,t){if(null==e)return{};var n,r,o=function(e,t){if(null==e)return{};var n,r,o={},i=Object.keys(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||(o[n]=e[n]);return o}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(o[n]=e[n])}return o}var u=r.createContext({}),c=function(e){var t=r.useContext(u),n=t;return e&&(n="function"==typeof e?e(t):a(a({},t),e)),n},s=function(e){var t=c(e.components);return r.createElement(u.Provider,{value:t},e.children)},p={inlineCode:"code",wrapper:function(e){var t=e.children;return r.createElement(r.Fragment,{},t)}},d=r.forwardRef((function(e,t){var n=e.components,o=e.mdxType,i=e.originalType,u=e.parentName,s=l(e,["components","mdxType","originalType","parentName"]),d=c(n),h=o,m=d["".concat(u,".").concat(h)]||d[h]||p[h]||i;return n?r.createElement(m,a(a({ref:t},s),{},{components:n})):r.createElement(m,a({ref:t},s))}));function h(e,t){var n=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var i=n.length,a=new Array(i);a[0]=d;var l={};for(var u in t)hasOwnProperty.call(t,u)&&(l[u]=t[u]);l.originalType=e,l.mdxType="string"==typeof e?e:o,a[1]=l;for(var c=2;c<i;c++)a[c]=n[c];return r.createElement.apply(null,a)}return r.createElement.apply(null,n)}d.displayName="MDXCreateElement"},4746:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>u,contentTitle:()=>a,default:()=>p,frontMatter:()=>i,metadata:()=>l,toc:()=>c});var r=n(7462),o=(n(7294),n(3905));const i={title:"Volunteer Routing",sidebar_position:15},a=void 0,l={unversionedId:"helpline/volunteer-routing",id:"helpline/volunteer-routing",title:"Volunteer Routing",description:"---",source:"@site/docs/helpline/volunteer-routing.md",sourceDirName:"helpline",slug:"/helpline/volunteer-routing",permalink:"/helpline/volunteer-routing",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/master/docs/docs/helpline/volunteer-routing.md",tags:[],version:"current",sidebarPosition:15,frontMatter:{title:"Volunteer Routing",sidebar_position:15},sidebar:"tutorialSidebar",previous:{title:"Voicemail",permalink:"/helpline/voicemail"},next:{title:"Dialback",permalink:"/helpline/dialback"}},u={},c=[],s={toc:c};function p(e){let{components:t,...n}=e;return(0,o.kt)("wrapper",(0,r.Z)({},s,n,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("hr",null),(0,o.kt)("p",null,"Incompatible with Yap 1.x Volunteer Dialers, you will have reconfigure your setup."),(0,o.kt)("p",null,"1) You will need to ensure that the following ",(0,o.kt)("inlineCode",{parentName:"p"},"config.php"),' parameters are set.  They should be a service body admin that will be responsible for reading and writing data back to your BMLT.  This will not work with the "Server Administrator" account.'),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $bmlt_username = "";\nstatic $bmlt_password = "";\n')),(0,o.kt)("p",null,"2) You will need to specify Twilio API parameters.  You can find this on your account dashboard when you login into Twilio."),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $twilio_account_sid = "";\nstatic $twilio_auth_token = "";\n')),(0,o.kt)("p",null,'3) Head over to your admin login page.  https://your-yap-instance/admin.\n4) Login with any credentials from your BMLT server.\n5) Go to the Service Bodies tab and click "Configure".  From there you should see a check box to enable Volunteer Routing.  Check it off and save.\n6) Go to Volunteers, and you should see that service body in the dropdown, and select it.\n7) Click Add Volunteer.  Fill out the Name field, and then click the "+" to expand out the rest of the details.  You should be able to start populating the number and shift information.  You will also have to click "Enable" in the bottom right.  Once you are done, click "Save Volunteers".\n8) You can also sort the sequence by dragging and dropping the volunteer cards.\n9) Go to Schedules to preview your changes.  Select your service body from the dropdown, and it should render onto the calendar.\n10) You can now test to see if things are working.'),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre"},'* Volunteer Routing Redirect: You do this by setting in the Service Body Call Handling the Volunteer Routing mechanism to "Volunteers Redirect" and specifying the respective Service Body Id in the "Volunteers Redirect Id" field.\n* Forced Caller Id: This setting changes the outgoing display caller id.\n* Call Timeout: This is the number of seconds before trying the next number for volunteer routing.\n')))}p.isMDXComponent=!0}}]);