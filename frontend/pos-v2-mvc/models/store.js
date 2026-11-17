export class Store {
  async load() {
    const response = await fetch(new URL('./screens.json', import.meta.url));
    if (!response.ok) throw Error('Không tải được dữ liệu');
    this.screens = await response.json();
  }
  get(id) { return this.screens[id]; }
}
export const roles = {s:'Phục vụ', c:'Thu ngân', b:'Pha chế', m:'Quản lý'};
