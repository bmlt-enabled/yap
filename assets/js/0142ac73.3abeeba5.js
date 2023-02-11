"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[6178],{3905:(e,t,i)=>{i.d(t,{Zo:()=>m,kt:()=>d});var o=i(7294);function n(e,t,i){return t in e?Object.defineProperty(e,t,{value:i,enumerable:!0,configurable:!0,writable:!0}):e[t]=i,e}function l(e,t){var i=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),i.push.apply(i,o)}return i}function c(e){for(var t=1;t<arguments.length;t++){var i=null!=arguments[t]?arguments[t]:{};t%2?l(Object(i),!0).forEach((function(t){n(e,t,i[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(i)):l(Object(i)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(i,t))}))}return e}function r(e,t){if(null==e)return{};var i,o,n=function(e,t){if(null==e)return{};var i,o,n={},l=Object.keys(e);for(o=0;o<l.length;o++)i=l[o],t.indexOf(i)>=0||(n[i]=e[i]);return n}(e,t);if(Object.getOwnPropertySymbols){var l=Object.getOwnPropertySymbols(e);for(o=0;o<l.length;o++)i=l[o],t.indexOf(i)>=0||Object.prototype.propertyIsEnumerable.call(e,i)&&(n[i]=e[i])}return n}var a=o.createContext({}),s=function(e){var t=o.useContext(a),i=t;return e&&(i="function"==typeof e?e(t):c(c({},t),e)),i},m=function(e){var t=s(e.components);return o.createElement(a.Provider,{value:t},e.children)},u="mdxType",p={inlineCode:"code",wrapper:function(e){var t=e.children;return o.createElement(o.Fragment,{},t)}},h=o.forwardRef((function(e,t){var i=e.components,n=e.mdxType,l=e.originalType,a=e.parentName,m=r(e,["components","mdxType","originalType","parentName"]),u=s(i),h=n,d=u["".concat(a,".").concat(h)]||u[h]||p[h]||l;return i?o.createElement(d,c(c({ref:t},m),{},{components:i})):o.createElement(d,c({ref:t},m))}));function d(e,t){var i=arguments,n=t&&t.mdxType;if("string"==typeof e||n){var l=i.length,c=new Array(l);c[0]=h;var r={};for(var a in t)hasOwnProperty.call(t,a)&&(r[a]=t[a]);r.originalType=e,r[u]="string"==typeof e?e:n,c[1]=r;for(var s=2;s<l;s++)c[s]=i[s];return o.createElement.apply(null,c)}return o.createElement.apply(null,i)}h.displayName="MDXCreateElement"},7483:(e,t,i)=>{i.r(t),i.d(t,{assets:()=>a,contentTitle:()=>c,default:()=>p,frontMatter:()=>l,metadata:()=>r,toc:()=>s});var o=i(7462),n=(i(7294),i(3905));const l={},c="Music On Hold",r={unversionedId:"helpline/music-on-hold",id:"helpline/music-on-hold",title:"Music On Hold",description:"---",source:"@site/docs/helpline/music-on-hold.md",sourceDirName:"helpline",slug:"/helpline/music-on-hold",permalink:"/helpline/music-on-hold",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/master/docs/docs/helpline/music-on-hold.md",tags:[],version:"current",frontMatter:{},sidebar:"tutorialSidebar",previous:{title:"Using Hidden Service Bodies For Helpline Lookups",permalink:"/helpline/hidden-service-bodies"},next:{title:"Skipping Helpline Call Routing",permalink:"/helpline/skipping-helpline-call-routing"}},a={},s=[],m={toc:s},u="wrapper";function p(e){let{components:t,...i}=e;return(0,n.kt)(u,(0,o.Z)({},m,i,{components:t,mdxType:"MDXLayout"}),(0,n.kt)("h1",{id:"music-on-hold"},"Music On Hold"),(0,n.kt)("hr",null),(0,n.kt)("p",null,"Music on hold will play when doing volunteer routing which is configurable from within the service body call handling.  You can specify one or more URLs to an MP3 file or Shoutcast stream.  Separate them by commas."),(0,n.kt)("p",null,"There are also some free alternatives.  They are licensed by Creative Commons.  They are playlists themselves so they may not be combined with any other URLs."),(0,n.kt)("ul",null,(0,n.kt)("li",{parentName:"ul"},"Ambient: ",(0,n.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient")," - ",(0,n.kt)("a",{parentName:"li",href:"http://com.twilio.music.ambient.s3.amazonaws.com/license.txt"},"[license]")),(0,n.kt)("li",{parentName:"ul"},"Classical (default): ",(0,n.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical")," - ",(0,n.kt)("a",{parentName:"li",href:"http://com.twilio.music.classical.s3.amazonaws.com/license.txt"},"[license]")),(0,n.kt)("li",{parentName:"ul"},"Electronica: ",(0,n.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.electronica"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.electronica")," - ",(0,n.kt)("a",{parentName:"li",href:"http://com.twilio.music.electronica.s3.amazonaws.com/license.txt"},"[license]")),(0,n.kt)("li",{parentName:"ul"},"Guitars: ",(0,n.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.guitars"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.guitars")," - ",(0,n.kt)("a",{parentName:"li",href:"http://com.twilio.music.guitars.s3.amazonaws.com/license.txt"},"[license]")),(0,n.kt)("li",{parentName:"ul"},"New Age: ",(0,n.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.newage"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.newage")," - ",(0,n.kt)("a",{parentName:"li",href:"http://com.twilio.music.newage.s3.amazonaws.com/license.txt"},"[license]")),(0,n.kt)("li",{parentName:"ul"},"Rock: ",(0,n.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.rock"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.rock")," - ",(0,n.kt)("a",{parentName:"li",href:"http://com.twilio.music.rock.s3.amazonaws.com/license.txt"},"[license]")),(0,n.kt)("li",{parentName:"ul"},"Soft Rock: ",(0,n.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.soft-rock"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.soft-rock")," - ",(0,n.kt)("a",{parentName:"li",href:"http://com.twilio.music.soft-rock.s3.amazonaws.com/license.txt"},"[license]"))),(0,n.kt)("p",null,"Music on Hold loops indefinitely."))}p.isMDXComponent=!0}}]);