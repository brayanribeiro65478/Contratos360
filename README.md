# Contratos 360 (PHP + MySQL)

Sistema Inteligente de Gestão de Contratos e Atendimento Público — desenvolvido em
**PHP puro (sem frameworks) + MySQL + HTML/CSS**, para a Cariri Inovação.

Fluxo implementado:
`Contrato → Chamado → Atendimento → Evidências → Medição → Relatório → Faturamento`

## 1. Requisitos

- **XAMPP**, **WAMP** ou **Laragon** (qualquer um serve — todos incluem PHP + MySQL + Apache)
- PHP 8.0 ou superior (com extensão `pdo_mysql` ativada — já vem ativada por padrão nesses pacotes)

## 2. Instalação passo a passo (Windows)

### 2.1. Instalar o XAMPP
Baixe em https://www.apachefriends.org/pt_br/index.html e instale normalmente.

### 2.2. Copiar o projeto
Extraia esta pasta `contratos360-php` para dentro de:
```
C:\xampp\htdocs\contratos360
```
(o caminho pode variar se você usar WAMP/Laragon — use a pasta `www` ou `htdocs` deles)

### 2.3. Iniciar Apache e MySQL
Abra o **XAMPP Control Panel** e clique em **Start** ao lado de **Apache** e **MySQL**.

### 2.4. Criar o banco de dados
1. Acesse **http://localhost/phpmyadmin**
2. Clique em **Importar** (Import)
3. Selecione o arquivo `config/database.sql` (dentro da pasta do projeto)
4. Clique em **Executar/Importar**

Isso cria o banco `contratos360`, todas as tabelas e os dados de exemplo
(1 órgão, 1 contrato e as 3 contas de demonstração).

### 2.5. Conferir as credenciais do banco
Abra `config/config.php` e confira se os dados batem com o seu ambiente
(no XAMPP/WAMP padrão, usuário `root` e senha em branco já funcionam sem alterar nada):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'contratos360');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 2.6. Acessar o sistema
No navegador, acesse:
```
http://localhost/contratos360/
```

## 3. Contas de demonstração

| Perfil                     | E-mail                          | Senha        |
|-----------------------------|----------------------------------|--------------|
| Administrador/Gestor        | admin@cariri.com                | admin123     |
| Técnico                     | tecnico@cariri.com               | tecnico123   |
| Cliente (servidor público)  | servidor@crato.ce.gov.br        | cliente123   |

Já existe um órgão de exemplo (Prefeitura Municipal de Crato) com um contrato
ativo (`CT-2026-001`) pronto para testar o fluxo completo.

## 4. Estrutura do projeto

```
contratos360-php/
├── config/
│   ├── config.php        # Configuração central (banco, empresa, uploads)
│   └── database.sql      # Schema MySQL + dados de exemplo (importar no phpMyAdmin)
├── includes/
│   ├── db.php             # Conexão PDO
│   ├── auth.php           # Sessão, login/logout, controle de acesso por perfil
│   ├── functions.php      # Funções utilitárias (badges, formatação, numeração)
│   ├── header_admin.php / footer_admin.php   # Layout com menu lateral (admin/técnico)
│   └── header_client.php / footer_client.php # Layout simples (servidor público)
├── assets/css/style.css
├── uploads/                # Evidências/anexos dos atendimentos (protegida por .htaccess)
├── login.php, dashboard.php, orgaos.php, contratos.php, chamados.php, ...
└── README.md
```

Cada tela é um arquivo `.php` independente (sem roteador central), o que facilita
encontrar e editar qualquer funcionalidade diretamente.

## 5. Sobre o relatório em PDF

O relatório mensal (`medicao_relatorio.php`) é gerado como uma página HTML
formatada para impressão, com um botão **"Imprimir / Salvar como PDF"**.
Ao clicar, o próprio navegador gera o PDF (Ctrl+P → Destino: "Salvar como PDF"),
sem depender de nenhuma biblioteca externa. O layout já vem pronto para isso
(cabeçalho da empresa, dados do contrato, tabela de atendimentos e totais).

Se no futuro você quiser um PDF gerado diretamente pelo servidor (sem depender
do navegador), é possível integrar bibliotecas como **Dompdf** ou **TCPDF** via
Composer — a estrutura do `medicao_relatorio.php` já está pronta para servir de
base para isso.

## 6. Fluxo de teste sugerido

1. Login como **servidor público** → "Abrir chamado" → preencher e enviar.
2. Login como **administrador** → Chamados → abrir o chamado → atribuir a um técnico.
3. Login como **técnico** → abrir o chamado → "Registrar atendimento" (horas + evidências).
4. Login como **administrador** → Medições → "Gerar medição" (escolher contrato e período)
   → abrir a medição → "Aprovar medição" → "Ver relatório mensal" → Imprimir/Salvar como PDF.
5. Faturamento → "Gerar fatura" a partir da medição aprovada → atualizar status (Emitida/Paga).

## 7. Segurança e LGPD

- Cada perfil (admin / técnico / cliente) só enxerga os dados aos quais tem permissão
  (o cliente só vê chamados, contratos e medições aprovadas do seu próprio órgão).
- Senhas são armazenadas com hash bcrypt (`password_hash`/`password_verify`), nunca em texto puro.
- Todas as consultas usam **PDO com prepared statements**, prevenindo injeção de SQL.
- A pasta `uploads/` tem um `.htaccess` que bloqueia a execução de scripts PHP nela,
  mesmo que alguém envie um arquivo malicioso disfarçado.
- Há registro de histórico (tabela `historico`) para ações de login/logout, servindo de
  base para auditoria.
- Este é um protótipo acadêmico: para produção, recomenda-se HTTPS, uma `SECRET_KEY`/salt
  de sessão dedicada, backups automáticos do banco e uma política de retenção de dados
  conforme a LGPD.

## 8. Erros comuns

**"Não foi possível conectar ao banco de dados"**
→ Verifique se o serviço MySQL está rodando no XAMPP/WAMP e se as credenciais em
`config/config.php` estão corretas.

**Página em branco ou erro 500**
→ Confirme que a extensão `pdo_mysql` está ativada no PHP (no XAMPP já vem ativada
por padrão). Verifique também os logs de erro do Apache (`xampp/apache/logs/error.log`).

**"Not Found" ao acessar http://localhost/contratos360/**
→ Confirme que a pasta do projeto está exatamente em `htdocs/contratos360`
(ou na pasta equivalente do seu WAMP/Laragon).
