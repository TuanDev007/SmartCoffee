import {api} from './api.js';
export const $ = id => document.getElementById(id);
export const money = n => n.toLocaleString('vi-VN')+' đ';
export function el(tag,text){const e=document.createElement(tag);e.textContent=text;return e}
export function button(text,action){const e=el('button',text);e.type='button';e.onclick=action;return e}
export function card(title){const e=document.createElement('article');e.className='card';e.append(el('strong',title));return e}
export function field(title,input){const label=el('label',title);label.append(input);return label}
export const draft=()=>JSON.parse(sessionStorage.getItem('waiter.draft')||'null');
export const save=d=>sessionStorage.setItem('waiter.draft',JSON.stringify(d));
export const message=text=>{$('status').textContent=text};
export async function start(render){
  try{
    const {user}=await api('/auth/me');
    if(user.role!=='waiter')throw Error('Chức năng dành cho phục vụ.');
    await render();$('body').hidden=false;
  }catch(e){if(e.code==='AUTH_REQUIRED')location.replace('index.html');else message(e.message)}
}
