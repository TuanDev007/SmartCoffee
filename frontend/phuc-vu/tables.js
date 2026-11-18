import {api} from '../shared/api.js';
import {$,button,card,el,start,save,draft,message} from '../shared/ui.js';
start(async()=>{
  const [areas,shift]=await Promise.all([api('/tables'),api('/shifts/current')]);
  $('shift').textContent=shift?'Ca '+shift.code+' · '+shift.cashier.full_name:'Chưa mở ca thu ngân';
  for(const area of areas){
    $('areas').append(el('h2',area.name));const grid=el('div','');grid.className='grid';
    for(const table of area.tables){
      const tile=card(table.code);tile.append(el('p',table.status==='empty'?'Trống':'Có khách'));
      const open=button('Chọn món',()=>{
        const current=draft();
        if(current?.table.id!==table.id){
          if(current?.items.length&&!confirm('Bỏ các món đang chọn để chuyển bàn?'))return;
          save({table,items:[]});
        }
        location.href='menu.html';
      });
      open.disabled=!shift;tile.append(open);grid.append(tile);
    }
    $('areas').append(grid);
  }
  message(areas.some(a=>a.tables.length)?'':'Chưa có bàn.');
});
