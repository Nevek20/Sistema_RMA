# Sistema RMA

Sistema web para controle de processadores em processo de **RMA** (Return Merchandise Authorization). Permite cadastrar clientes e modelos de processadores, vinculá-los com seus respectivos números de série e gerenciar os vínculos através de uma interface com modo de edição protegido por senha.

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

Execute o arquivo `schema.sql` incluído no repositório, ou rode o SQL abaixo manualmente no phpMyAdmin, MySQL Workbench, HeidiSQL etc.:

```sql
CREATE DATABASE IF NOT EXISTS controle_rma
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE controle_rma;

CREATE TABLE clientes (
    id   INT          AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE processadores (
    id     INT          AUTO_INCREMENT PRIMARY KEY,
    modelo VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE processador_cliente (
    id             INT          AUTO_INCREMENT PRIMARY KEY,
    cliente_id     INT          NOT NULL,
    processador_id INT          NOT NULL,
    serial_number  VARCHAR(255) NOT NULL UNIQUE,
    data_cadastro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id)     REFERENCES clientes(id),
    FOREIGN KEY (processador_id) REFERENCES processadores(id)
);

CREATE TABLE logs (
    id        INT         AUTO_INCREMENT PRIMARY KEY,
    acao      VARCHAR(50) NOT NULL,
    entidade  VARCHAR(50) NOT NULL,
    detalhe   TEXT,
    criado_em DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

> **Banco já existente?** Se você já tem o banco de uma versão anterior, só precisa criar a tabela `logs` — as outras já existem.

### 2. Configuração da conexão

Edite `assets/php/db.php` com os dados do seu ambiente:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // senha do MySQL (padrão XAMPP: vazia)
define('DB_NAME', 'controle_rma');
define('ADMIN_PASS', '1234');     // ⚠️ altere antes de usar em produção
```

### 3. Configuração do backup

Edite o topo de `assets/php/backup.php` com os caminhos do seu ambiente:

```php
$backupDir = "C:/Users/SeuUsuario/Desktop/Backup BD"; // pasta de destino
$mysqldump = '"C:/xampp/mysql/bin/mysqldump.exe"';     // caminho do mysqldump
```

> **Atenção:** o recurso de backup foi desenvolvido para **Windows com XAMPP**. A pasta de destino precisa existir antes de fazer o primeiro backup.

### 4. Servidor

Coloque a pasta do projeto dentro de `C:/xampp/htdocs/` e acesse via:

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

### Modo de edição

As operações de edição e exclusão ficam bloqueadas por padrão. Clique no cadeado **🔒** no canto superior direito e informe a senha definida em `db.php` para habilitar o modo de edição. Clique novamente no cadeado **🔓** para sair.

### Exportação CSV

Na página de Processadores Vinculados, o botão **↓ Exportar CSV** gera um arquivo com os registros do filtro ativo no momento. O arquivo inclui BOM UTF-8 para abrir corretamente no Excel sem problemas de acentuação.

---

## Estrutura do projeto

```
Sistema_RMA/
├── index.php                  # Entry point — roteamento de páginas
├── exportar.php               # Script standalone de exportação CSV
├── schema.sql                 # Schema completo do banco de dados
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── processadores.js
│   └── php/
│       ├── db.php             # Conexão + registrarLog()
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

- **Senha exposta no HTML:** A senha do modo de edição é embutida no fonte da página para comparação client-side. Para uso em rede interna restrita isso é aceitável, mas em produção o recomendado é mover a verificação para o servidor via sessão PHP.

- **Sem Post/Redirect/Get (PRG):** Após salvar ou excluir registros, a página não faz redirecionamento. Recarregar imediatamente após uma ação pode reenviar o formulário.

- **Backup somente no Windows/XAMPP:** Os caminhos do `mysqldump` e da pasta de destino estão fixos para o ambiente Windows com XAMPP.

---

## Autor

Matheus Guida — [GitHub](https://github.com/Nevek20)
