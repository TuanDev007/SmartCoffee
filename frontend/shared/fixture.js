const user={id:2,full_name:'Tuấn Anh',staff_code:'PV01',role:'waiter'};
const session={user,csrf_token:'fixture'};
const products=['Cà phê sữa','Trà đào','Bạc xỉu'].map((name,i)=>({id:i+1,name,price:[35000,42000,38000][i],description:null,image_path:null,status:i===2?'sold_out':'available',has_sweetness:true,has_bitterness:i===0,has_ice:true}));
const failure=(code,message)=>({error:{code,message}});
export function fixture(path,body){
  if(path==='/auth/login'){
    if(body.username!=='phucvu.01'||body.password!=='demo')return failure('LOGIN_FAILED','Sai tài khoản hoặc mật khẩu.');
    sessionStorage.setItem('fixture.waiter','1');return {data:session};
  }
  if(!sessionStorage.getItem('fixture.waiter'))return failure('AUTH_REQUIRED','Cần đăng nhập.');
  const orders=JSON.parse(localStorage.getItem('fixture.orders')||'{}');
  const tables=[1,2,3,4].map(id=>{
    const paid=Object.values(orders).filter(o=>o.table.id===id);
    return {id,code:'B0'+id,status:paid.length?'occupied':'empty',session:paid.length?{id,started_at:paid[0].paid_at,order_count:paid.length}:null};
  });
  if(path==='/auth/me')return {data:session};
  if(path==='/shifts/current')return {data:{id:1,code:'CA-01',cashier:{id:3,full_name:'Minh',staff_code:'TN01'},opened_at:'2026-11-18T08:00:00+07:00',opening_cash:500000}};
  if(path==='/tables')return {data:[{id:1,name:'Tầng trệt',tables}]};
  if(path==='/menu')return {data:[{id:1,name:'Thực đơn',products}]};
  if(!['/orders','/orders/preview'].includes(path))return failure('NOT_FOUND','Không tìm thấy.');
  if(path==='/orders'&&orders[body.client_request_id])return {data:orders[body.client_request_id]};
  const items=body.items.map(line=>{
    const p=products.find(p=>p.id===line.product_id);
    if(p?.status!=='available'||!Number.isInteger(line.quantity)||line.quantity<1)throw Error('Món hoặc số lượng không hợp lệ.');
    return {product_id:p.id,product_name:p.name,unit_price:p.price,quantity:line.quantity,line_total:p.price*line.quantity};
  });
  const subtotal=items.reduce((sum,i)=>sum+i.line_total,0);
  const voucher=body.voucher_code==='CAFE5'?{code:'CAFE5',discount_type:'amount',discount_value:5000}:null;
  const discount=voucher?Math.min(subtotal,5000):0,total=subtotal-discount;
  const invalid=body.voucher_code&&!voucher;
  if(path==='/orders/preview')return {data:{items,subtotal,total,discount,voucher,...(invalid?{voucher_error:'VOUCHER_INVALID'}:{})}};
  if(invalid)return failure('VOUCHER_INVALID','Voucher không hợp lệ.');
  const received=body.payment.received_amount??null;
  if(body.payment.method==='cash'&&(!Number.isInteger(received)||received<total))return failure('INSUFFICIENT_CASH','Tiền khách đưa chưa đủ.');
  const table=tables.find(t=>t.id===body.table_id);
  const id=Object.keys(orders).length+1;
  const order={id,code:'SC-'+id,invoice_no:'HD-'+id,paid_at:new Date().toISOString(),table:{id:table.id,code:table.code},order_type:'dine_in',subtotal,discount,total,payment:{method:body.payment.method,amount:total,received_amount:received,change:received===null?0:received-total},items:items.map((i,index)=>({id:index+1,product_name:i.product_name,quantity:i.quantity,unit_price:i.unit_price,status:'waiting'}))};
  orders[body.client_request_id]=order;localStorage.setItem('fixture.orders',JSON.stringify(orders));return {data:order};
}
