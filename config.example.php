<?php
// Copie este arquivo para config.local.php e ajuste para o seu ambiente.
// O config.local.php fica no .gitignore, então senhas e caminhos da sua
// máquina nunca vão para o repositório.

// Banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');               // padrão do XAMPP: vazia
define('DB_NAME', 'controle_rma');

// Backup: use uma pasta FORA do htdocs, senão o .sql fica acessível pelo navegador
define('BACKUP_DIR', 'C:/Users/SeuUsuario/Desktop/Backup BD');
define('MYSQLDUMP_PATH', 'C:/xampp/mysql/bin/mysqldump.exe');

// Minutos sem uso até a sessão expirar e pedir login de novo
define('SESSAO_MINUTOS', 480);
