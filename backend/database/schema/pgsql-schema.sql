--
-- PostgreSQL database dump
--

\restrict xEhrNMIqQNksQuEt1JHHiEfm7o2JXcp5EoqWJrczvgURv7lDp93JYmEC2RXe467

-- Dumped from database version 15.15 (Homebrew)
-- Dumped by pg_dump version 15.15 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id uuid NOT NULL,
    empresa_id uuid,
    usuario_id uuid,
    accion character varying(50) NOT NULL,
    tabla_afectada character varying(50),
    registro_id uuid,
    datos_anteriores jsonb,
    datos_nuevos jsonb,
    ip character varying(45) NOT NULL,
    created_at timestamp(0) without time zone NOT NULL,
    superadmin_id uuid
);

ALTER TABLE ONLY public.audit_logs FORCE ROW LEVEL SECURITY;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categorias; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categorias (
    id uuid NOT NULL,
    empresa_id uuid NOT NULL,
    nombre character varying(120) NOT NULL,
    descripcion text,
    categoria_padre_id uuid,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: descuentos_tenant; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.descuentos_tenant (
    id uuid NOT NULL,
    empresa_id uuid NOT NULL,
    superadmin_id uuid NOT NULL,
    tipo character varying(15) NOT NULL,
    valor numeric(8,2) NOT NULL,
    motivo character varying(255) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: empresas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.empresas (
    id uuid NOT NULL,
    ruc character varying(11) NOT NULL,
    razon_social character varying(200) NOT NULL,
    nombre_comercial character varying(200),
    direccion text,
    ubigeo character varying(6),
    logo_url character varying(500),
    regimen_tributario character varying(3) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

ALTER TABLE ONLY public.empresas FORCE ROW LEVEL SECURITY;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: impersonation_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.impersonation_logs (
    id uuid NOT NULL,
    superadmin_id uuid NOT NULL,
    empresa_id uuid NOT NULL,
    token_hash character varying(64) NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    ended_at timestamp(0) without time zone,
    ip character varying(45) NOT NULL
);


--
-- Name: invitaciones_usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invitaciones_usuario (
    id uuid NOT NULL,
    empresa_id uuid NOT NULL,
    email character varying(255) NOT NULL,
    rol character varying(10) NOT NULL,
    token character varying(100) NOT NULL,
    invitado_por uuid NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone NOT NULL
);

ALTER TABLE ONLY public.invitaciones_usuario FORCE ROW LEVEL SECURITY;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    id uuid NOT NULL,
    email character varying(255) NOT NULL,
    token character varying(100) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone NOT NULL
);


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id uuid NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: planes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.planes (
    id uuid NOT NULL,
    nombre character varying(20) NOT NULL,
    nombre_display character varying(50) NOT NULL,
    precio_mensual numeric(8,2) NOT NULL,
    max_usuarios integer,
    modulos jsonb NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: precio_historial; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.precio_historial (
    id uuid NOT NULL,
    producto_id uuid NOT NULL,
    precio_anterior numeric(12,4) NOT NULL,
    precio_nuevo numeric(12,4) NOT NULL,
    usuario_id uuid,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: producto_componentes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_componentes (
    id uuid NOT NULL,
    producto_id uuid NOT NULL,
    componente_id uuid NOT NULL,
    cantidad numeric(12,4) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_cantidad CHECK ((cantidad > (0)::numeric)),
    CONSTRAINT chk_no_self_ref CHECK ((producto_id <> componente_id))
);


--
-- Name: producto_imagenes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_imagenes (
    id uuid NOT NULL,
    producto_id uuid NOT NULL,
    url text NOT NULL,
    path_r2 text,
    orden smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: producto_precios_lista; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_precios_lista (
    id uuid NOT NULL,
    producto_id uuid NOT NULL,
    lista character varying(255) NOT NULL,
    nombre_lista character varying(60) DEFAULT 'Lista'::character varying NOT NULL,
    precio numeric(12,4) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_precio_lista CHECK ((precio > (0)::numeric)),
    CONSTRAINT producto_precios_lista_lista_check CHECK (((lista)::text = ANY ((ARRAY['L1'::character varying, 'L2'::character varying, 'L3'::character varying])::text[])))
);


--
-- Name: producto_promociones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_promociones (
    id uuid NOT NULL,
    producto_id uuid NOT NULL,
    nombre character varying(120) NOT NULL,
    tipo character varying(255) NOT NULL,
    valor numeric(12,4) NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_promo_valor CHECK ((valor > (0)::numeric)),
    CONSTRAINT producto_promociones_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['porcentaje'::character varying, 'monto_fijo'::character varying])::text[])))
);


--
-- Name: producto_unidades; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_unidades (
    id uuid NOT NULL,
    producto_id uuid NOT NULL,
    unidad_medida character varying(20) NOT NULL,
    factor_conversion numeric(12,6) NOT NULL,
    precio_venta numeric(12,4),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_factor_conversion CHECK ((factor_conversion > (0)::numeric))
);


--
-- Name: productos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.productos (
    id uuid NOT NULL,
    empresa_id uuid NOT NULL,
    categoria_id uuid NOT NULL,
    nombre character varying(255) NOT NULL,
    descripcion text,
    sku character varying(100) NOT NULL,
    codigo_barras character varying(50),
    tipo character varying(255) DEFAULT 'simple'::character varying NOT NULL,
    unidad_medida_principal character varying(20) NOT NULL,
    precio_compra numeric(12,4),
    precio_venta numeric(12,4) NOT NULL,
    igv_tipo character varying(255) DEFAULT 'gravado'::character varying NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_precio_venta CHECK ((precio_venta > (0)::numeric)),
    CONSTRAINT productos_igv_tipo_check CHECK (((igv_tipo)::text = ANY ((ARRAY['gravado'::character varying, 'exonerado'::character varying, 'inafecto'::character varying])::text[]))),
    CONSTRAINT productos_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['simple'::character varying, 'compuesto'::character varying, 'servicio'::character varying])::text[])))
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id character varying(36),
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: superadmins; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.superadmins (
    id uuid NOT NULL,
    nombre character varying(150) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    last_login timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: suscripciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.suscripciones (
    id uuid NOT NULL,
    empresa_id uuid NOT NULL,
    plan_id uuid NOT NULL,
    downgrade_plan_id uuid,
    estado character varying(10) NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_vencimiento date NOT NULL,
    fecha_proximo_cobro date,
    fecha_cancelacion date,
    culqi_subscription_id character varying(100),
    culqi_customer_id character varying(100),
    culqi_card_id character varying(100),
    card_last4 character varying(4),
    card_brand character varying(20),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

ALTER TABLE ONLY public.suscripciones FORCE ROW LEVEL SECURITY;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuarios (
    id uuid NOT NULL,
    empresa_id uuid NOT NULL,
    nombre character varying(150) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    rol character varying(10) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    last_login timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

ALTER TABLE ONLY public.usuarios FORCE ROW LEVEL SECURITY;


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id);


--
-- Name: descuentos_tenant descuentos_tenant_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.descuentos_tenant
    ADD CONSTRAINT descuentos_tenant_pkey PRIMARY KEY (id);


--
-- Name: empresas empresas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.empresas
    ADD CONSTRAINT empresas_pkey PRIMARY KEY (id);


--
-- Name: empresas empresas_ruc_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.empresas
    ADD CONSTRAINT empresas_ruc_unique UNIQUE (ruc);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: impersonation_logs impersonation_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.impersonation_logs
    ADD CONSTRAINT impersonation_logs_pkey PRIMARY KEY (id);


--
-- Name: invitaciones_usuario invitaciones_usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitaciones_usuario
    ADD CONSTRAINT invitaciones_usuario_pkey PRIMARY KEY (id);


--
-- Name: invitaciones_usuario invitaciones_usuario_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitaciones_usuario
    ADD CONSTRAINT invitaciones_usuario_token_unique UNIQUE (token);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_token_unique UNIQUE (token);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: planes planes_nombre_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planes
    ADD CONSTRAINT planes_nombre_unique UNIQUE (nombre);


--
-- Name: planes planes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planes
    ADD CONSTRAINT planes_pkey PRIMARY KEY (id);


--
-- Name: precio_historial precio_historial_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.precio_historial
    ADD CONSTRAINT precio_historial_pkey PRIMARY KEY (id);


--
-- Name: producto_componentes producto_componentes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_componentes
    ADD CONSTRAINT producto_componentes_pkey PRIMARY KEY (id);


--
-- Name: producto_imagenes producto_imagenes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_imagenes
    ADD CONSTRAINT producto_imagenes_pkey PRIMARY KEY (id);


--
-- Name: producto_precios_lista producto_precios_lista_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_precios_lista
    ADD CONSTRAINT producto_precios_lista_pkey PRIMARY KEY (id);


--
-- Name: producto_promociones producto_promociones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_promociones
    ADD CONSTRAINT producto_promociones_pkey PRIMARY KEY (id);


--
-- Name: producto_unidades producto_unidades_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_unidades
    ADD CONSTRAINT producto_unidades_pkey PRIMARY KEY (id);


--
-- Name: productos productos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: superadmins superadmins_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.superadmins
    ADD CONSTRAINT superadmins_email_unique UNIQUE (email);


--
-- Name: superadmins superadmins_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.superadmins
    ADD CONSTRAINT superadmins_pkey PRIMARY KEY (id);


--
-- Name: suscripciones suscripciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suscripciones
    ADD CONSTRAINT suscripciones_pkey PRIMARY KEY (id);


--
-- Name: categorias unique_categoria_nombre_empresa; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT unique_categoria_nombre_empresa UNIQUE (empresa_id, nombre, categoria_padre_id);


--
-- Name: producto_componentes unique_componente; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_componentes
    ADD CONSTRAINT unique_componente UNIQUE (producto_id, componente_id);


--
-- Name: producto_precios_lista unique_precio_lista; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_precios_lista
    ADD CONSTRAINT unique_precio_lista UNIQUE (producto_id, lista);


--
-- Name: productos unique_sku_empresa; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT unique_sku_empresa UNIQUE (empresa_id, sku);


--
-- Name: producto_unidades unique_unidad_producto; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_unidades
    ADD CONSTRAINT unique_unidad_producto UNIQUE (producto_id, unidad_medida);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_email_unique UNIQUE (email);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- Name: audit_logs_accion_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_accion_index ON public.audit_logs USING btree (accion);


--
-- Name: audit_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_created_at_index ON public.audit_logs USING btree (created_at);


--
-- Name: audit_logs_empresa_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_empresa_id_index ON public.audit_logs USING btree (empresa_id);


--
-- Name: audit_logs_superadmin_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_superadmin_id_index ON public.audit_logs USING btree (superadmin_id);


--
-- Name: audit_logs_usuario_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_usuario_id_index ON public.audit_logs USING btree (usuario_id);


--
-- Name: descuentos_tenant_empresa_id_activo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX descuentos_tenant_empresa_id_activo_index ON public.descuentos_tenant USING btree (empresa_id, activo);


--
-- Name: descuentos_tenant_superadmin_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX descuentos_tenant_superadmin_id_index ON public.descuentos_tenant USING btree (superadmin_id);


--
-- Name: empresas_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX empresas_created_at_index ON public.empresas USING btree (created_at);


--
-- Name: idx_categorias_empresa; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_categorias_empresa ON public.categorias USING btree (empresa_id);


--
-- Name: idx_categorias_padre; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_categorias_padre ON public.categorias USING btree (categoria_padre_id);


--
-- Name: idx_historial_empresa; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_historial_empresa ON public.precio_historial USING btree (producto_id);


--
-- Name: idx_historial_producto; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_historial_producto ON public.precio_historial USING btree (producto_id, created_at);


--
-- Name: idx_imagenes_producto; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_imagenes_producto ON public.producto_imagenes USING btree (producto_id);


--
-- Name: idx_productos_activo; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_productos_activo ON public.productos USING btree (empresa_id, activo);


--
-- Name: idx_productos_categoria; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_productos_categoria ON public.productos USING btree (categoria_id);


--
-- Name: idx_productos_codigo_barras; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_productos_codigo_barras ON public.productos USING btree (empresa_id, codigo_barras);


--
-- Name: idx_productos_empresa; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_productos_empresa ON public.productos USING btree (empresa_id);


--
-- Name: idx_promociones_producto; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_promociones_producto ON public.producto_promociones USING btree (producto_id, activo);


--
-- Name: idx_promociones_vigencia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_promociones_vigencia ON public.producto_promociones USING btree (producto_id, fecha_inicio, fecha_fin);


--
-- Name: impersonation_logs_empresa_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX impersonation_logs_empresa_id_index ON public.impersonation_logs USING btree (empresa_id);


--
-- Name: impersonation_logs_started_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX impersonation_logs_started_at_index ON public.impersonation_logs USING btree (started_at);


--
-- Name: impersonation_logs_superadmin_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX impersonation_logs_superadmin_id_index ON public.impersonation_logs USING btree (superadmin_id);


--
-- Name: invitaciones_usuario_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invitaciones_usuario_email_index ON public.invitaciones_usuario USING btree (email);


--
-- Name: invitaciones_usuario_empresa_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invitaciones_usuario_empresa_id_index ON public.invitaciones_usuario USING btree (empresa_id);


--
-- Name: invitaciones_usuario_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invitaciones_usuario_expires_at_index ON public.invitaciones_usuario USING btree (expires_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: password_reset_tokens_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX password_reset_tokens_email_index ON public.password_reset_tokens USING btree (email);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: suscripciones_empresa_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suscripciones_empresa_id_index ON public.suscripciones USING btree (empresa_id);


--
-- Name: suscripciones_estado_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suscripciones_estado_index ON public.suscripciones USING btree (estado);


--
-- Name: suscripciones_fecha_vencimiento_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suscripciones_fecha_vencimiento_index ON public.suscripciones USING btree (fecha_vencimiento);


--
-- Name: uq_impersonation_activa; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_impersonation_activa ON public.impersonation_logs USING btree (empresa_id, superadmin_id) WHERE (ended_at IS NULL);


--
-- Name: usuarios_activo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX usuarios_activo_index ON public.usuarios USING btree (activo);


--
-- Name: usuarios_empresa_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX usuarios_empresa_id_index ON public.usuarios USING btree (empresa_id);


--
-- Name: audit_logs audit_logs_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id);


--
-- Name: audit_logs audit_logs_superadmin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_superadmin_id_fkey FOREIGN KEY (superadmin_id) REFERENCES public.superadmins(id);


--
-- Name: audit_logs audit_logs_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- Name: categorias categorias_categoria_padre_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_categoria_padre_id_foreign FOREIGN KEY (categoria_padre_id) REFERENCES public.categorias(id) ON DELETE SET NULL;


--
-- Name: categorias categorias_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id) ON DELETE CASCADE;


--
-- Name: descuentos_tenant descuentos_tenant_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.descuentos_tenant
    ADD CONSTRAINT descuentos_tenant_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id);


