# Peixoto OS

Sistema operacional pessoal de Jean Peixoto: Second Brain, produtividade, projetos,
financeiro, documentos e assistência técnica em um único lugar.

O projeto nasceu como iFixAdmin, focado em assistência técnica Apple. A assistência
continua existindo, mas agora é um módulo dentro de uma arquitetura LifeOS.

## Stack

- PHP 8.3 (PHP-FPM), sem framework
- PostgreSQL 16 (PDO + prepared statements)
- HTML, CSS e JavaScript puro
- Nginx
- Dompdf para PDFs de OS

## Conceito

Tudo no sistema deve poder se relacionar com qualquer outra coisa.

- Uma nota pode estar ligada a cliente, projeto, OS, equipamento e documento.
- Um projeto pode reunir tarefas, documentação, financeiro, links, GitHub e servidor.
- Uma OS pode aparecer na Timeline junto de pagamentos, comentários e conhecimento técnico.
- A camada `ai_context_sources` prepara o conteúdo para um futuro chat interno.

## Módulos principais

| Rota | Função |
|---|---|
| `/home` | Home LifeOS: rotina, foco do dia, projetos, notas, financeiro, OS e timeline |
| `/brain` | Notas em Markdown, tags, favoritos, projeto e relações |
| `/agenda` | Eventos, reuniões, blocos de rotina e lembretes |
| `/tasks` | Kanban de tarefas com drag-and-drop, prioridade, prazo e checklist |
| `/projects` | Projetos com objetivo, documentação, tecnologias, GitHub, servidor e domínio |
| `/knowledge` | Base de conhecimento pesquisável |
| `/ideas` | Captura de ideias |
| `/diary` | Diário pessoal com humor, produtividade, aprendizados e melhorias |
| `/documents` | Biblioteca de arquivos pesquisável |
| `/development` | Visão de projetos de software |
| `/automoveis` | Registros e histórico de automóveis |
| `/timeline` | Histórico global cronológico |
| `/settings` | Estrutura do LifeOS e entidades relacionáveis |

## Assistência técnica

Os módulos existentes permanecem:

| Rota | Função |
|---|---|
| `/clients` | Clientes |
| `/devices` | Equipamentos |
| `/orders` | Ordens de serviço |
| `/inventory` | Estoque |
| `/financial` | Financeiro |
| `/schematics` | Esquemas elétricos e boardviews |

## Banco de dados

Instalação nova:

```bash
psql -h 127.0.0.1 -U peixotoadmin -d peixotoadmin -f database/schema.sql
```

Migração de uma instalação existente:

```bash
psql -h 127.0.0.1 -U peixotoadmin -d peixotoadmin -f database/migrations/002_lifeos_foundation.sql
```

Migração antiga de limpeza single-user, apenas se necessário:

```bash
psql -h 127.0.0.1 -U peixotoadmin -d peixotoadmin -f database/migrations/001_single_user_cleanup.sql
```

## Desenvolvimento local

```bash
cp .env.example .env
php -S 127.0.0.1:8080 -t public public/index.php
```

Depois acesse `http://127.0.0.1:8080`.

## Segurança

- Sessões com cookie `HttpOnly`, `Secure` quando HTTPS e `SameSite=Lax`
- CSRF obrigatório em todo POST
- PDO com prepared statements
- Saída escapada com `htmlspecialchars`
- Uploads ficam fora da raiz pública e são servidos por rotas autenticadas
