const API = '/admin';

async function get(path) {
  try {
    const res = await fetch(API + path);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.error('API error:', e);
    return null;
  }
}

async function patch(path, body) {
  try {
    const res = await fetch(API + path, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    return await res.json();
  } catch (e) {
    console.error('API error:', e);
    return null;
  }
}

if (typeof lucide !== 'undefined') lucide.createIcons();
