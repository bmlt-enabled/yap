"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[7188],{3905:(e,t,r)=>{r.d(t,{Zo:()=>s,kt:()=>f});var n=r(7294);function o(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function a(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function l(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?a(Object(r),!0).forEach((function(t){o(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):a(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function i(e,t){if(null==e)return{};var r,n,o=function(e,t){if(null==e)return{};var r,n,o={},a=Object.keys(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||(o[r]=e[r]);return o}(e,t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(o[r]=e[r])}return o}var p=n.createContext({}),c=function(e){var t=n.useContext(p),r=t;return e&&(r="function"==typeof e?e(t):l(l({},t),e)),r},s=function(e){var t=c(e.components);return n.createElement(p.Provider,{value:t},e.children)},u={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},d=n.forwardRef((function(e,t){var r=e.components,o=e.mdxType,a=e.originalType,p=e.parentName,s=i(e,["components","mdxType","originalType","parentName"]),d=c(r),f=o,m=d["".concat(p,".").concat(f)]||d[f]||u[f]||a;return r?n.createElement(m,l(l({ref:t},s),{},{components:r})):n.createElement(m,l({ref:t},s))}));function f(e,t){var r=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var a=r.length,l=new Array(a);l[0]=d;var i={};for(var p in t)hasOwnProperty.call(t,p)&&(i[p]=t[p]);i.originalType=e,i.mdxType="string"==typeof e?e:o,l[1]=i;for(var c=2;c<a;c++)l[c]=r[c];return n.createElement.apply(null,l)}return n.createElement.apply(null,r)}d.displayName="MDXCreateElement"},7172:(e,t,r)=>{r.r(t),r.d(t,{assets:()=>p,contentTitle:()=>l,default:()=>u,frontMatter:()=>a,metadata:()=>i,toc:()=>c});var n=r(7462),o=(r(7294),r(3905));const a={},l="State/Province Lookup",i={unversionedId:"general/stateprovince-lookup",id:"general/stateprovince-lookup",title:"State/Province Lookup",description:"---",source:"@site/docs/general/stateprovince-lookup.md",sourceDirName:"general",slug:"/general/stateprovince-lookup",permalink:"/general/stateprovince-lookup",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/master/docs/docs/general/stateprovince-lookup.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Postal Code Lengths",permalink:"/general/postal-code-lengths"},next:{title:"Tollfree Province Bias",permalink:"/general/tollfree-province-bias"}},p={},c=[],s={toc:c};function u(e){let{components:t,...r}=e;return(0,o.kt)("wrapper",(0,n.Z)({},s,r,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"stateprovince-lookup"},"State/Province Lookup"),(0,o.kt)("hr",null),(0,o.kt)("p",null,"It may be that your instance needs to search multiple states. By default searches will be biased towards the local number state (unless it's tollfree).  To enable province lookup set the ",(0,o.kt)("inlineCode",{parentName:"p"},"$province_lookup"),", variable to ",(0,o.kt)("inlineCode",{parentName:"p"},"true")," in the ",(0,o.kt)("inlineCode",{parentName:"p"},"config.php")," file.  "),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"static $province_lookup = true;\n")),(0,o.kt)("p",null,"You can also specify a predetermined list of provinces / states. If you use this setting, then a speech gathering will be replaced with a numbered menu of states. Currently it would support up to 9 states in the list. To enable this do the following for example (the order in the list and position is the number that will be said to be pressed in the menu):"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $province_lookup = true;\nstatic $province_lookup_list = ["North Carolina", "South Carolina"];\n')))}u.isMDXComponent=!0}}]);