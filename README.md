
# API de Produtos - Projeto Backend PHP

## Visão Geral

Este projeto consiste em uma **API RESTful** para gerenciamento de produtos e categorias, desenvolvida em **PHP 8**. A aplicação atende às demandas do cliente XPTO Ltda., permitindo operações como cadastro, edição, exclusão, listagem e funcionalidades adicionais como controle de estoque, tradução de categorias, comentários e geração de relatórios.

---

## 🧱 Funcionalidades Principais

- CRUD de **Produtos**
- CRUD de **Categorias** (com suporte a traduções)
- Filtros e ordenações de produtos
- Controle de **Estoque**
- Área de **Comentários** com suporte a respostas e curtidas
- **Relatório** em HTML com logs de alterações
- **Histórico de alterações** por usuário
- Integração com Postman
- Docker para ambiente de desenvolvimento
- Migrations e testes automatizados

---

## 📦 Stack Utilizada

- **PHP >= 8.0**
- **SQLite** (com desafio para uso de banco relacional)
- **Composer**
- **Phinx** (migrations)
- **PHPUnit** (testes)
- **PHPStan** (análise estática)
- Docker

---

## ▶️ Instruções para Rodar o Projeto

### Requisitos
- PHP >= 8.0 com extensões:
  - `pdo`, `pdo_sqlite`, `sqlite3`
- SQLite
- Composer

### Instalação Manual
```bash
composer install
php -S localhost:8000 -t public
```

### Usando Docker

#### Build da Imagem
```bash
docker build -tapi-product .
```

#### Subir o container com docker-compose
```bash
docker-compose up
```

#### Rodar comandos úteis
```bash
# Rodar testes
docker-compose run --rm app composer test

# Rodar migrations
docker-compose run --rm app composer migrate

# Análise estática
docker run --rm -v ${PWD}:/app -w /appapi-product ./vendor/bin/phpstan analyse src

# Verificar deploy
docker run --rm -v "${PWD}:/app" -w /app api-products ./check_deploy.sh
```

---

## 🛠 Rotas da API

### Produtos
- `GET /products`
- `GET /products/{id}`
- `POST /products`
- `PUT /products/{id}`
- `DELETE /products/{id}`
- `GET /products/last-edit/{id}` → Última alteração
- Filtros suportados:
  - `?active=1`
  - `?category=3`
  - `?created_at=ASC|DESC`
  - `?stock_min=10`
  - `?lang=pt`

### Categorias
- `GET /categories`
- `GET /categories/{id}`
- `POST /categories`
- `PUT /categories/{id}`
- `DELETE /categories/{id}`

### Traduções
- `POST /categories/{id}` com payload:
```json
{
  "translations": [
    { "lang_code": "en", "label": "home" },
    { "lang_code": "pt", "label": "casa" }
  ]
}
```

### Comentários
- `POST /products/{id}/comments`
- `POST /products/comments/{id}/reply`
- `DELETE /products/comments/{id}`
- `POST /products/comments/{id}/like`
- `GET /products/{id}/comments`

### Relatório
- `GET /report` → Gera HTML com logs no formato:
  ```
  (Nome do usuário, Tipo de alteração, Data)
  ```

---

## 📄 Documentação da API

- Arquivo Postman: `docs/postman-api.json`
- PDF da documentação: `docs/api-docs.pdf`
- Header obrigatório para autenticação:
  ```
  admin_user_id: 1
  ```

---

## 💾 Banco de Dados

- Banco em `SQLite` com backup em `db/db-backup.sqlite3`
- Migrations via Phinx
  - Criar: `composer create-migration`
  - Rodar: `composer migrate`
  - Reverter: `composer rollback`

---

## 🚀 Checklist de Implementações

### Correções Realizadas
- Categoria incorreta na listagem de produtos
- Produtos com categoria `null`
- Produtos com múltiplas categorias
- Filtros de ativo/inativo e por categoria
- Ordenação por data
- Correção na coluna de logs do relatório
- Último log de alteração por usuário
- Correção dos testes unitários

### Funcionalidades Novas
- Tradução de categorias
- Parâmetro `lang` nas buscas
- Controle de estoque
- Área de comentários com resposta, exclusão, curtida e listagem hierárquica
- Rota dedicada para última edição de produto

---

## 🧪 Qualidade de Código

- ✅ Testes automatizados com PHPUnit
- ✅ Análise estática com PHPStan
- ✅ Linter implementado
- ✅ Script `check_deploy.sh` para validações antes de deploy

---

## 📌 Observações Finais

- Desenvolvimento realizado em ambiente **Windows**
- Corrigidos problemas nas rotas com `/` finais
- Tratamento de categorias padrão e personalizadas
- Uso de `fetchAll` e `clone` para manipulação correta de múltiplas categorias
- Filtros e ordenações implementados com segurança
- Logs formatados com concatenação e tradução dos tipos
- Comentários isolados devem ser futuramente refatorados para controller próprio

---

## 📝 Observações sobre Docker

- Ambiente funciona 100% via containers
- Todos os comandos importantes são executáveis por Docker
- Evita dependência de ambiente local para outros desenvolvedores
- Banco SQLite pode ser substituído por MySQL/PostgreSQL para desafio adicional

---

## Conclusão

Este projeto é uma API completa, robusta e documentada para gestão de produtos, preparada para produção com testes, Docker, migrations e boas práticas de desenvolvimento. Todas as demandas do cliente foram analisadas, executadas e documentadas conforme esperado.
