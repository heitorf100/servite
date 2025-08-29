const API_USERS = '/Servite/api/users.php';

document.getElementById('formUser').addEventListener('submit', async (e) => {
  e.preventDefault();
  const f = e.target;
  const data = {
    nome: f.nome.value,
    cpf_cnpj: f.cpf_cnpj.value,
    email: f.email.value,
    senha: f.senha.value,
    telefone: f.telefone.value,
    tipo: f.tipo.value
  };
  const res = await fetch(API_USERS, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  });
  const json = await res.json();
  document.getElementById('msg').innerText = json.error ? ('Erro: ' + json.error) : ('Salvo! ID: ' + (json.id || '—'));
  listarUsuarios();
  f.reset();
});

document.getElementById('btnList').addEventListener('click', listarUsuarios);

async function listarUsuarios() {
  const res = await fetch(API_USERS);
  const arr = await res.json();
  const el = document.getElementById('list');
  if (!Array.isArray(arr) || arr.length === 0) {
    el.innerHTML = '<div class="muted">Nenhum usuário</div>';
    return;
  }
  el.innerHTML = '<table><thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Tipo</th></tr></thead><tbody>' +
    arr.map(u => `<tr><td>${u.id}</td><td>${u.nome}</td><td>${u.email}</td><td>${u.tipo}</td></tr>`).join('') +
    '</tbody></table>';
}

// carrega lista inicialmente
listarUsuarios();
