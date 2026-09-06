module.exports=[61095,(r,e,t)=>{e.exports=r.x("node:net",()=>require("node:net"))},85560,(r,e,t)=>{e.exports=r.x("node:tls",()=>require("node:tls"))},76763,r=>{"use strict";var e=r.i(85560),t=r.i(61095);function n(r){let e=Buffer.from(r,"utf8"),t="",n=0;for(let r of e){if(r>=33&&r<=60||r>=62&&r<=126||9===r||32===r)t+=String.fromCharCode(r),n++;else{let e=r.toString(16).toUpperCase().padStart(2,"0");t+=`=${e}`,n+=3}n>=70&&(t+="=\r\n",n=0)}return t}function o(r,e){if(!e||/[\r\n\u0000-\u001f\u007f]/.test(e)||/[<>\s]/.test(e))throw Error(`${r} invalid`)}async function i(r){return o("sender",r.sender),o("recipient",r.to),new Promise((o,i)=>{let s,f=465===r.port,u=r=>{s=r,a(r)},a=t=>{let u=r=>t.write(r+"\r\n"),a=[],d=0,l=(t,l)=>{let c=parseInt(t.slice(0,3),10);if(" "===t[3]||3===t.length){if(0===d&&220===c){if(!f&&465!==r.port){l.write("STARTTLS\r\n"),d=90;return}u("EHLO postyar"),d=1;return}if(90===d&&220===c){let t=e.default.connect({socket:l,servername:r.host,rejectUnauthorized:!0},()=>{f=!0,d=0,a.length=0,t.write("EHLO postyar\r\n")});t.setEncoding("utf8");let s=[],u=1;t.on("data",e=>{for(let f of(s.push(e),s.join("").split("\r\n"))){if(!f)continue;let e=parseInt(f.slice(0,3),10);if(" "===f[3]||3===f.length){if(1===u&&250===e){t.write("AUTH LOGIN\r\n"),u=2;continue}if(2===u&&334===e){t.write(Buffer.from(r.user).toString("base64")),u=3;continue}if(3===u&&334===e){t.write(Buffer.from(r.password).toString("base64")),u=4;continue}if(4===u&&235===e){t.write(`MAIL FROM:<${r.sender}>`),u=5;continue}if(5===u&&250===e){t.write(`RCPT TO:<${r.to}>`),u=6;continue}if(6===u&&250===e){t.write("DATA"),u=7;continue}if(7===u&&354===e){let e=`From: =?UTF-8?Q?${n(r.senderName)}?= <${r.sender}>\r
To: <${r.to}>\r
Subject: =?UTF-8?Q?${n(r.subjectFa)}?=\r
MIME-Version: 1.0\r
Content-Type: text/html; charset=UTF-8\r
Content-Transfer-Encoding: quoted-printable\r
\r
${n(r.htmlFa)}\r
.\r
`;t.write(e),u=8;continue}if(8===u&&250===e){t.write("QUIT"),o(),t.end();return}if(e>=400){i(Error(`SMTP error: ${f}`)),t.destroy();return}}}s.length=0}),t.on("error",r=>i(r)),t.setTimeout(15e3,()=>{i(Error("SMTP timeout")),t.destroy()});return}if(90===d&&c>=400){i(Error("SMTP server does not support STARTTLS")),l.destroy();return}if(1===d&&250===c){u("AUTH LOGIN"),d=2;return}if(2===d&&334===c){u(Buffer.from(r.user).toString("base64")),d=3;return}if(3===d&&334===c){u(Buffer.from(r.password).toString("base64")),d=4;return}if(4===d&&235===c){u(`MAIL FROM:<${r.sender}>`),d=5;return}if(5===d&&250===c){u(`RCPT TO:<${r.to}>`),d=6;return}if(6===d&&250===c){u("DATA"),d=7;return}if(7===d&&354===c){u(`From: =?UTF-8?Q?${n(r.senderName)}?= <${r.sender}>\r
To: <${r.to}>\r
Subject: =?UTF-8?Q?${n(r.subjectFa)}?=\r
MIME-Version: 1.0\r
Content-Type: text/html; charset=UTF-8\r
Content-Transfer-Encoding: quoted-printable\r
\r
${n(r.htmlFa)}\r
.\r
`),d=8;return}if(8===d&&250===c){u("QUIT"),o(),s.end();return}if(c>=400){i(Error(`SMTP error: ${t}`)),l.destroy();return}}};t.setEncoding("utf8"),t.on("data",r=>{for(let e of(a.push(r),a.join("").split("\r\n")))e&&l(e,t);a.length=0}),t.on("error",r=>i(r)),t.setTimeout(15e3,()=>{i(Error("SMTP timeout")),t.destroy()})};if(465===r.port)u(e.default.connect({host:r.host,port:r.port,servername:r.host,rejectUnauthorized:!0}));else{let e=new t.Socket;e.connect(r.port,r.host),u(e)}})}r.s(["sendMail",()=>i])}];

//# sourceMappingURL=%5Broot-of-the-server%5D__b89c5a91._.js.map