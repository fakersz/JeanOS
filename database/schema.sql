-- =============================================================
-- Peixoto Admin — Schema PostgreSQL
-- Assistência Técnica Apple — Peixoto Tech / Jean Peixoto
-- =============================================================

BEGIN;

-- ---------- Usuários ----------
CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(120)  NOT NULL,
    email         VARCHAR(160)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- ---------- Clientes ----------
CREATE TABLE IF NOT EXISTS clients (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(160) NOT NULL,
    phone      VARCHAR(30),
    email      VARCHAR(160),
    document   VARCHAR(30),
    address    TEXT,
    notes      TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_clients_name ON clients (LOWER(name));

-- ---------- Equipamentos ----------
CREATE TABLE IF NOT EXISTS devices (
    id              SERIAL PRIMARY KEY,
    client_id       INTEGER NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    category        VARCHAR(40)  NOT NULL,           -- MacBook, iMac, Mac mini, iPad, iPhone, Apple Watch, Outro
    brand           VARCHAR(60)  NOT NULL DEFAULT 'Apple',
    model           VARCHAR(120),                    -- ex: MacBook Pro 13" 2015
    model_code      VARCHAR(20),                     -- ex: A1502, A1466, A1708, A1989
    serial_number   VARCHAR(60),
    year            SMALLINT,
    processor       VARCHAR(80),
    memory          VARCHAR(40),
    storage         VARCHAR(40),
    condition_notes TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_devices_client     ON devices (client_id);
CREATE INDEX IF NOT EXISTS idx_devices_model_code ON devices (UPPER(model_code));

-- ---------- Ordens de Serviço ----------
CREATE TABLE IF NOT EXISTS service_orders (
    id             SERIAL PRIMARY KEY,
    client_id      INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    device_id      INTEGER NOT NULL REFERENCES devices(id) ON DELETE RESTRICT,
    title          VARCHAR(160) NOT NULL,
    reported_issue TEXT,
    diagnosis      TEXT,
    solution       TEXT,
    status         VARCHAR(30) NOT NULL DEFAULT 'aberta'
                   CHECK (status IN ('aberta','diagnostico','aguardando_aprovacao',
                                     'aguardando_peca','em_reparo','pronto','entregue','cancelada')),
    priority       VARCHAR(10) NOT NULL DEFAULT 'normal'
                   CHECK (priority IN ('baixa','normal','alta','urgente')),
    labor_price    NUMERIC(10,2) NOT NULL DEFAULT 0,
    parts_price    NUMERIC(10,2) NOT NULL DEFAULT 0,
    total_price    NUMERIC(10,2) NOT NULL DEFAULT 0,
    paid_amount    NUMERIC(10,2) NOT NULL DEFAULT 0,
    payment_status VARCHAR(10) NOT NULL DEFAULT 'pendente'
                   CHECK (payment_status IN ('pendente','parcial','pago')),
    opened_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    closed_at      TIMESTAMPTZ,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_os_status ON service_orders (status);
CREATE INDEX IF NOT EXISTS idx_os_client ON service_orders (client_id);
CREATE INDEX IF NOT EXISTS idx_os_device ON service_orders (device_id);

-- ---------- Estoque ----------
CREATE TABLE IF NOT EXISTS inventory (
    id                SERIAL PRIMARY KEY,
    name              VARCHAR(160) NOT NULL,
    category          VARCHAR(60),                    -- Bateria, Tela, SSD, Flex, Parafuso, Consumível...
    compatible_models VARCHAR(255),                   -- ex: A1502, A1466
    quantity          INTEGER NOT NULL DEFAULT 0,
    min_quantity      INTEGER NOT NULL DEFAULT 1,
    cost_price        NUMERIC(10,2) NOT NULL DEFAULT 0,
    sale_price        NUMERIC(10,2) NOT NULL DEFAULT 0,
    supplier          VARCHAR(160),
    notes             TEXT,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_inventory_name ON inventory (LOWER(name));

-- ---------- Financeiro ----------
CREATE TABLE IF NOT EXISTS financial_entries (
    id             SERIAL PRIMARY KEY,
    type           VARCHAR(10) NOT NULL CHECK (type IN ('entrada','saida')),
    description    VARCHAR(255) NOT NULL,
    amount         NUMERIC(10,2) NOT NULL CHECK (amount >= 0),
    category       VARCHAR(60),                       -- Serviço, Peça, Aluguel, Ferramenta...
    reference_type VARCHAR(30),                       -- ex: service_order
    reference_id   INTEGER,
    entry_date     DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_fin_date ON financial_entries (entry_date);
CREATE INDEX IF NOT EXISTS idx_fin_ref  ON financial_entries (reference_type, reference_id);

-- ---------- Esquemas Elétricos ----------
CREATE TABLE IF NOT EXISTS schematics (
    id              SERIAL PRIMARY KEY,
    title           VARCHAR(160) NOT NULL,
    device_category VARCHAR(40),
    model_code      VARCHAR(20),                      -- ex: A1502
    board_code      VARCHAR(30),                      -- ex: 820-3536
    year            SMALLINT,
    file_path       VARCHAR(255),                     -- caminho relativo em storage/uploads
    external_link   VARCHAR(500),
    notes           TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_schematics_model ON schematics (UPPER(model_code));

-- ---------- Trello (quadro kanban) ----------
CREATE TABLE IF NOT EXISTS trello_columns (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(80) NOT NULL,
    position   INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS trello_cards (
    id         SERIAL PRIMARY KEY,
    column_id  INTEGER NOT NULL REFERENCES trello_columns(id) ON DELETE CASCADE,
    title      VARCHAR(200) NOT NULL,
    body       TEXT,                                 -- conteúdo em markdown
    tags       VARCHAR(255),                         -- etiquetas separadas por vírgula
    position   INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_trello_cards_col ON trello_cards (column_id, position);

CREATE TABLE IF NOT EXISTS trello_comments (
    id         SERIAL PRIMARY KEY,
    card_id    INTEGER NOT NULL REFERENCES trello_cards(id) ON DELETE CASCADE,
    body       TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_trello_comments_card ON trello_comments (card_id);

INSERT INTO trello_columns (name, position)
SELECT * FROM (VALUES ('A Fazer', 0), ('Em Andamento', 1), ('Concluído', 2)) AS v(name, position)
WHERE NOT EXISTS (SELECT 1 FROM trello_columns);

-- ---------- LifeOS / Second Brain ----------
CREATE TABLE IF NOT EXISTS life_nodes (
    id            SERIAL PRIMARY KEY,
    type          VARCHAR(40)  NOT NULL CHECK (type IN (
        'note', 'knowledge', 'idea', 'diary', 'document',
        'project', 'task', 'agenda_event', 'vehicle'
    )),
    title         VARCHAR(220) NOT NULL,
    summary       TEXT,
    body          TEXT,
    status        VARCHAR(40),
    priority      VARCHAR(20),
    category      VARCHAR(120),
    tags          VARCHAR(500),
    favorite      BOOLEAN NOT NULL DEFAULT FALSE,
    source_url    VARCHAR(600),
    file_path     VARCHAR(255),
    mime_type     VARCHAR(120),
    file_size     BIGINT,
    starts_at     TIMESTAMPTZ,
    due_at        TIMESTAMPTZ,
    completed_at  TIMESTAMPTZ,
    time_minutes  INTEGER NOT NULL DEFAULT 0,
    position      INTEGER NOT NULL DEFAULT 0,
    metadata      JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_life_nodes_type_status ON life_nodes (type, status);
CREATE INDEX IF NOT EXISTS idx_life_nodes_due         ON life_nodes (due_at);
CREATE INDEX IF NOT EXISTS idx_life_nodes_starts      ON life_nodes (starts_at);
CREATE INDEX IF NOT EXISTS idx_life_nodes_updated     ON life_nodes (updated_at DESC);
CREATE INDEX IF NOT EXISTS idx_life_nodes_search ON life_nodes USING GIN (
    to_tsvector('simple',
        COALESCE(title, '') || ' ' ||
        COALESCE(summary, '') || ' ' ||
        COALESCE(body, '') || ' ' ||
        COALESCE(category, '') || ' ' ||
        COALESCE(tags, '')
    )
);

CREATE TABLE IF NOT EXISTS life_comments (
    id         SERIAL PRIMARY KEY,
    node_id    INTEGER NOT NULL REFERENCES life_nodes(id) ON DELETE CASCADE,
    body       TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_life_comments_node ON life_comments (node_id, created_at DESC);

CREATE TABLE IF NOT EXISTS entity_links (
    id            SERIAL PRIMARY KEY,
    source_type   VARCHAR(60) NOT NULL,
    source_id     INTEGER NOT NULL,
    target_type   VARCHAR(60) NOT NULL,
    target_id     INTEGER NOT NULL,
    relation_type VARCHAR(60) NOT NULL DEFAULT 'relacionado',
    description   TEXT,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (source_type, source_id, target_type, target_id, relation_type)
);
CREATE INDEX IF NOT EXISTS idx_entity_links_source ON entity_links (source_type, source_id);
CREATE INDEX IF NOT EXISTS idx_entity_links_target ON entity_links (target_type, target_id);

CREATE TABLE IF NOT EXISTS life_events (
    id            SERIAL PRIMARY KEY,
    actor_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    event_type    VARCHAR(60) NOT NULL,
    entity_type   VARCHAR(60) NOT NULL,
    entity_id     INTEGER,
    title         VARCHAR(255) NOT NULL,
    body          TEXT,
    metadata      JSONB NOT NULL DEFAULT '{}'::jsonb,
    occurred_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_life_events_time   ON life_events (occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_life_events_entity ON life_events (entity_type, entity_id);

CREATE TABLE IF NOT EXISTS ai_context_sources (
    id           SERIAL PRIMARY KEY,
    entity_type  VARCHAR(60) NOT NULL,
    entity_id    INTEGER NOT NULL,
    title        VARCHAR(255) NOT NULL,
    content      TEXT NOT NULL,
    metadata     JSONB NOT NULL DEFAULT '{}'::jsonb,
    content_hash VARCHAR(64) NOT NULL,
    indexed_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (entity_type, entity_id)
);
CREATE INDEX IF NOT EXISTS idx_ai_context_sources_entity ON ai_context_sources (entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_ai_context_sources_search ON ai_context_sources USING GIN (
    to_tsvector('simple', COALESCE(title, '') || ' ' || COALESCE(content, ''))
);

COMMIT;
