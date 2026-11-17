-- =============================================================
-- Migration 002 — LifeOS foundation
-- Cria a camada de Second Brain: nós, relações, timeline e índice de IA.
-- =============================================================

BEGIN;

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
    id          SERIAL PRIMARY KEY,
    entity_type VARCHAR(60) NOT NULL,
    entity_id   INTEGER NOT NULL,
    title       VARCHAR(255) NOT NULL,
    content     TEXT NOT NULL,
    metadata    JSONB NOT NULL DEFAULT '{}'::jsonb,
    content_hash VARCHAR(64) NOT NULL,
    indexed_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (entity_type, entity_id)
);
CREATE INDEX IF NOT EXISTS idx_ai_context_sources_entity ON ai_context_sources (entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_ai_context_sources_search ON ai_context_sources USING GIN (
    to_tsvector('simple', COALESCE(title, '') || ' ' || COALESCE(content, ''))
);

COMMIT;
