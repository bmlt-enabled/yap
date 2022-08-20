"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[247],{3905:(e,t,r)=>{r.d(t,{Zo:()=>s,kt:()=>m});var n=r(7294);function o(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function i(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function a(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?i(Object(r),!0).forEach((function(t){o(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):i(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function l(e,t){if(null==e)return{};var r,n,o=function(e,t){if(null==e)return{};var r,n,o={},i=Object.keys(e);for(n=0;n<i.length;n++)r=i[n],t.indexOf(r)>=0||(o[r]=e[r]);return o}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(n=0;n<i.length;n++)r=i[n],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(o[r]=e[r])}return o}var c=n.createContext({}),p=function(e){var t=n.useContext(c),r=t;return e&&(r="function"==typeof e?e(t):a(a({},t),e)),r},s=function(e){var t=p(e.components);return n.createElement(c.Provider,{value:t},e.children)},u={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},g=n.forwardRef((function(e,t){var r=e.components,o=e.mdxType,i=e.originalType,c=e.parentName,s=l(e,["components","mdxType","originalType","parentName"]),g=p(r),m=o,d=g["".concat(c,".").concat(m)]||g[m]||u[m]||i;return r?n.createElement(d,a(a({ref:t},s),{},{components:r})):n.createElement(d,a({ref:t},s))}));function m(e,t){var r=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var i=r.length,a=new Array(i);a[0]=g;var l={};for(var c in t)hasOwnProperty.call(t,c)&&(l[c]=t[c]);l.originalType=e,l.mdxType="string"==typeof e?e:o,a[1]=l;for(var p=2;p<i;p++)a[p]=r[p];return n.createElement.apply(null,a)}return n.createElement.apply(null,r)}g.displayName="MDXCreateElement"},8781:(e,t,r)=>{r.r(t),r.d(t,{assets:()=>c,contentTitle:()=>a,default:()=>u,frontMatter:()=>i,metadata:()=>l,toc:()=>p});var n=r(7462),o=(r(7294),r(3905));const i={title:"Voice Greeting",sidebar_position:10},a=void 0,l={unversionedId:"general/voice-greeting",id:"general/voice-greeting",title:"Voice Greeting",description:"---",source:"@site/docs/general/voice-greeting.md",sourceDirName:"general",slug:"/general/voice-greeting",permalink:"/general/voice-greeting",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/docs/docs/general/voice-greeting.md",tags:[],version:"current",sidebarPosition:10,frontMatter:{title:"Voice Greeting",sidebar_position:10},sidebar:"tutorialSidebar",previous:{title:"Tollfree Bias",permalink:"/general/tollfree-bias"},next:{title:"Voice Recognition Optimizations",permalink:"/general/voice-recognition-optimizations"}},c={},p=[],s={toc:p};function u(e){let{components:t,...r}=e;return(0,o.kt)("wrapper",(0,n.Z)({},s,r,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("hr",null),(0,o.kt)("p",null,"It's possible to record a custom voice prompt and have it play back instead of the traditional voice engine.  Set the following:"),(0,o.kt)("p",null,"*Keep in mind that this will override the main menu as well, so you should record the relevant prompts (i.e. press 1 to find someone to talk too... press 2 to find a meeting)"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $en_US_greeting = "https://example.com/your-recorded-greeting.mp3"\n')),(0,o.kt)("p",null,"You can also set a custom greeting for voicemail."),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $en_US_voicemail_greeting = "https://example.com/your-recorded-greeting.mp3"\n')),(0,o.kt)("p",null,"These settings are overridable from within each service body call handling."))}u.isMDXComponent=!0}}]);