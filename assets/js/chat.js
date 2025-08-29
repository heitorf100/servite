// chat.js
const mensagensDiv = document.getElementById('chat'); // div do chat
const input = document.getElementById('msgInput');
const btnSend = document.getElementById('btnSend');

// Ajuste conforme contexto do usuário/agendamento
const agendamentoId = 1;
const clienteId = 1;
const prestadorId = 1;

// Função para escapar conteúdo HTML e evitar injeção
function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

// Carregar mensagens do agendamento
function carregarMensagens() {
    fetch(`api/mensagem.php?agendamento_id=${agendamentoId}`)
        .then(res => res.json())
        .then(data => {
            mensagensDiv.innerHTML = '';
            data.forEach(m => {
                const div = document.createElement('div');
                const remetente = (m.cliente_id === clienteId) ? 'Você' : 'Prestador';
                div.innerHTML = `<b>${remetente}:</b> ${escapeHTML(m.conteudo)}`;
                mensagensDiv.appendChild(div);
            });
            mensagensDiv.scrollTop = mensagensDiv.scrollHeight;
        })
        .catch(console.error);
}

// Enviar mensagem
btnSend.addEventListener('click', () => {
    const conteudo = input.value.trim();
    if (!conteudo) return;

    fetch('api/mensagem.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            agendamento_id: agendamentoId,
            cliente_id: clienteId,
            prestador_id: prestadorId,
            conteudo: conteudo
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            carregarMensagens();
        }
    })
    .catch(console.error);
});

// Atualização periódica do chat
setInterval(carregarMensagens, 3000); // atualiza a cada 3 segundos
carregarMensagens();
