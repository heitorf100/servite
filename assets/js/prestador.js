// prestador.js
const prestadoresDiv = document.getElementById('prestadores');

// Função para escapar conteúdo HTML
function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

// Função para carregar prestadores do back-end
function carregarPrestadores() {
    fetch('api/prestador.php?usuario_id=1') // ajuste conforme contexto
        .then(res => res.json())
        .then(data => {
            prestadoresDiv.innerHTML = '';
            if (!data || data.length === 0) {
                prestadoresDiv.innerHTML = '<p>Nenhum prestador encontrado.</p>';
                return;
            }
            data.forEach(p => {
                const div = document.createElement('div');
                div.classList.add('prestador-card');
                div.innerHTML = `
                    <h3>${escapeHTML(p.nome || 'Sem nome')}</h3>
                    <p><b>Serviço/Produto:</b> ${escapeHTML(p.titulo || 'Não informado')}</p>
                    <p><b>Descrição:</b> ${escapeHTML(p.descricao || 'Não informada')}</p>
                    <p><b>Categoria:</b> ${escapeHTML(p.categoria || 'Não informada')}</p>
                `;
                prestadoresDiv.appendChild(div);
            });
        })
        .catch(err => {
            console.error(err);
            prestadoresDiv.innerHTML = '<p>Erro ao carregar prestadores.</p>';
        });
}

carregarPrestadores();
