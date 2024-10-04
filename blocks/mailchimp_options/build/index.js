(()=>{"use strict";const e=window.React,C=window.wp.components,t=window.wp.data,i=window.wp.editPost,n=window.wp.coreData,{__}=wp.i18n,{registerPlugin:a}=wp.plugins;a("mailchimp-options",{render:function(){const a=(0,t.useSelect)((e=>e("core/editor").getCurrentPostType()),[]);if(null==a)return"";const[l,m]=(0,n.useEntityProp)("postType",a,"meta"),o=l.mailchimp_segment_id,s=l.mailchimp_email,r=l.mailchimp_extra_message,p=(e,C)=>{let t={...l};t[C]=e,m(t)};return(0,e.createElement)(i.PluginDocumentSettingPanel,{name:"mailchimp-options",title:__("Mailchimp Options","sim"),className:"mailchimp-options"},(0,e.createElement)(C.SelectControl,{label:__("Mailchimp group"),value:o,options:[{label:"Select one ...",value:""},...mailchimp],onChange:e=>p(e,"mailchimp_segment_id"),__nextHasNoMarginBottom:!0}),(0,e.createElement)(C.__experimentalInputControl,{isPressEnterToChange:!0,label:__("From email address"),value:s,onChange:e=>p(e,"mailchimp_email")}),(0,e.createElement)(C.__experimentalInputControl,{isPressEnterToChange:!0,label:__("Prepend message"),value:r,onChange:e=>p(e,"mailchimp_extra_message")}))},icon:(0,e.createElement)("svg",{width:"20px",height:"20px",viewBox:"0 0 20 21"},(0,e.createElement)("g",{id:"surface1"},(0,e.createElement)("path",{d:"M 19.210938 13.296875 C 19.203125 13.261719 19.140625 13.046875 19.058594 12.78125 C 18.976562 12.519531 18.890625 12.335938 18.890625 12.335938 C 19.222656 11.84375 19.230469 11.402344 19.183594 11.15625 C 19.136719 10.847656 19.007812 10.582031 18.746094 10.3125 C 18.484375 10.039062 17.949219 9.761719 17.195312 9.550781 C 17.109375 9.527344 16.828125 9.449219 16.800781 9.441406 C 16.800781 9.425781 16.78125 8.523438 16.761719 8.132812 C 16.75 7.851562 16.726562 7.414062 16.589844 6.980469 C 16.425781 6.398438 16.140625 5.886719 15.785156 5.558594 C 16.765625 4.550781 17.378906 3.4375 17.378906 2.484375 C 17.375 0.652344 15.101562 0.0976562 12.300781 1.246094 C 12.296875 1.25 11.710938 1.496094 11.707031 1.496094 C 11.703125 1.496094 10.632812 0.453125 10.617188 0.441406 C 7.421875 -2.316406 -2.566406 8.675781 0.628906 11.34375 L 1.328125 11.929688 C 1.144531 12.394531 1.074219 12.925781 1.132812 13.496094 C 1.207031 14.230469 1.589844 14.9375 2.210938 15.480469 C 2.800781 15.996094 3.574219 16.324219 4.324219 16.324219 C 5.566406 19.15625 8.40625 20.898438 11.738281 20.996094 C 15.308594 21.101562 18.304688 19.441406 19.5625 16.460938 C 19.644531 16.25 19.992188 15.308594 19.992188 14.476562 C 19.992188 13.640625 19.515625 13.296875 19.210938 13.296875 Z M 4.597656 15.527344 C 4.492188 15.546875 4.378906 15.554688 4.269531 15.550781 C 3.191406 15.523438 2.023438 14.558594 1.910156 13.417969 C 1.78125 12.160156 2.429688 11.191406 3.582031 10.960938 C 3.71875 10.933594 3.886719 10.914062 4.066406 10.925781 C 4.710938 10.960938 5.660156 11.449219 5.878906 12.84375 C 6.074219 14.074219 5.765625 15.328125 4.597656 15.527344 Z M 3.394531 10.207031 C 2.679688 10.34375 2.046875 10.746094 1.660156 11.300781 C 1.429688 11.109375 1 10.742188 0.921875 10.597656 C 0.304688 9.4375 1.59375 7.183594 2.496094 5.910156 C 4.722656 2.765625 8.210938 0.382812 9.828125 0.8125 C 10.089844 0.886719 10.960938 1.886719 10.960938 1.886719 C 10.960938 1.886719 9.34375 2.773438 7.847656 4.007812 C 5.832031 5.546875 4.308594 7.78125 3.394531 10.207031 Z M 14.71875 15.054688 C 14.742188 15.046875 14.757812 15.019531 14.753906 14.992188 C 14.75 14.960938 14.722656 14.9375 14.691406 14.941406 C 14.691406 14.941406 13 15.1875 11.402344 14.609375 C 11.578125 14.050781 12.039062 14.253906 12.738281 14.308594 C 14 14.382812 15.125 14.199219 15.960938 13.964844 C 16.683594 13.757812 17.632812 13.351562 18.371094 12.777344 C 18.621094 13.316406 18.707031 13.914062 18.707031 13.914062 C 18.707031 13.914062 18.902344 13.878906 19.0625 13.976562 C 19.214844 14.070312 19.324219 14.261719 19.25 14.761719 C 19.09375 15.691406 18.695312 16.445312 18.023438 17.140625 C 17.613281 17.574219 17.117188 17.953125 16.550781 18.230469 C 16.25 18.386719 15.929688 18.523438 15.589844 18.632812 C 13.058594 19.449219 10.46875 18.550781 9.636719 16.617188 C 9.566406 16.472656 9.511719 16.320312 9.46875 16.164062 C 9.113281 14.890625 9.414062 13.363281 10.359375 12.402344 C 10.359375 12.402344 10.359375 12.402344 10.359375 12.398438 C 10.417969 12.339844 10.476562 12.265625 10.476562 12.175781 C 10.476562 12.101562 10.425781 12.019531 10.386719 11.964844 C 10.054688 11.488281 8.910156 10.679688 9.140625 9.117188 C 9.304688 7.992188 10.296875 7.203125 11.222656 7.25 C 11.300781 7.253906 11.378906 7.257812 11.457031 7.261719 C 11.859375 7.285156 12.207031 7.335938 12.539062 7.351562 C 13.09375 7.375 13.589844 7.292969 14.179688 6.808594 C 14.375 6.644531 14.535156 6.503906 14.804688 6.457031 C 14.832031 6.453125 14.902344 6.429688 15.042969 6.433594 C 15.1875 6.441406 15.324219 6.480469 15.449219 6.5625 C 15.921875 6.875 15.988281 7.625 16.011719 8.175781 C 16.023438 8.492188 16.0625 9.25 16.078125 9.46875 C 16.105469 9.96875 16.238281 10.039062 16.507812 10.128906 C 16.65625 10.175781 16.796875 10.214844 17.003906 10.269531 C 17.628906 10.445312 18 10.621094 18.234375 10.847656 C 18.371094 10.988281 18.4375 11.140625 18.457031 11.28125 C 18.53125 11.8125 18.039062 12.472656 16.742188 13.066406 C 15.320312 13.71875 13.597656 13.886719 12.40625 13.753906 C 12.316406 13.742188 11.992188 13.707031 11.988281 13.707031 C 11.035156 13.582031 10.492188 14.800781 11.066406 15.632812 C 11.433594 16.171875 12.4375 16.523438 13.441406 16.523438 C 15.742188 16.523438 17.511719 15.550781 18.167969 14.710938 C 18.191406 14.683594 18.191406 14.679688 18.222656 14.632812 C 18.253906 14.585938 18.230469 14.558594 18.1875 14.585938 C 17.648438 14.949219 15.261719 16.394531 12.707031 15.960938 C 12.707031 15.960938 12.398438 15.910156 12.113281 15.800781 C 11.890625 15.714844 11.417969 15.5 11.359375 15.023438 C 13.417969 15.652344 14.71875 15.054688 14.71875 15.054688 Z M 11.453125 14.675781 Z M 7.507812 5.898438 C 8.300781 4.992188 9.273438 4.203125 10.148438 3.761719 C 10.179688 3.746094 10.210938 3.777344 10.195312 3.808594 C 10.125 3.933594 9.992188 4.199219 9.949219 4.402344 C 9.941406 4.433594 9.976562 4.457031 10.003906 4.4375 C 10.546875 4.070312 11.492188 3.679688 12.320312 3.628906 C 12.355469 3.625 12.375 3.671875 12.34375 3.691406 C 12.21875 3.789062 12.082031 3.917969 11.980469 4.054688 C 11.960938 4.078125 11.980469 4.109375 12.007812 4.109375 C 12.589844 4.113281 13.410156 4.316406 13.945312 4.609375 C 13.980469 4.632812 13.957031 4.699219 13.914062 4.691406 C 13.105469 4.507812 11.78125 4.367188 10.40625 4.703125 C 9.175781 4.996094 8.238281 5.457031 7.554688 5.949219 C 7.523438 5.972656 7.480469 5.929688 7.507812 5.898438 Z M 7.507812 5.898438 "})))})})();