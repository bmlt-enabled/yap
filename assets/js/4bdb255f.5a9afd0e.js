"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[7889],{5709:(e,n,t)=>{t.r(n),t.d(n,{assets:()=>a,contentTitle:()=>c,default:()=>p,frontMatter:()=>r,metadata:()=>o,toc:()=>l});const o=JSON.parse('{"id":"general/voice-recognition-options","title":"Voice Recognition Options","description":"---","source":"@site/docs/general/voice-recognition-options.md","sourceDirName":"general","slug":"/general/voice-recognition-options","permalink":"/general/voice-recognition-options","draft":false,"unlisted":false,"editUrl":"https://github.com/bmlt-enabled/yap/edit/main/docs/docs/general/voice-recognition-options.md","tags":[],"version":"current","frontMatter":{},"sidebar":"tutorialSidebar","previous":{"title":"Voice Greeting","permalink":"/general/voice-greeting"},"next":{"title":"Custom Query","permalink":"/meeting-search/custom-query"}}');var i=t(4848),s=t(8453);const r={},c="Voice Recognition Options",a={},l=[];function h(e){const n={a:"a",code:"code",h1:"h1",header:"header",hr:"hr",p:"p",pre:"pre",...(0,s.R)(),...e.components};return(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(n.header,{children:(0,i.jsx)(n.h1,{id:"voice-recognition-options",children:"Voice Recognition Options"})}),"\n",(0,i.jsx)(n.hr,{}),"\n",(0,i.jsxs)(n.p,{children:["It's possible to set the expected spoken language, for recognition by setting the following variable in config.php to the culture variant.  The default is ",(0,i.jsx)(n.code,{children:"en-US"}),", which is US English."]}),"\n",(0,i.jsxs)(n.p,{children:["Use the this chart to find the code of your preference ",(0,i.jsx)(n.a,{href:"https://www.twilio.com/docs/api/twiml/gather#languagetags",children:"https://www.twilio.com/docs/api/twiml/gather#languagetags"}),"."]}),"\n",(0,i.jsx)(n.pre,{children:(0,i.jsx)(n.code,{className:"language-php",children:'static $gather_language = "en-US";\n'})}),"\n",(0,i.jsx)(n.p,{children:"You can also set some expected words or hints, to help the voice recognition engine along.  Use the setting by separating words with commas.  You can use phrases as well."}),"\n",(0,i.jsx)(n.p,{children:"Each hint may not be more than 100 characters (including spaces).  You can use up to 500 hints."}),"\n",(0,i.jsx)(n.pre,{children:(0,i.jsx)(n.code,{className:"language-php",children:'static $gather_hints = "";\n'})}),"\n",(0,i.jsx)(n.p,{children:"Voice recognition for input gathering is turned on by default, to turn it off you can do the following."}),"\n",(0,i.jsx)(n.pre,{children:(0,i.jsx)(n.code,{className:"language-php",children:"static $speech_gathering = false;\n"})})]})}function p(e={}){const{wrapper:n}={...(0,s.R)(),...e.components};return n?(0,i.jsx)(n,{...e,children:(0,i.jsx)(h,{...e})}):h(e)}},8453:(e,n,t)=>{t.d(n,{R:()=>r,x:()=>c});var o=t(6540);const i={},s=o.createContext(i);function r(e){const n=o.useContext(s);return o.useMemo((function(){return"function"==typeof e?e(n):{...n,...e}}),[n,e])}function c(e){let n;return n=e.disableParentContext?"function"==typeof e.components?e.components(i):e.components||i:r(e.components),o.createElement(s.Provider,{value:n},e.children)}}}]);