--
-- Name: descuentos_tenant descuentos_tenant_superadmin_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.descuentos_tenant
    ADD CONSTRAINT descuentos_tenant_superadmin_id_foreign FOREIGN KEY (superadmin_id) REFERENCES public.superadmins(id);


--
-- Name: impersonation_logs impersonation_logs_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.impersonation_logs
    ADD CONSTRAINT impersonation_logs_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id);


--
-- Name: impersonation_logs impersonation_logs_superadmin_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.impersonation_logs
    ADD CONSTRAINT impersonation_logs_superadmin_id_foreign FOREIGN KEY (superadmin_id) REFERENCES public.superadmins(id);


--
-- Name: invitaciones_usuario invitaciones_usuario_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitaciones_usuario
    ADD CONSTRAINT invitaciones_usuario_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id);


--
-- Name: invitaciones_usuario invitaciones_usuario_invitado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitaciones_usuario
    ADD CONSTRAINT invitaciones_usuario_invitado_por_foreign FOREIGN KEY (invitado_por) REFERENCES public.usuarios(id);


--
-- Name: precio_historial precio_historial_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.precio_historial
    ADD CONSTRAINT precio_historial_producto_id_foreign FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: precio_historial precio_historial_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.precio_historial
    ADD CONSTRAINT precio_historial_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: producto_componentes producto_componentes_componente_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_componentes
    ADD CONSTRAINT producto_componentes_componente_id_foreign FOREIGN KEY (componente_id) REFERENCES public.productos(id);


