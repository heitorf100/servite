const listaDiv = document.getElementById('listaAvaliacoes');

function carregarAvaliacoes(){
    fetch('http://localhost/Servite/api/avaliacao.php?prestador_id=1')
    .then(res=>res.json())
    .then(json=>{
        listaDiv.innerHTML = '';
        json.forEach(a=>{
            listaDiv.innerHTML += `<p><b>${a.cliente_nome}:</b> ${a.conteudo} (${a.nota})</p>`;
        });
    });
}

document.getElementById('formAvaliacao').addEventListener('submit', e=>{
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    fetch('http://localhost/Servite/api/avaliacao.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(data)
    }).then(()=>{
        e.target.reset();
        carregarAvaliacoes();
    });
});

carregarAvaliacoes();
