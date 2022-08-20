"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[6310],{3905:(e,t,r)=>{r.d(t,{Zo:()=>u,kt:()=>h});var n=r(7294);function l(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function a(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function i(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?a(Object(r),!0).forEach((function(t){l(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):a(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function o(e,t){if(null==e)return{};var r,n,l=function(e,t){if(null==e)return{};var r,n,l={},a=Object.keys(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||(l[r]=e[r]);return l}(e,t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(l[r]=e[r])}return l}var c=n.createContext({}),p=function(e){var t=n.useContext(c),r=t;return e&&(r="function"==typeof e?e(t):i(i({},t),e)),r},u=function(e){var t=p(e.components);return n.createElement(c.Provider,{value:t},e.children)},s={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},d=n.forwardRef((function(e,t){var r=e.components,l=e.mdxType,a=e.originalType,c=e.parentName,u=o(e,["components","mdxType","originalType","parentName"]),d=p(r),h=l,f=d["".concat(c,".").concat(h)]||d[h]||s[h]||a;return r?n.createElement(f,i(i({ref:t},u),{},{components:r})):n.createElement(f,i({ref:t},u))}));function h(e,t){var r=arguments,l=t&&t.mdxType;if("string"==typeof e||l){var a=r.length,i=new Array(a);i[0]=d;var o={};for(var c in t)hasOwnProperty.call(t,c)&&(o[c]=t[c]);o.originalType=e,o.mdxType="string"==typeof e?e:l,i[1]=o;for(var p=2;p<a;p++)i[p]=r[p];return n.createElement.apply(null,i)}return n.createElement.apply(null,r)}d.displayName="MDXCreateElement"},6718:(e,t,r)=>{r.r(t),r.d(t,{assets:()=>c,contentTitle:()=>i,default:()=>s,frontMatter:()=>a,metadata:()=>o,toc:()=>p});var n=r(7462),l=(r(7294),r(3905));const a={layout:"default",title:"Helpline Search Radius",nav_order:7,parent:"Helpline / Volunteer Routing"},i=void 0,o={unversionedId:"helpline/helpline-search-radius",id:"helpline/helpline-search-radius",title:"Helpline Search Radius",description:"Helpline Search Radius",source:"@site/docs/helpline/helpline-search-radius.md",sourceDirName:"helpline",slug:"/helpline/helpline-search-radius",permalink:"/helpline/helpline-search-radius",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/docs/docs/helpline/helpline-search-radius.md",tags:[],version:"current",frontMatter:{layout:"default",title:"Helpline Search Radius",nav_order:7,parent:"Helpline / Volunteer Routing"},sidebar:"tutorialSidebar",previous:{title:"Volunteer Routing",permalink:"/helpline/volunteer-routing"},next:{title:"Connectors",permalink:"/miscellaneous/connectors"}},c={},p=[{value:"Helpline Search Radius",id:"helpline-search-radius",level:2}],u={toc:p};function s(e){let{components:t,...r}=e;return(0,l.kt)("wrapper",(0,n.Z)({},u,r,{components:t,mdxType:"MDXLayout"}),(0,l.kt)("h2",{id:"helpline-search-radius"},"Helpline Search Radius"),(0,l.kt)("hr",null),(0,l.kt)("p",null,"Change the default helpline search radius, this is in miles. You can change this in your ",(0,l.kt)("inlineCode",{parentName:"p"},"config.php")," with the following:"),(0,l.kt)("pre",null,(0,l.kt)("code",{parentName:"pre",className:"language-php"},"static $helpline_search_radius = 30;\n")),(0,l.kt)("p",null,"This would set the radius to a maximum of 30 miles and is the default."))}s.isMDXComponent=!0}}]);