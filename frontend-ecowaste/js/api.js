// Frontend API wrapper. Uses window.__BACKEND_BASE_URL__ injected at build time.
const BACKEND = (typeof window !== 'undefined' && window.__BACKEND_BASE_URL__) ? window.__BACKEND_BASE_URL__ : '';

async function apiFetch(path, opts = {}){
  if (!BACKEND) return { demo: true, message: 'No backend configured' };
  const url = BACKEND.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
  const res = await fetch(url, Object.assign({credentials: 'include', headers: {'Accept':'application/json'}}, opts));
  const text = await res.text();
  try{ return JSON.parse(text); }catch(e){ return { ok: res.ok, status: res.status, text }; }
}

export async function login(email, password){
  if (!BACKEND) return { demo: true, success: true, role: email.includes('admin') ? 'admin' : 'user', name: email.split('@')[0] };
  return apiFetch('/api/login.php', { method: 'POST', body: new URLSearchParams({email,password}) });
}

export async function signup(data){
  if (!BACKEND) return { demo: true, success: true };
  return apiFetch('/api/signup.php', { method: 'POST', body: JSON.stringify(data), headers: {'Content-Type':'application/json'} });
}

export async function requestPickup(payload){
  if (!BACKEND) return { demo: true, success: true };
  return apiFetch('/api/request_pickup.php', { method: 'POST', body: JSON.stringify(payload), headers: {'Content-Type':'application/json'} });
}

export async function getCenters(){
  if (!BACKEND) return { demo: true, centers: [] };
  return apiFetch('/api/centers.php');
}

export function isDemo(){ return !BACKEND; }

window.EcoApi = { login, signup, requestPickup, getCenters, isDemo };
