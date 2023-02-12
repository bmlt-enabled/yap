"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[3931],{3905:(e,t,n)=>{n.d(t,{Zo:()=>s,kt:()=>m});var a=n(7294);function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function l(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);t&&(a=a.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,a)}return n}function r(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?l(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):l(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function i(e,t){if(null==e)return{};var n,a,o=function(e,t){if(null==e)return{};var n,a,o={},l=Object.keys(e);for(a=0;a<l.length;a++)n=l[a],t.indexOf(n)>=0||(o[n]=e[n]);return o}(e,t);if(Object.getOwnPropertySymbols){var l=Object.getOwnPropertySymbols(e);for(a=0;a<l.length;a++)n=l[a],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(o[n]=e[n])}return o}var p=a.createContext({}),c=function(e){var t=a.useContext(p),n=t;return e&&(n="function"==typeof e?e(t):r(r({},t),e)),n},s=function(e){var t=c(e.components);return a.createElement(p.Provider,{value:t},e.children)},u="mdxType",g={inlineCode:"code",wrapper:function(e){var t=e.children;return a.createElement(a.Fragment,{},t)}},d=a.forwardRef((function(e,t){var n=e.components,o=e.mdxType,l=e.originalType,p=e.parentName,s=i(e,["components","mdxType","originalType","parentName"]),u=c(n),d=o,m=u["".concat(p,".").concat(d)]||u[d]||g[d]||l;return n?a.createElement(m,r(r({ref:t},s),{},{components:n})):a.createElement(m,r({ref:t},s))}));function m(e,t){var n=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var l=n.length,r=new Array(l);r[0]=d;var i={};for(var p in t)hasOwnProperty.call(t,p)&&(i[p]=t[p]);i.originalType=e,i[u]="string"==typeof e?e:o,r[1]=i;for(var c=2;c<l;c++)r[c]=n[c];return a.createElement.apply(null,r)}return a.createElement.apply(null,n)}d.displayName="MDXCreateElement"},2882:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>p,contentTitle:()=>r,default:()=>g,frontMatter:()=>l,metadata:()=>i,toc:()=>c});var a=n(7462),o=(n(7294),n(3905));const l={},r="Language Options",i={unversionedId:"general/language-options",id:"general/language-options",title:"Language Options",description:"---",source:"@site/docs/general/language-options.md",sourceDirName:"general",slug:"/general/language-options",permalink:"/general/language-options",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/general/language-options.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Initial Pause",permalink:"/general/initial-pause"},next:{title:"Location Lookup Bias",permalink:"/general/location-lookup-bias"}},p={},c=[{value:"Language Call Routing",id:"language-call-routing",level:3},{value:"Mixing languages and voices",id:"mixing-languages-and-voices",level:3}],s={toc:c},u="wrapper";function g(e){let{components:t,...n}=e;return(0,o.kt)(u,(0,a.Z)({},s,n,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"language-options"},"Language Options"),(0,o.kt)("hr",null),(0,o.kt)("p",null,"There is a concept of language resource files.  You will notice them in the ",(0,o.kt)("inlineCode",{parentName:"p"},"lang/")," folder.  Please open a ticket if you would like to contribute to translating to another language."),(0,o.kt)("p",null,"You can also override any of the language prompts in the ",(0,o.kt)("inlineCode",{parentName:"p"},"config.php")," file. "),(0,o.kt)("p",null,'For example, say you wanted to still use English, but change the "city or county" prompt to say, "city or suburb".  You would do the following in config.php:'),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $override_city_or_county = "city or suburb";\n')),(0,o.kt)("p",null,"You can see the full listing in the ",(0,o.kt)("inlineCode",{parentName:"p"},"lang/en-US.php")," which always has the full latest listing of the voice prompts."),(0,o.kt)("p",null,"You can also change the spoken language accent.  There is a wide variety.  See the Twilio documentation for more details: ",(0,o.kt)("a",{parentName:"p",href:"https://www.twilio.com/docs/voice/twiml/say#attributes-language"},"https://www.twilio.com/docs/voice/twiml/say#attributes-language"),".  There are also some additional voices available here as well ",(0,o.kt)("a",{parentName:"p",href:"https://www.twilio.com/docs/voice/twiml/say/text-speech#voices"},"https://www.twilio.com/docs/voice/twiml/say/text-speech#voices"),"."),(0,o.kt)("p",null,"An example would be using an Australian English Accent.  Set your config.php to:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $voice = "alice";\nstatic $language = "en-AU";\n')),(0,o.kt)("h3",{id:"language-call-routing"},"Language Call Routing"),(0,o.kt)("p",null,"You can also create a language selection menu upon dialing in.  It will only be available for those that there are resource files for in ",(0,o.kt)("inlineCode",{parentName:"p"},"lang/")," folder.  If you have some translations, please send them, so they can be merged in."),(0,o.kt)("p",null,"Add a new setting called, specifying the language codes for each language you want included.  The order will indicate the order in which it will be played back:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'static $language_selections = "en-US,pig-latin";\n')),(0,o.kt)("p",null,"This example will make option 1, English and option 2, pig latin."),(0,o.kt)("h3",{id:"mixing-languages-and-voices"},"Mixing languages and voices"),(0,o.kt)("p",null,"Voices can be configured for every language option.  For example for Spanish:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},'es_US_voice = "Polly.Penelope";\n')),(0,o.kt)("p",null,(0,o.kt)("strong",{parentName:"p"},"New Yap 3.0")," If you want to route calls to volunteers by language, see the section on Language in ",(0,o.kt)("a",{parentName:"p",href:"../../helpline/specialized-routing/"},"Specialized Routing"),"."))}g.isMDXComponent=!0}}]);