"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[7471],{3905:(e,t,n)=>{n.d(t,{Zo:()=>u,kt:()=>m});var r=n(7294);function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function l(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function c(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?l(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):l(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function i(e,t){if(null==e)return{};var n,r,o=function(e,t){if(null==e)return{};var n,r,o={},l=Object.keys(e);for(r=0;r<l.length;r++)n=l[r],t.indexOf(n)>=0||(o[n]=e[n]);return o}(e,t);if(Object.getOwnPropertySymbols){var l=Object.getOwnPropertySymbols(e);for(r=0;r<l.length;r++)n=l[r],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(o[n]=e[n])}return o}var a=r.createContext({}),p=function(e){var t=r.useContext(a),n=t;return e&&(n="function"==typeof e?e(t):c(c({},t),e)),n},u=function(e){var t=p(e.components);return r.createElement(a.Provider,{value:t},e.children)},s="mdxType",h={inlineCode:"code",wrapper:function(e){var t=e.children;return r.createElement(r.Fragment,{},t)}},f=r.forwardRef((function(e,t){var n=e.components,o=e.mdxType,l=e.originalType,a=e.parentName,u=i(e,["components","mdxType","originalType","parentName"]),s=p(n),f=o,m=s["".concat(a,".").concat(f)]||s[f]||h[f]||l;return n?r.createElement(m,c(c({ref:t},u),{},{components:n})):r.createElement(m,c({ref:t},u))}));function m(e,t){var n=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var l=n.length,c=new Array(l);c[0]=f;var i={};for(var a in t)hasOwnProperty.call(t,a)&&(i[a]=t[a]);i.originalType=e,i[s]="string"==typeof e?e:o,c[1]=i;for(var p=2;p<l;p++)c[p]=n[p];return r.createElement.apply(null,c)}return r.createElement.apply(null,n)}f.displayName="MDXCreateElement"},7283:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>a,contentTitle:()=>c,default:()=>h,frontMatter:()=>l,metadata:()=>i,toc:()=>p});var r=n(7462),o=(n(7294),n(3905));const l={},c="Checking the call routing",i={unversionedId:"helpline/checking-call-routing",id:"helpline/checking-call-routing",title:"Checking the call routing",description:"---",source:"@site/docs/helpline/checking-call-routing.md",sourceDirName:"helpline",slug:"/helpline/checking-call-routing",permalink:"/helpline/checking-call-routing",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/master/docs/docs/helpline/checking-call-routing.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Announce Service Body",permalink:"/helpline/announce_servicebody_volunteer_routing"},next:{title:"Custom Extensions",permalink:"/helpline/custom-extensions"}},a={},p=[],u={toc:p},s="wrapper";function h(e){let{components:t,...n}=e;return(0,o.kt)(s,(0,r.Z)({},u,n,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"checking-the-call-routing"},"Checking the call routing"),(0,o.kt)("hr",null),(0,o.kt)("p",null,"There is a very simple way to check where a call would be routed to."),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-shell"},"curl https://example.com/yap/helpline-search.php?Digits=Turkey,NC\n")))}h.isMDXComponent=!0}}]);