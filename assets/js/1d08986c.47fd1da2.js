"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[5560],{3905:(e,t,n)=>{n.d(t,{Zo:()=>c,kt:()=>d});var o=n(7294);function r(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function i(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function a(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?i(Object(n),!0).forEach((function(t){r(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):i(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function l(e,t){if(null==e)return{};var n,o,r=function(e,t){if(null==e)return{};var n,o,r={},i=Object.keys(e);for(o=0;o<i.length;o++)n=i[o],t.indexOf(n)>=0||(r[n]=e[n]);return r}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(o=0;o<i.length;o++)n=i[o],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(r[n]=e[n])}return r}var s=o.createContext({}),p=function(e){var t=o.useContext(s),n=t;return e&&(n="function"==typeof e?e(t):a(a({},t),e)),n},c=function(e){var t=p(e.components);return o.createElement(s.Provider,{value:t},e.children)},u={inlineCode:"code",wrapper:function(e){var t=e.children;return o.createElement(o.Fragment,{},t)}},m=o.forwardRef((function(e,t){var n=e.components,r=e.mdxType,i=e.originalType,s=e.parentName,c=l(e,["components","mdxType","originalType","parentName"]),m=p(n),d=r,f=m["".concat(s,".").concat(d)]||m[d]||u[d]||i;return n?o.createElement(f,a(a({ref:t},c),{},{components:n})):o.createElement(f,a({ref:t},c))}));function d(e,t){var n=arguments,r=t&&t.mdxType;if("string"==typeof e||r){var i=n.length,a=new Array(i);a[0]=m;var l={};for(var s in t)hasOwnProperty.call(t,s)&&(l[s]=t[s]);l.originalType=e,l.mdxType="string"==typeof e?e:r,a[1]=l;for(var p=2;p<i;p++)a[p]=n[p];return o.createElement.apply(null,a)}return o.createElement.apply(null,n)}m.displayName="MDXCreateElement"},3212:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>s,contentTitle:()=>a,default:()=>u,frontMatter:()=>i,metadata:()=>l,toc:()=>p});var o=n(7462),r=(n(7294),n(3905));const i={},a="Custom Extensions",l={unversionedId:"helpline/custom-extensions",id:"helpline/custom-extensions",title:"Custom Extensions",description:"---",source:"@site/docs/helpline/custom-extensions.md",sourceDirName:"helpline",slug:"/helpline/custom-extensions",permalink:"/helpline/custom-extensions",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/master/docs/docs/helpline/custom-extensions.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Checking the call routing",permalink:"/helpline/checking-call-routing"},next:{title:"Dialback",permalink:"/helpline/dialback"}},s={},p=[],c={toc:p};function u(e){let{components:t,...n}=e;return(0,r.kt)("wrapper",(0,o.Z)({},c,n,{components:t,mdxType:"MDXLayout"}),(0,r.kt)("h1",{id:"custom-extensions"},"Custom Extensions"),(0,r.kt)("hr",null),(0,r.kt)("p",null,"It's possible to make custom extensions with the use of a few settings."),(0,r.kt)("ol",null,(0,r.kt)("li",{parentName:"ol"},"Add an option to your ",(0,r.kt)("inlineCode",{parentName:"li"},"$digit_map_search_type")," that points to ",(0,r.kt)("inlineCode",{parentName:"li"},"SearchType::CUSTOM_EXTENSIONS"),"."),(0,r.kt)("li",{parentName:"ol"},"Add mappings in ",(0,r.kt)("inlineCode",{parentName:"li"},"$custom_extensions"),' for each extension you want to add. For example if you wanted to redirect extension "365" to a specific phone number you would do the below:')),(0,r.kt)("pre",null,(0,r.kt)("code",{parentName:"pre",className:"language-php"},"static $custom_extensions = [365 => '555-555-1212'];\n")),(0,r.kt)("ol",{start:3},(0,r.kt)("li",{parentName:"ol"},"Add an option for the mp3 or wav file prompt that will play back all the choices in the custom extensions' menu ",(0,r.kt)("inlineCode",{parentName:"li"},"$en_US_custom_extensions_greeting")," (be sure to specify recordings for each language that you offer).\nTo test, call in dial the digit map choice, and you should hear the audio file prompt playback. Enter the extension number followed by the pound sign (it might be good to inform the end-user in your prompt to press pound after they dial the appropriate extension).")),(0,r.kt)("p",null,"Also to note is that the main menu greeting will not inform the user about the custom extensions option, so you may also want to set ",(0,r.kt)("inlineCode",{parentName:"p"},"$en_US_greeting")," to include that information."))}u.isMDXComponent=!0}}]);