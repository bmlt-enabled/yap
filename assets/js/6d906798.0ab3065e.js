"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[6843],{3905:(e,t,r)=>{r.d(t,{Zo:()=>u,kt:()=>d});var n=r(7294);function a(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function i(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function o(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?i(Object(r),!0).forEach((function(t){a(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):i(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function s(e,t){if(null==e)return{};var r,n,a=function(e,t){if(null==e)return{};var r,n,a={},i=Object.keys(e);for(n=0;n<i.length;n++)r=i[n],t.indexOf(r)>=0||(a[r]=e[r]);return a}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(n=0;n<i.length;n++)r=i[n],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(a[r]=e[r])}return a}var c=n.createContext({}),l=function(e){var t=n.useContext(c),r=t;return e&&(r="function"==typeof e?e(t):o(o({},t),e)),r},u=function(e){var t=l(e.components);return n.createElement(c.Provider,{value:t},e.children)},p={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},m=n.forwardRef((function(e,t){var r=e.components,a=e.mdxType,i=e.originalType,c=e.parentName,u=s(e,["components","mdxType","originalType","parentName"]),m=l(r),d=a,h=m["".concat(c,".").concat(d)]||m[d]||p[d]||i;return r?n.createElement(h,o(o({ref:t},u),{},{components:r})):n.createElement(h,o({ref:t},u))}));function d(e,t){var r=arguments,a=t&&t.mdxType;if("string"==typeof e||a){var i=r.length,o=new Array(i);o[0]=m;var s={};for(var c in t)hasOwnProperty.call(t,c)&&(s[c]=t[c]);s.originalType=e,s.mdxType="string"==typeof e?e:a,o[1]=s;for(var l=2;l<i;l++)o[l]=r[l];return n.createElement.apply(null,o)}return n.createElement.apply(null,r)}m.displayName="MDXCreateElement"},5088:(e,t,r)=>{r.r(t),r.d(t,{assets:()=>c,contentTitle:()=>o,default:()=>p,frontMatter:()=>i,metadata:()=>s,toc:()=>l});var n=r(7462),a=(r(7294),r(3905));const i={title:"Meeting Search Radius",sidebar_position:7},o=void 0,s={unversionedId:"meeting-search/meeting-search-radius",id:"meeting-search/meeting-search-radius",title:"Meeting Search Radius",description:"---",source:"@site/docs/meeting-search/meeting-search-radius.md",sourceDirName:"meeting-search",slug:"/meeting-search/meeting-search-radius",permalink:"/meeting-search/meeting-search-radius",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/docs/docs/meeting-search/meeting-search-radius.md",tags:[],version:"current",sidebarPosition:7,frontMatter:{title:"Meeting Search Radius",sidebar_position:7},sidebar:"tutorialSidebar",previous:{title:"Ignoring Certain Formats",permalink:"/meeting-search/ignoring-certain-formats"},next:{title:"Mobile Check",permalink:"/meeting-search/mobile-check"}},c={},l=[],u={toc:l};function p(e){let{components:t,...r}=e;return(0,a.kt)("wrapper",(0,n.Z)({},u,r,{components:t,mdxType:"MDXLayout"}),(0,a.kt)("hr",null),(0,a.kt)("p",null,"Change the default meeting search radius, this can be in miles or a negative number which would set the radius at the first n results. You can change this in your ",(0,a.kt)("inlineCode",{parentName:"p"},"config.php")," with the following:"),(0,a.kt)("pre",null,(0,a.kt)("code",{parentName:"pre",className:"language-php"},"static $meeting_search_radius = 30;\n")),(0,a.kt)("p",null,"This would set the radius to a maximum of 30 miles."),(0,a.kt)("pre",null,(0,a.kt)("code",{parentName:"pre",className:"language-php"},"static $meeting_search_radius = -50;\n")),(0,a.kt)("p",null,"This would set the radius at the first 50 results and is the default."),(0,a.kt)("p",null,"More information on how the BMLT uses search radius is here: ",(0,a.kt)("a",{parentName:"p",href:"https://bmlt.app/how-auto-radius-works/"},"https://bmlt.app/how-auto-radius-works/")))}p.isMDXComponent=!0}}]);