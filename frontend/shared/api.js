// Set to 'live' when the same-origin PHP endpoints are deployed.
export const mode = 'fixture';
let csrf = '';
export async function api(path, body) {
  let result;
  if (mode === 'fixture') {
    const {fixture} = await import('./fixture.js');
    result = fixture(path, body);
  } else {
    const response = await fetch('/api'+path, {
      method: body ? 'POST' : 'GET', credentials:'include',
      headers: body ? {'Content-Type':'application/json','X-CSRF-Token':csrf} : {},
      body: body ? JSON.stringify(body) : undefined
    });
    result = await response.json();
  }
  if(result.error) throw Object.assign(new Error(result.error.message),{code:result.error.code});
  if(result.data?.csrf_token) csrf=result.data.csrf_token;
  return result.data;
}
