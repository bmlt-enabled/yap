"use strict";(self.webpackChunkyapdocs=self.webpackChunkyapdocs||[]).push([[6178],{3905:function(e,t,i){i.d(t,{Zo:function(){return m},kt:function(){return h}});var n=i(7294);function o(e,t,i){return t in e?Object.defineProperty(e,t,{value:i,enumerable:!0,configurable:!0,writable:!0}):e[t]=i,e}function r(e,t){var i=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),i.push.apply(i,n)}return i}function c(e){for(var t=1;t<arguments.length;t++){var i=null!=arguments[t]?arguments[t]:{};t%2?r(Object(i),!0).forEach((function(t){o(e,t,i[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(i)):r(Object(i)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(i,t))}))}return e}function l(e,t){if(null==e)return{};var i,n,o=function(e,t){if(null==e)return{};var i,n,o={},r=Object.keys(e);for(n=0;n<r.length;n++)i=r[n],t.indexOf(i)>=0||(o[i]=e[i]);return o}(e,t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);for(n=0;n<r.length;n++)i=r[n],t.indexOf(i)>=0||Object.prototype.propertyIsEnumerable.call(e,i)&&(o[i]=e[i])}return o}var a=n.createContext({}),s=function(e){var t=n.useContext(a),i=t;return e&&(i="function"==typeof e?e(t):c(c({},t),e)),i},m=function(e){var t=s(e.components);return n.createElement(a.Provider,{value:t},e.children)},u={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},p=n.forwardRef((function(e,t){var i=e.components,o=e.mdxType,r=e.originalType,a=e.parentName,m=l(e,["components","mdxType","originalType","parentName"]),p=s(i),h=o,f=p["".concat(a,".").concat(h)]||p[h]||u[h]||r;return i?n.createElement(f,c(c({ref:t},m),{},{components:i})):n.createElement(f,c({ref:t},m))}));function h(e,t){var i=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var r=i.length,c=new Array(r);c[0]=p;var l={};for(var a in t)hasOwnProperty.call(t,a)&&(l[a]=t[a]);l.originalType=e,l.mdxType="string"==typeof e?e:o,c[1]=l;for(var s=2;s<r;s++)c[s]=i[s];return n.createElement.apply(null,c)}return n.createElement.apply(null,i)}p.displayName="MDXCreateElement"},7483:function(e,t,i){i.r(t),i.d(t,{assets:function(){return m},contentTitle:function(){return a},default:function(){return h},frontMatter:function(){return l},metadata:function(){return s},toc:function(){return u}});var n=i(7462),o=i(3366),r=(i(7294),i(3905)),c=["components"],l={title:"Music On Hold",sidebar_position:9},a=void 0,s={unversionedId:"helpline/music-on-hold",id:"helpline/music-on-hold",title:"Music On Hold",description:"---",source:"@site/docs/helpline/music-on-hold.md",sourceDirName:"helpline",slug:"/helpline/music-on-hold",permalink:"/helpline/music-on-hold",draft:!1,editUrl:"https://github.com/bmlt-enabled/yap/edit/docs/docs/helpline/music-on-hold.md",tags:[],version:"current",sidebarPosition:9,frontMatter:{title:"Music On Hold",sidebar_position:9},sidebar:"tutorialSidebar",previous:{title:"Using Hidden Service Bodies For Helpline Lookups",permalink:"/helpline/hidden-service-bodies"},next:{title:"Skipping Helpline Call Routing",permalink:"/helpline/skipping-helpline-call-routing"}},m={},u=[],p={toc:u};function h(e){var t=e.components,i=(0,o.Z)(e,c);return(0,r.kt)("wrapper",(0,n.Z)({},p,i,{components:t,mdxType:"MDXLayout"}),(0,r.kt)("hr",null),(0,r.kt)("p",null,"Music on hold will play when doing volunteer routing which is configurable from within the service body call handling.  You can specify one or more URLs to an MP3 file or Shoutcast stream.  Separate them by commas."),(0,r.kt)("p",null,"There are also some free alternatives.  They are licensed by Creative Commons.  They are playlists themselves so they may not be combined with any other URLs."),(0,r.kt)("ul",null,(0,r.kt)("li",{parentName:"ul"},"Ambient: ",(0,r.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient")," - ",(0,r.kt)("a",{parentName:"li",href:"http://com.twilio.music.ambient.s3.amazonaws.com/license.txt"},"[license]")),(0,r.kt)("li",{parentName:"ul"},"Classical (default): ",(0,r.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical")," - ",(0,r.kt)("a",{parentName:"li",href:"http://com.twilio.music.classical.s3.amazonaws.com/license.txt"},"[license]")),(0,r.kt)("li",{parentName:"ul"},"Electronica: ",(0,r.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.electronica"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.electronica")," - ",(0,r.kt)("a",{parentName:"li",href:"http://com.twilio.music.electronica.s3.amazonaws.com/license.txt"},"[license]")),(0,r.kt)("li",{parentName:"ul"},"Guitars: ",(0,r.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.guitars"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.guitars")," - ",(0,r.kt)("a",{parentName:"li",href:"http://com.twilio.music.guitars.s3.amazonaws.com/license.txt"},"[license]")),(0,r.kt)("li",{parentName:"ul"},"New Age: ",(0,r.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.newage"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.newage")," - ",(0,r.kt)("a",{parentName:"li",href:"http://com.twilio.music.newage.s3.amazonaws.com/license.txt"},"[license]")),(0,r.kt)("li",{parentName:"ul"},"Rock: ",(0,r.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.rock"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.rock")," - ",(0,r.kt)("a",{parentName:"li",href:"http://com.twilio.music.rock.s3.amazonaws.com/license.txt"},"[license]")),(0,r.kt)("li",{parentName:"ul"},"Soft Rock: ",(0,r.kt)("a",{parentName:"li",href:"https://twimlets.com/holdmusic?Bucket=com.twilio.music.soft-rock"},"https://twimlets.com/holdmusic?Bucket=com.twilio.music.soft-rock")," - ",(0,r.kt)("a",{parentName:"li",href:"http://com.twilio.music.soft-rock.s3.amazonaws.com/license.txt"},"[license]"))),(0,r.kt)("p",null,"Music on Hold loops indefinitely."))}h.isMDXComponent=!0}}]);