--
-- Name: producto_componentes producto_componentes_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_componentes
    ADD CONSTRAINT producto_componentes_producto_id_foreign FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: producto_imagenes producto_imagenes_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_imagenes
    ADD CONSTRAINT producto_imagenes_producto_id_foreign FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: producto_precios_lista producto_precios_lista_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_precios_lista
    ADD CONSTRAINT producto_precios_lista_producto_id_foreign FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: producto_promociones producto_promociones_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_promociones
    ADD CONSTRAINT producto_promociones_producto_id_foreign FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: producto_unidades producto_unidades_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_unidades
    ADD CONSTRAINT producto_unidades_producto_id_foreign FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: productos productos_categoria_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_categoria_id_foreign FOREIGN KEY (categoria_id) REFERENCES public.categorias(id);


--
-- Name: productos productos_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id) ON DELETE CASCADE;


--
-- Name: suscripciones suscripciones_downgrade_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suscripciones
    ADD CONSTRAINT suscripciones_downgrade_plan_id_foreign FOREIGN KEY (downgrade_plan_id) REFERENCES public.planes(id);


--
-- Name: suscripciones suscripciones_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suscripciones
    ADD CONSTRAINT suscripciones_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id);


--
-- Name: suscripciones suscripciones_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suscripciones
    ADD CONSTRAINT suscripciones_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES public.planes(id);


