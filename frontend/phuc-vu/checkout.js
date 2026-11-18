import {api} from '../shared/api.js';
import {$,el,money,draft,save,start,message} from '../shared/ui.js';
start(async()=>{
  const d=draft();if(!d?.items.length||!d.requestId){location.replace('tables.html');return}
  $('table').textContent='Bàn '+d.table.code;
  const payload={order_type:'dine_in',table_id:d.table.id,items:d.items.map(({name,...line})=>line),voucher_code:d.voucher||null};
  const form=$('pay'),amount=form.elements.amount,method=form.elements.method;
  $('voucher').value=d.voucher||'';
  let preview,version=0;
  function invalidate(){version++;preview=null;$('confirm').disabled=true;$('summary').replaceChildren()}
  async function refresh(){
    invalidate();const revision=version;
    payload.voucher_code=$('voucher').value.trim()||null;d.voucher=payload.voucher_code;save(d);
    try{
      const result=await api('/orders/preview',payload);if(revision!==version)return;
      for(const item of result.items)$('summary').append(el('p',item.product_name+' × '+item.quantity+' · '+money(item.line_total)));
      $('summary').append(el('p','Tạm tính '+money(result.subtotal)+' · Giảm '+money(result.discount)),el('strong','Cần thu '+money(result.total)));
      amount.min=result.total;
      if(result.voucher_error){message('Voucher không dùng được. Hãy sửa hoặc xóa mã rồi áp dụng lại.');return}
      preview=result;$('confirm').disabled=false;message('');
    }catch(e){message(e.message)}
  }
  $('voucher').oninput=invalidate;$('apply').onclick=refresh;
  method.onchange=()=>{amount.disabled=method.value!=='cash';$('cash').hidden=amount.disabled};
  form.onsubmit=async event=>{
    event.preventDefault();if(!preview||$('confirm').disabled)return;
    $('confirm').disabled=true;$('apply').disabled=true;$('voucher').disabled=true;
    try{
      const payment={method:method.value};if(method.value==='cash')payment.received_amount=amount.valueAsNumber;
      const order=await api('/orders',{...payload,client_request_id:d.requestId,payment});
      sessionStorage.removeItem('waiter.draft');form.hidden=true;$('receipt').hidden=false;
      const link=el('a','Về sơ đồ bàn');link.href='tables.html';
      $('receipt').append(el('h2','Đã thanh toán'),el('p',order.invoice_no+' · '+money(order.total)),el('p','Tiền thối '+money(order.payment.change||0)),link);message('');
    }catch(e){message(e.message);$('confirm').disabled=false;$('apply').disabled=false;$('voucher').disabled=false}
  };
  await refresh();
});
