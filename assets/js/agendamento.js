document.getElementById('formAgendamento').addEventListener('submit', function(e){
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this).entries());
    fetch('http://localhost/Servite/api/agendamento.php', {
        method: 'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(data)
    })
    .then(res=>res.json())
    .then(json=>document.getElementById('resposta').innerText=JSON.stringify(json))
    .catch(err=>console.error(err));
});
