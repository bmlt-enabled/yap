"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[563],{7487:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>l,contentTitle:()=>s,default:()=>d,frontMatter:()=>r,metadata:()=>i,toc:()=>c});var a=n(5893),o=n(1151);const r={slug:"merging-yap-post",title:"Merging Yap Servers",authors:["fortysevenbot"],tags:["yap"]},s="Merging a Regional Yap Server into a Zonal Yap Server",i={permalink:"/blog/merging-yap-post",editUrl:"https://github.com/bmlt-enabled/yap/edit/main/docs/blog/2021-08-01-merging-yap-post/index.md",source:"@site/blog/2021-08-01-merging-yap-post/index.md",title:"Merging Yap Servers",description:"Since Yap 3.0.0 (released in March 2019), it\u2019s been possible to use a single Yap server with multiple Twilio accounts.  What this means is that a service body can handle the overhead of server management while another service body retains the management of phone numbers and billing.",date:"2021-08-01T00:00:00.000Z",tags:[{inline:!0,label:"yap",permalink:"/blog/tags/yap"}],readingTime:2.01,hasTruncateMarker:!0,authors:[{name:"FortySeven Bot",title:"BMLT Bot",url:"https://github.com/fortysevenbot",imageURL:"https://github.com/fortysevenbot.png",key:"fortysevenbot"}],frontMatter:{slug:"merging-yap-post",title:"Merging Yap Servers",authors:["fortysevenbot"],tags:["yap"]},unlisted:!1,nextItem:{title:"Excluding a custom format from a Yap query",permalink:"/blog/excluding-format-post"}},l={authorsImageUrls:[void 0]},c=[];function h(e){const t={a:"a",code:"code",img:"img",li:"li",ol:"ol",p:"p",pre:"pre",...(0,o.a)(),...e.components};return(0,a.jsxs)(a.Fragment,{children:[(0,a.jsx)(t.p,{children:"Since Yap 3.0.0 (released in March 2019), it\u2019s been possible to use a single Yap server with multiple Twilio accounts.  What this means is that a service body can handle the overhead of server management while another service body retains the management of phone numbers and billing."}),"\n",(0,a.jsxs)(t.p,{children:["Yap 3 also introduced the concept of configuration precedence ",(0,a.jsx)(t.a,{href:"https://github.com/bmlt-enabled/yap/wiki/Configuration-Precedence",children:"https://github.com/bmlt-enabled/yap/wiki/Configuration-Precedence"}),".  This creates the ability to manifest all kinds of powerful capability without requiring access to the config.php on the server (critical for this situation where server management is handled by someone else).  It also has the ability to set a value at regional level while the hierarchy of the BMLT automatically cascades down to the member areas."]}),"\n",(0,a.jsxs)(t.p,{children:["Recently I had to migrate my regional yap server to the zonal server.  Below is the process I followed, feel free to send an email to ",(0,a.jsx)(t.a,{href:"mailto:help@bmlt.app",children:"help@bmlt.app"})," if you\u2019d like more details."]}),"\n",(0,a.jsx)(t.p,{children:"Consider whether you may want to take a backup and overwrite your existing Yap database, or make a copy with a new install and config to do side by side testing.  You may also want to consider setting in the database config ahead of time or afterward.  You may also want to transfer any other settings in your top level config.php to the Config settings in the admin portal.  Keep in mind that service bodies will use the hierarchy, so if you set this as a regional level all the service bodies connected will inherit them."}),"\n",(0,a.jsxs)(t.ol,{children:["\n",(0,a.jsx)(t.li,{children:"Delete any configuration from the target yap server, use the server body IDs that would be the IDs that would be the query below:"}),"\n"]}),"\n",(0,a.jsx)(t.pre,{children:(0,a.jsx)(t.code,{className:"language-sql",children:"DELETE FROM config where service_body_id in (x [,x]);\n"})}),"\n",(0,a.jsxs)(t.ol,{start:"2",children:["\n",(0,a.jsx)(t.li,{children:"Begin an export from your source yap server, select only data and exclude the flags table.  See the screenshot below.  (Use a self-contained file)"}),"\n"]}),"\n",(0,a.jsx)(t.p,{children:(0,a.jsx)(t.img,{alt:"MySQL Export",src:n(7033).Z+"",width:"618",height:"656"})}),"\n",(0,a.jsxs)(t.ol,{start:"3",children:["\n",(0,a.jsx)(t.li,{children:"After the file has been exported run the below on your system.  In the below example, \u201cexport.sql\u201d is the file exported from Step 2 above."}),"\n"]}),"\n",(0,a.jsx)(t.pre,{children:(0,a.jsx)(t.code,{className:"language-bash",children:"cat export.sql | sed -e \u201cs/([0-9]*,/(NULL,/g\u201d > export-mod.sql\n"})}),"\n",(0,a.jsxs)(t.ol,{start:"4",children:["\n",(0,a.jsxs)(t.li,{children:["\n",(0,a.jsx)(t.p,{children:"Import export-mod.sql into the target yap server.  \u201cexport-mod.sql\u201d is the output from the command run locally in Step 3 above."}),"\n"]}),"\n",(0,a.jsxs)(t.li,{children:["\n",(0,a.jsx)(t.p,{children:"Your phone numbers must have explicit service body overrides in order to pull configuration values from the database (you can use either override_service_body_id or override_service_body_config_id).  One changes your service body for call routing and the other selects configuration, respectively."}),"\n"]}),"\n"]})]})}function d(e={}){const{wrapper:t}={...(0,o.a)(),...e.components};return t?(0,a.jsx)(t,{...e,children:(0,a.jsx)(h,{...e})}):h(e)}},7033:(e,t,n)=>{n.d(t,{Z:()=>a});const a=n.p+"assets/images/mysql-export-e5f71a6ce1c368a20c842f3ab6a8bca5.png"},1151:(e,t,n)=>{n.d(t,{Z:()=>i,a:()=>s});var a=n(7294);const o={},r=a.createContext(o);function s(e){const t=a.useContext(r);return a.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function i(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(o):e.components||o:s(e.components),a.createElement(r.Provider,{value:t},e.children)}}}]);