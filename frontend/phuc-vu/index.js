import {api,mode} from '../shared/api.js';
import {$,message} from '../shared/ui.js';
$('sample').hidden=mode!=='fixture';
$('login').onsubmit=async event=>{
  event.preventDefault();const form=event.target;const submit=form.querySelector('button');submit.disabled=true;
  try{
    const {user}=await api('/auth/login',Object.fromEntries(new FormData(form)));
    if(user.role!=='waiter')throw Error('Tài khoản này không thuộc vai trò phục vụ.');
    location.href='tables.html';
  }catch(e){message(e.message)}finally{submit.disabled=false}
};
try{const {user}=await api('/auth/me');if(user.role==='waiter')location.replace('tables.html');}
catch(e){if(e.code!=='AUTH_REQUIRED')message(e.message)}
$('body').hidden=false;if($('status').textContent==='Đang tải…')message('');
