import {Store, roles} from '../models/store.js';
import {home, screen} from '../views/render.js';
const store = new Store();
const root = document.querySelector('#app');
function navigate(device, id) { location.hash = id === 'index' ? '/' : '/' + device + '/' + id; }
function render() {
  const [,device,id] = location.hash.replace(/^#/,'').split('/');
  if (!store.get(id) || !['mobile','desktop'].includes(device)) { home(root,navigate); return; }
  screen(root,store.get(id),device,Object.values(store.screens).filter(s=>s.role===store.get(id).role),navigate);
  window.scrollTo(0,0);
}
window.addEventListener('hashchange',render);
try { await store.load(); render(); } catch(error) { root.textContent = error.message + '. Mở bằng setup.bat.'; }
