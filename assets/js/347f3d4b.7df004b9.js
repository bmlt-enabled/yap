"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[5385],{3905:function(e,t,n){n.d(t,{Zo:function(){return p},kt:function(){return m}});var r=n(7294);function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function i(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function a(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?i(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):i(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function l(e,t){if(null==e)return{};var n,r,o=function(e,t){if(null==e)return{};var n,r,o={},i=Object.keys(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||(o[n]=e[n]);return o}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(o[n]=e[n])}return o}var s=r.createContext({}),c=function(e){var t=r.useContext(s),n=t;return e&&(n="function"==typeof e?e(t):a(a({},t),e)),n},p=function(e){var t=c(e.components);return r.createElement(s.Provider,{value:t},e.children)},u={inlineCode:"code",wrapper:function(e){var t=e.children;return r.createElement(r.Fragment,{},t)}},f=r.forwardRef((function(e,t){var n=e.components,o=e.mdxType,i=e.originalType,s=e.parentName,p=l(e,["components","mdxType","originalType","parentName"]),f=c(n),m=o,d=f["".concat(s,".").concat(m)]||f[m]||u[m]||i;return n?r.createElement(d,a(a({ref:t},p),{},{components:n})):r.createElement(d,a({ref:t},p))}));function m(e,t){var n=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var i=n.length,a=new Array(i);a[0]=f;var l={};for(var s in t)hasOwnProperty.call(t,s)&&(l[s]=t[s]);l.originalType=e,l.mdxType="string"==typeof e?e:o,a[1]=l;for(var c=2;c<i;c++)a[c]=n[c];return r.createElement.apply(null,a)}return r.createElement.apply(null,n)}f.displayName="MDXCreateElement"},464:function(e,t,n){n.r(t),n.d(t,{frontMatter:function(){return l},contentTitle:function(){return s},metadata:function(){return c},toc:function(){return p},default:function(){return f}});var r=n(7462),o=n(3366),i=(n(7294),n(3905)),a=["components"],l={title:"Post Call Options",sidebar_position:9},s=void 0,c={unversionedId:"meeting-search/post-call-options",id:"meeting-search/post-call-options",title:"Post Call Options",description:"---",source:"@site/docs/meeting-search/post-call-options.md",sourceDirName:"meeting-search",slug:"/meeting-search/post-call-options",permalink:"/meeting-search/post-call-options",editUrl:"https://github.com/bmlt-enabled/yap/edit/docs/docs/meeting-search/post-call-options.md",tags:[],version:"current",sidebarPosition:9,frontMatter:{title:"Post Call Options",sidebar_position:9},sidebar:"tutorialSidebar",previous:{title:"Mobile Check",permalink:"/meeting-search/mobile-check"},next:{title:"Results Counts Maximums",permalink:"/meeting-search/results-counts-maximums"}},p=[{value:"Making SMS results for voice calls optional",id:"making-sms-results-for-voice-calls-optional",children:[],level:2},{value:"Infinite Searches",id:"infinite-searches",children:[],level:2}],u={toc:p};function f(e){var t=e.components,n=(0,o.Z)(e,a);return(0,i.kt)("wrapper",(0,r.Z)({},u,n,{components:t,mdxType:"MDXLayout"}),(0,i.kt)("hr",null),(0,i.kt)("h2",{id:"making-sms-results-for-voice-calls-optional"},"Making SMS results for voice calls optional"),(0,i.kt)("p",null,"The default of the system is to send an SMS after each voice meeting result.  As an option to you audience you can add the following parameter to your ",(0,i.kt)("inlineCode",{parentName:"p"},"config.php")," file."),(0,i.kt)("pre",null,(0,i.kt)("code",{parentName:"pre",className:"language-php"},"static $sms_ask = true;\n")),(0,i.kt)("p",null,"By setting this, a prompt will be played at the end of the results, asking if they would like the results texted to them.  If they do not respond the call will automatically hang up in 10 seconds."),(0,i.kt)("h2",{id:"infinite-searches"},"Infinite Searches"),(0,i.kt)("p",null,"You can provide an option to allow someone to search again.  Just set:"),(0,i.kt)("pre",null,(0,i.kt)("code",{parentName:"pre",className:"language-php"},"static $infinite_searching = true;\n")))}f.isMDXComponent=!0}}]);