"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[9722],{3905:(e,t,r)=>{r.d(t,{Zo:()=>u,kt:()=>f});var n=r(7294);function o(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function a(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function i(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?a(Object(r),!0).forEach((function(t){o(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):a(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function s(e,t){if(null==e)return{};var r,n,o=function(e,t){if(null==e)return{};var r,n,o={},a=Object.keys(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||(o[r]=e[r]);return o}(e,t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(o[r]=e[r])}return o}var l=n.createContext({}),c=function(e){var t=n.useContext(l),r=t;return e&&(r="function"==typeof e?e(t):i(i({},t),e)),r},u=function(e){var t=c(e.components);return n.createElement(l.Provider,{value:t},e.children)},p="mdxType",m={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},d=n.forwardRef((function(e,t){var r=e.components,o=e.mdxType,a=e.originalType,l=e.parentName,u=s(e,["components","mdxType","originalType","parentName"]),p=c(r),d=o,f=p["".concat(l,".").concat(d)]||p[d]||m[d]||a;return r?n.createElement(f,i(i({ref:t},u),{},{components:r})):n.createElement(f,i({ref:t},u))}));function f(e,t){var r=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var a=r.length,i=new Array(a);i[0]=d;var s={};for(var l in t)hasOwnProperty.call(t,l)&&(s[l]=t[l]);s.originalType=e,s[p]="string"==typeof e?e:o,i[1]=s;for(var c=2;c<a;c++)i[c]=r[c];return n.createElement.apply(null,i)}return n.createElement.apply(null,r)}d.displayName="MDXCreateElement"},8230:(e,t,r)=>{r.r(t),r.d(t,{assets:()=>l,contentTitle:()=>i,default:()=>m,frontMatter:()=>a,metadata:()=>s,toc:()=>c});var n=r(7462),o=(r(7294),r(3905));const a={},i="Venue Options",s={unversionedId:"meeting-search/venue-options",id:"meeting-search/venue-options",title:"Venue Options",description:"Temporary Closures",source:"@site/docs/meeting-search/venue-options.md",sourceDirName:"meeting-search",slug:"/meeting-search/venue-options",permalink:"/meeting-search/venue-options",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/master/docs/docs/meeting-search/venue-options.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Tomato Meeting Search",permalink:"/meeting-search/tomato-meeting-search"},next:{title:"Announce Service Body",permalink:"/helpline/announce_servicebody_volunteer_routing"}},l={},c=[{value:"Temporary Closures",id:"temporary-closures",level:2},{value:"Virtual Meetings",id:"virtual-meetings",level:2}],u={toc:c},p="wrapper";function m(e){let{components:t,...r}=e;return(0,o.kt)(p,(0,n.Z)({},u,r,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"venue-options"},"Venue Options"),(0,o.kt)("h2",{id:"temporary-closures"},"Temporary Closures"),(0,o.kt)("p",null,"If a meeting is marked with the TC format then it will be excluded from results. If it marked as a Virtual Meetings as well then it will be returned by with no physical address details."),(0,o.kt)("p",null,"If you want the text from the format description to be returned add TC to ",(0,o.kt)("inlineCode",{parentName:"p"},"include_format_details"),". Example:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"static $include_format_details = ['TC', 'VM', 'HY'];\n")),(0,o.kt)("p",null,"You can include any format here.  For example if you wanted to show whether a meeting is Open or Closed you could do that by including the format code in this setting."),(0,o.kt)("p",null,"If you want to change the description of some of the specific formats you can change the format description for that specific language in your root server."),(0,o.kt)("h2",{id:"virtual-meetings"},"Virtual Meetings"),(0,o.kt)("p",null,"If a meeting is marked as VM or HY with a format then you should be able to automatically have the virtual_meeting_link and phone_meeting_number returned in the SMS. If you want the links (for some reason), to be said in voice responses, you can enable this with say_links set to true. If you want the text from the format description to be returned add VM or HY to include_format_details. Example:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"static $include_format_details = ['TC', 'VM', 'HY'];\n")),(0,o.kt)("p",null,"If you want to change the description of some of the specific formats you can change the format description for that specific language in your root server."))}m.isMDXComponent=!0}}]);