<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Servite — Protótipo</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main class="wrap">
    <h1>Servite — Protótipo</h1>

    <section class="card">
      <h2>Novo Usuário (teste)</h2>
      <form id="formUser">
        <label>Nome*<input name="nome" required></label>
        <label>CPF/CNPJ*<input name="cpf_cnpj" required></label>
        <label>E-mail*<input name="email" type="email" required></label>
        <label>Senha*<input name="senha" type="password" required></label>
        <label>Telefone<input name="telefone"></label>
        <label>Tipo
          <select name="tipo">
            <option>Cliente</option>
            <option>Prestador</option>
          </select>
        </label>
        <div class="actions">
          <button type="submit">Salvar</button>
        </div>
      </form>
      <div id="msg"></div>
    </section>

    <section class="card">
      <h2>Usuários Cadastrados</h2>
      <button id="btnList">Listar</button>
      <div id="list"></div>
    </section>
  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