--
-- Name: usuarios usuarios_empresa_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_empresa_id_foreign FOREIGN KEY (empresa_id) REFERENCES public.empresas(id);


--
-- Name: audit_logs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.audit_logs ENABLE ROW LEVEL SECURITY;

--
-- Name: categorias; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.categorias ENABLE ROW LEVEL SECURITY;

--
-- Name: empresas; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.empresas ENABLE ROW LEVEL SECURITY;

--
-- Name: invitaciones_usuario; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.invitaciones_usuario ENABLE ROW LEVEL SECURITY;

--
-- Name: productos; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.productos ENABLE ROW LEVEL SECURITY;

--
-- Name: suscripciones; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.suscripciones ENABLE ROW LEVEL SECURITY;

--
-- Name: audit_logs tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY tenant_isolation ON public.audit_logs USING (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE ((empresa_id IS NULL) OR (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid))
END) WITH CHECK (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE ((empresa_id IS NULL) OR (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid))
END);


--
-- Name: categorias tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY tenant_isolation ON public.categorias USING (((empresa_id)::text = current_setting('app.empresa_id'::text, true)));


--
-- Name: empresas tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY tenant_isolation ON public.empresas USING (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE (id = (current_setting('app.empresa_id'::text, true))::uuid)
END) WITH CHECK (true);


