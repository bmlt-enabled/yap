"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[3422],{3905:function(e,t,r){r.d(t,{Zo:function(){return l},kt:function(){return f}});var n=r(7294);function o(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function i(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function a(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?i(Object(r),!0).forEach((function(t){o(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):i(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function s(e,t){if(null==e)return{};var r,n,o=function(e,t){if(null==e)return{};var r,n,o={},i=Object.keys(e);for(n=0;n<i.length;n++)r=i[n],t.indexOf(r)>=0||(o[r]=e[r]);return o}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(n=0;n<i.length;n++)r=i[n],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(o[r]=e[r])}return o}var u=n.createContext({}),c=function(e){var t=n.useContext(u),r=t;return e&&(r="function"==typeof e?e(t):a(a({},t),e)),r},l=function(e){var t=c(e.components);return n.createElement(u.Provider,{value:t},e.children)},p={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},d=n.forwardRef((function(e,t){var r=e.components,o=e.mdxType,i=e.originalType,u=e.parentName,l=s(e,["components","mdxType","originalType","parentName"]),d=c(r),f=o,m=d["".concat(u,".").concat(f)]||d[f]||p[f]||i;return r?n.createElement(m,a(a({ref:t},l),{},{components:r})):n.createElement(m,a({ref:t},l))}));function f(e,t){var r=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var i=r.length,a=new Array(i);a[0]=d;var s={};for(var u in t)hasOwnProperty.call(t,u)&&(s[u]=t[u]);s.originalType=e,s.mdxType="string"==typeof e?e:o,a[1]=s;for(var c=2;c<i;c++)a[c]=r[c];return n.createElement.apply(null,a)}return n.createElement.apply(null,r)}d.displayName="MDXCreateElement"},7403:function(e,t,r){r.r(t),r.d(t,{assets:function(){return l},contentTitle:function(){return u},default:function(){return f},frontMatter:function(){return s},metadata:function(){return c},toc:function(){return p}});var n=r(7462),o=r(3366),i=(r(7294),r(3905)),a=["components"],s={title:"Sorting Results",sidebar_position:14},u=void 0,c={unversionedId:"meeting-search/sorting-results",id:"meeting-search/sorting-results",title:"Sorting Results",description:"---",source:"@site/docs/meeting-search/sorting-results.md",sourceDirName:"meeting-search",slug:"/meeting-search/sorting-results",permalink:"/meeting-search/sorting-results",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/docs/docs/meeting-search/sorting-results.md",tags:[],version:"current",sidebarPosition:14,frontMatter:{title:"Sorting Results",sidebar_position:14},sidebar:"tutorialSidebar",previous:{title:"SMS Summary Page",permalink:"/meeting-search/sms-summary-page"},next:{title:"Checking the call routing",permalink:"/helpline/checking-call-routing"}},l={},p=[],d={toc:p};function f(e){var t=e.components,r=(0,o.Z)(e,a);return(0,i.kt)("wrapper",(0,n.Z)({},d,r,{components:t,mdxType:"MDXLayout"}),(0,i.kt)("hr",null),(0,i.kt)("p",null,"By default the results will be sorted starting with today and then moving on to the next result.  If latitude and longitude are not used in the meeting query, the first meeting latitude and longitude will be the assumed timezone."),(0,i.kt)("p",null,"If you wanted to hardcode the sorting to start with another day you could use say for Wednesday use ",(0,i.kt)("inlineCode",{parentName:"p"},"static $meeting_result_sort = 4;"),"."),(0,i.kt)("p",null,"Or you can keep a more natural flow by setting it to 1 which would sort Sunday to Saturday."))}f.isMDXComponent=!0}}]);