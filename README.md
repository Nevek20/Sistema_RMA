# Sistema RMA

Sistema web para controle de processadores em processo de **RMA** (Return Merchandise Authorization). Permite cadastrar clientes e modelos de processadores, vinculá-los com seus respectivos números de série e gerenciar os vínculos, com login por usuário e perfis de acesso (admin e operador).

## Tecnologias

- **Backend:** PHP 8.x (extensão `mysqli`)
- **Banco de dados:** MySQL / MariaDB
- **Frontend:** HTML, CSS e JavaScript puro (sem frameworks)
- **Servidor:** XAMPP
- **Gráficos:** Chart.js 4.x (via CDN, somente no Dashboard)

## Pré-requisitos

- PHP 8.0 ou superior com extensão `mysqli` habilitada
- MySQL 5.7+ / MariaDB 10.3+
- XAMPP (ou Apache + MySQL instalados manualmente)

---

## Instalação

### 1. Banco de dados

**Banco novo:** rode o `schema.sql` no phpMyAdmin, HeidiSQL ou no terminal. Ele cria o banco `controle_rma` com todas as tabelas.

**Banco de uma versão anterior:** rode só o `migracao_usuarios.sql`, uma vez. Ele cria a tabela `usuarios` e acrescenta a coluna `usuario` no histórico, sem mexer nos dados.

### 2. Configuração

Copie `config.example.php` para `config.local.php` e ajuste:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');                                   // padrão do XAMPP: vazia
define('DB_NAME', 'controle_rma');
define('BACKUP_DIR', 'C:/Users/SeuUsuario/Desktop/Backup BD');
define('MYSQLDUMP_PATH', 'C:/xampp/mysql/bin/mysqldump.exe');
define('SESSAO_MINUTOS', 480);
```

O `config.local.php` está no `.gitignore`, então a senha do banco e os caminhos da sua máquina não vão para o repositório. Use uma pasta de backup **fora** do `htdocs`, senão os arquivos `.sql` ficam acessíveis pelo navegador. A pasta é criada sozinha no primeiro backup.

### 3. Primeiro usuário

No terminal, dentro da pasta do projeto:

```
C:\xampp\php\php.exe criar_usuario.php matheus admin
```

A senha é pedida em seguida (mínimo 8 caracteres) e não fica no histórico do terminal. O mesmo comando cria operadores (`operador` no lugar de `admin`) e, se o usuário já existe, troca a senha e o perfil. O script só roda pelo terminal; aberto pelo navegador, responde 404.

### 4. Servidor

Coloque a pasta do projeto dentro de `C:/xampp/htdocs/` e acesse:

```
http://localhost/Sistema_RMA/
```

---

## Funcionalidades

| Página | Descrição |
|--------|-----------|
| **Dashboard** | Cards com totais, gráfico de vínculos dos últimos 6 meses e ranking de top 5 clientes e modelos |
| **Processadores Vinculados** | Lista paginada com filtros por cliente, modelo e intervalo de datas; exportação CSV; seleção múltipla para editar/excluir |
| **Vincular Processador** | Associa um ou mais processadores (com SN) a um cliente em um único formulário |
| **Processadores** | Cadastro, busca, edição e exclusão de modelos de processadores |
| **Clientes** | Cadastro, busca, edição e exclusão de clientes |
| **Histórico** | Log de todas as ações realizadas no sistema (vincular, inserir, editar, excluir) com badges por tipo |
| **Backup do Banco** | Gera um dump `.sql` do banco na pasta configurada e exibe quando foi o último backup |

### Login e perfis

Todas as páginas e a exportação CSV exigem login.

| Perfil | Pode |
|--------|------|
| **operador** | Consultar, cadastrar clientes e processadores, vincular e exportar CSV |
| **admin** | Tudo do operador, mais editar, excluir, ver o Histórico e fazer Backup |

As permissões são conferidas **no servidor**, antes de a página ser montada. Esconder botões na tela é só conforto; um POST montado na mão por um operador para excluir algo recebe `403`.

Para o admin, o cadeado **🔒** no topo continua existindo como trava contra clique sem querer: ele mostra as caixas de seleção e a barra de editar/excluir.

### Segurança

- Senhas guardadas com `password_hash` (bcrypt) e conferidas com `password_verify`
- ID de sessão trocado no login (contra fixação de sessão), cookie `HttpOnly` e `SameSite=Lax`, expiração por inatividade
- Token CSRF em todo formulário POST, inclusive login e logout
- Bloqueio de 15 minutos após 5 senhas erradas para o mesmo usuário
- Mensagem de erro igual para usuário inexistente e senha errada, com o mesmo tempo de resposta
- Consultas com prepared statements em tudo que recebe dado do usuário
- Arquivos de `assets/php/` não rodam se acessados direto pela URL
- Histórico registra quem fez cada ação, além de logins, falhas de login, logouts e backups

### Exportação CSV

Na página de Processadores Vinculados, o botão **↓ Exportar CSV** gera um arquivo com os registros do filtro ativo no momento. O arquivo inclui BOM UTF-8 para abrir corretamente no Excel sem problemas de acentuação.

---

## Estrutura do projeto

```
Sistema_RMA/
├── index.php                  # Roteamento de páginas e checagem de permissões
├── login.php
├── logout.php
├── exportar.php               # Exportação CSV (exige login)
├── criar_usuario.php          # Cria/atualiza usuários pelo terminal
├── config.example.php         # Modelo do config.local.php
├── schema.sql                 # Schema completo (banco novo)
├── migracao_usuarios.sql      # Atualização de banco de versão anterior
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── processadores.js
│   └── php/
│       ├── db.php             # Conexão + registrarLog()
│       ├── auth.php           # Sessão, login, perfis e CSRF
│       ├── dashboard.php
│       ├── listar.php
│       ├── vincular.php
│       ├── produto.php
│       ├── cliente.php
│       ├── historico.php
│       └── backup.php
```

---

## Limitações conhecidas

- **Sem Post/Redirect/Get (PRG):** após salvar ou excluir, a página não redireciona. Recarregar logo depois de uma ação pode reenviar o formulário.

- **Usuários só pelo terminal:** ainda não há tela de gerenciamento de usuários; criar, trocar senha e mudar perfil é pelo `criar_usuario.php`.

- **HTTPS:** em rede interna o sistema roda em HTTP. Se for exposto fora da rede, configure HTTPS no Apache; o cookie de sessão passa a ser `Secure` sozinho.

---

## Autor

Matheus Guida | [GitHub](https://github.com/Nevek20)