--
-- Name: invitaciones_usuario tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY tenant_isolation ON public.invitaciones_usuario USING (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid)
END) WITH CHECK (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid)
END);


--
-- Name: productos tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY tenant_isolation ON public.productos USING (((empresa_id)::text = current_setting('app.empresa_id'::text, true)));


--
-- Name: suscripciones tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY tenant_isolation ON public.suscripciones USING (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid)
END) WITH CHECK (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid)
END);


--
-- Name: usuarios tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY tenant_isolation ON public.usuarios USING (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid)
END) WITH CHECK (
CASE
    WHEN (COALESCE(current_setting('app.empresa_id'::text, true), ''::text) = ''::text) THEN true
    ELSE (empresa_id = (current_setting('app.empresa_id'::text, true))::uuid)
END);


--
-- Name: usuarios; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.usuarios ENABLE ROW LEVEL SECURITY;

--
-- PostgreSQL database dump complete
--

\unrestrict xEhrNMIqQNksQuEt1JHHiEfm7o2JXcp5EoqWJrczvgURv7lDp93JYmEC2RXe467

--
-- PostgreSQL database dump
--

\restrict DgH4ryNeC3FueKTDwiqRnBFpcrPlPO840u9JrnwQJZgMR9EaeW2HhtG4L5kJeDF

-- Dumped from database version 15.15 (Homebrew)
-- Dumped by pg_dump version 15.15 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_03_05_000001_create_planes_table	1
5	2026_03_05_000002_create_empresas_table	1
6	2026_03_05_000003_create_suscripciones_table	1
7	2026_03_05_000004_create_usuarios_table	1
8	2026_03_05_000005_create_invitaciones_usuario_table	1
9	2026_03_05_000006_create_audit_logs_table	1
10	2026_03_05_000007_create_password_reset_tokens_table	1
11	2026_03_05_000008_add_rls_policies	1
12	2026_03_05_191630_create_personal_access_tokens_table	1
13	2026_03_05_223404_make_nombre_comercial_nullable_in_empresas	1
14	2026_03_06_000001_create_categorias_table	1
15	2026_03_06_000001_create_superadmins_table	1
16	2026_03_06_000002_create_impersonation_logs_table	1
17	2026_03_06_000002_create_productos_table	1
18	2026_03_06_000003_create_descuentos_tenant_table	1
19	2026_03_06_000003_create_producto_imagenes_table	1
20	2026_03_06_000004_add_superadmin_id_to_audit_logs	1
21	2026_03_06_000004_create_producto_precios_lista_table	1
22	2026_03_06_000005_create_producto_promociones_table	1
23	2026_03_06_000006_create_producto_unidades_table	1
24	2026_03_06_000007_create_producto_componentes_table	1
25	2026_03_06_000008_create_precio_historial_table	1
26	2026_03_06_000009_add_rls_policies_productos	1
27	2026_03_06_030453_make_empresa_id_nullable_in_audit_logs	1
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 27, true);


--
-- PostgreSQL database dump complete
--

\unrestrict DgH4ryNeC3FueKTDwiqRnBFpcrPlPO840u9JrnwQJZgMR9EaeW2HhtG4L5kJeDF

