const roomId = document.body.dataset.room;
const csrf = document.querySelector('meta[name="csrf"]').content;
const list = document.getElementById('messages');
const form = document.getElementById('chatForm');
const input = document.getElementById('text');
const statusBox = document.getElementById('status');

let signature = null;   // remembers which messages are on screen, so we only redraw on changes

function showError(msg) {
  statusBox.textContent = msg;
  statusBox.hidden = !msg;
}

async function post(params) {
  const res = await fetch('api.php', {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrf },
    body: new URLSearchParams(params),
  });
  if (res.status === 401) { location.href = 'login.php'; return null; }
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Something went wrong');
  return data;
}

function render(messages) {
  list.textContent = '';

  if (messages.length === 0) {
    const empty = document.createElement('li');
    empty.className = 'empty';
    empty.textContent = 'No messages yet. Say hello!';
    list.appendChild(empty);
    return;
  }

  messages.forEach((m) => {
    const li = document.createElement('li');
    li.className = 'msg' + (m.mine ? ' mine' : '');

    const meta = document.createElement('div');
    meta.className = 'meta';

    const who = document.createElement('strong');
    who.textContent = m.username;           // textContent: user input can never inject HTML

    const time = document.createElement('span');
    time.textContent = m.time;

    meta.append(who, time);

    if (m.mine) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'del';
      del.dataset.id = m.id;
      del.textContent = 'Delete';
      meta.append(del);
    }

    const p = document.createElement('p');
    p.textContent = m.body;

    li.append(meta, p);
    list.appendChild(li);
  });
}

async function load(forceScroll = false) {
  try {
    const res = await fetch('api.php?room=' + roomId);
    if (res.status === 401) { location.href = 'login.php'; return; }
    const data = await res.json();

    const sig = data.map((m) => m.id).join(',');
    if (sig === signature && !forceScroll) return;

    const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
    const firstLoad = signature === null;
    signature = sig;

    render(data);
    if (nearBottom || firstLoad || forceScroll) list.scrollTop = list.scrollHeight;
    showError('');
  } catch (err) {
    showError('Connection problem. Retrying...');
  }
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const body = input.value.trim();
  if (!body) return;

  try {
    await post({ action: 'send', room: roomId, body });
    input.value = '';
    await load(true);
  } catch (err) {
    showError(err.message);
  }
});

list.addEventListener('click', async (e) => {
  const btn = e.target.closest('.del');
  if (!btn) return;
  try {
    await post({ action: 'delete', id: btn.dataset.id });
    await load();
  } catch (err) {
    showError(err.message);
  }
});

load();
setInterval(load, 2500);   // poll for new messages every 2.5 seconds
