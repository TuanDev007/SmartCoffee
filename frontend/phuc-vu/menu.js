import {api} from '../shared/api.js';
import {$,el,card,button,field,draft,save,start,money,message} from '../shared/ui.js';
start(async()=>{
  const d=draft();if(!d){location.replace('tables.html');return}
  const [categories,shift]=await Promise.all([api('/menu'),api('/shifts/current')]);
  if(!shift)throw Error('Chưa mở ca. Hãy quay lại sơ đồ bàn khi thu ngân mở ca.');
  $('table').textContent='Bàn '+d.table.code;
  function cart(){
    $('cart').replaceChildren();$('next').disabled=!d.items.length;
    for(const [index,line] of d.items.entries()){
      const row=el('li',line.name+' × '+line.quantity+(line.note?' · '+line.note:''));
      row.append(button('Bỏ',()=>{d.items.splice(index,1);delete d.requestId;save(d);cart()}));$('cart').append(row);
    }
  }
  for(const category of categories){
    $('products').append(el('h2',category.name));const grid=el('div','');grid.className='grid';
    for(const p of category.products){
      const tile=card(p.name);tile.append(el('p',money(p.price)));
      const options={};
      for(const [key,title] of [['sweetness','Độ ngọt'],['bitterness','Độ đắng'],['ice','Đá']]){
        if(!p['has_'+key])continue;
        const select=el('select','');
        for(const [value,label] of [['normal','Bình thường'],['less','Ít'],['more','Nhiều'],...(key==='ice'?[['none','Không đá']]:[])]){
          const option=el('option',label);option.value=value;select.append(option);
        }
        tile.append(field(title,select));options[key]=select;
      }
      const qty=el('input','');qty.type='number';qty.min='1';qty.value='1';qty.required=true;
      const note=el('input','');tile.append(field('Số lượng',qty),field('Ghi chú',note));
      const add=button(p.status==='available'?'Thêm món':'Hết món',()=>{
        if(!qty.reportValidity())return;
        d.items.push({name:p.name,product_id:p.id,quantity:qty.valueAsNumber,sweetness:options.sweetness?.value??null,bitterness:options.bitterness?.value??null,ice:options.ice?.value??null,note:note.value.trim()||null});
        delete d.requestId;save(d);cart();message('Đã thêm '+p.name);
      });
      add.disabled=p.status!=='available';tile.append(add);grid.append(tile);
    }
    $('products').append(grid);
  }
  cart();message(categories.some(c=>c.products.length)?'':'Thực đơn đang trống.');
  $('next').onclick=()=>{d.requestId??=crypto.randomUUID();save(d);location.href='checkout.html'};
});
