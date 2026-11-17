import {roles} from '../models/store.js';
function node(tag,text,className) {
  const e=document.createElement(tag);
  if(text)e.textContent=text;
  if(className)e.className=className;
  return e;
}
function layoutItems(model) {
  const cards=model.items.filter(item=>item.type==='box'&&item.w===168&&item.h===162);
  if(!cards.length)return model.items;
  const rows=[...new Set(cards.map(card=>card.y))].sort((a,b)=>a-b);
  return model.items.map(item=>{
    const row=rows.filter(y=>item.y>=y).length-1;
    if(row<0)return item;
    const offset=Math.min(row,rows.length-1)*32;
    return {...item,y:item.y+offset,h:cards.includes(item)?194:item.h};
  });
}
export function home(root,navigate) {
  root._observer?.disconnect(); root.replaceChildren();
  const intro=node('section',null,'intro');
  intro.append(node('small','KHÔNG GIAN LÀM VIỆC'),node('h1','Mỗi vai trò. Một nhịp phối hợp.'),node('p','Chọn thiết bị và vị trí làm việc để bắt đầu ca của bạn.'));
  const grid=node('div',null,'devices');
  const descriptions={s:'Sơ đồ bàn, gọi món và giao món.',c:'Đơn hàng, thanh toán và tổng kết ca.',b:'Hàng chờ, đang pha và hoàn tất món.',m:'Quản lý đơn, xử lý món và voucher.'};
  for(const [device,title,keys] of [['mobile','Điện thoại',['s','m']],['desktop','Desktop',['c','b','m']]]) {
    const panel=node('section',null,'device'); panel.append(node('small',device.toUpperCase()),node('h2',title));
    for(const key of keys) {
      const button=node('button',null,'role'); button.append(node('strong',roles[key]+' ↗'),node('span',descriptions[key]));
      button.onclick=()=>navigate(device,key+'_login'); panel.append(button);
    }
    grid.append(panel);
  }
  root.append(intro,grid);
}
export function screen(root,model,device,screens,navigate) {
  root._observer?.disconnect();root.replaceChildren();
  const toolbar=node('nav'); const back=node('button','← Quay lại');back.onclick=()=>history.back();
  const homeLink=node('a','Đổi vai trò');homeLink.href='#/';
  const select=node('select');select.setAttribute('aria-label','Chuyển màn hình kiểm tra');
  for(const m of screens){const option=node('option',m.title+' · '+m.id);option.value=m.id;select.append(option);}
  select.value=model.id; select.onchange=()=>navigate(device,select.value);
  toolbar.append(back,homeLink,select);root.append(toolbar);
  const shell=node('section',null,'shell '+device),canvas=node('div',null,'canvas');
  const flow=device==='mobile'&&model.role==='Manager';
  canvas.classList.toggle('flow',flow);
  if(!flow){canvas.style.width=model.w+'px';canvas.style.height=model.h+'px';}
  for(const item of layoutItems(model)){
    if(flow&&item.type==='box')continue;
    const e=node(item.type==='button'?'button':'div',item.text,item.type);
    if(!flow){Object.assign(e.style,{left:item.x+'px',top:item.y+'px',width:item.w+'px'});if(item.h)e.style.height=item.h+'px';}
    if(item.type==='text'){e.style.fontSize=(flow?Math.min(24,item.size):item.size)+'px';e.style.fontWeight=item.bold?'700':'400';}
    if(item.type==='box'){
      e.style.background=item.fill==='#ffffff'?'#fff':'#f5f3ed';
      if(item.x===0&&item.y===0&&item.w===model.w)e.style.borderRadius='0';
    }
    if(item.type==='button'){
      e.disabled=!!item.disabled||!item.to;
      e.classList.toggle('primary',!!item.primary||/Đăng nhập|Xác nhận|Thanh toán|Bắt đầu pha|^Xong$/.test(item.text));
      e.classList.toggle('danger',/Hủy đơn|duyệt hủy/.test(item.text));
      e.onclick=()=>navigate(device,item.to);
    }
    canvas.append(e);
  }
  shell.append(canvas);root.append(shell,node('p','Bản thử dữ liệu mẫu. Các ô nhập và nút chuyển sang trạng thái mẫu theo POS v2.','note'));
  if(!flow){const resize=()=>{const ratio=shell.clientWidth/model.w;canvas.style.transform='scale('+ratio+')';shell.style.height=model.h*ratio+'px';};root._observer=new ResizeObserver(resize);root._observer.observe(shell);resize();}
}
