--
-- PostgreSQL database dump
--

\restrict lTYv7b0BlFnYFw9PxofiZfVyuHBTiYaCIOO64YawBzldMIJPhLgJF9hPGChFrBQ

-- Dumped from database version 16.14
-- Dumped by pg_dump version 16.14

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid
);


ALTER TABLE public.activity_log OWNER TO ieepis_user;

--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.activity_log_id_seq OWNER TO ieepis_user;

--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: approved_users; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.approved_users (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    name character varying(255),
    role character varying(255),
    division character varying(255),
    division_id bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by bigint,
    actioned_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT approved_users_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.approved_users OWNER TO ieepis_user;

--
-- Name: approved_users_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.approved_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.approved_users_id_seq OWNER TO ieepis_user;

--
-- Name: approved_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.approved_users_id_seq OWNED BY public.approved_users.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO ieepis_user;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO ieepis_user;

--
-- Name: districts; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.districts (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    division character varying(255),
    region character varying(255),
    code character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    division_id bigint
);


ALTER TABLE public.districts OWNER TO ieepis_user;

--
-- Name: districts_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.districts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.districts_id_seq OWNER TO ieepis_user;

--
-- Name: districts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.districts_id_seq OWNED BY public.districts.id;


--
-- Name: divisions; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.divisions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    region character varying(255),
    code character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.divisions OWNER TO ieepis_user;

--
-- Name: divisions_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.divisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.divisions_id_seq OWNER TO ieepis_user;

--
-- Name: divisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.divisions_id_seq OWNED BY public.divisions.id;


--
-- Name: documents; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.documents (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    equipment_id bigint,
    employee_id bigint,
    document_type character varying(255) NOT NULL,
    document_no character varying(255),
    title character varying(255) NOT NULL,
    description text,
    file_path character varying(255) NOT NULL,
    file_name character varying(255),
    file_size bigint,
    mime_type character varying(255),
    uploaded_by_id bigint,
    document_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.documents OWNER TO ieepis_user;

--
-- Name: documents_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.documents_id_seq OWNER TO ieepis_user;

--
-- Name: documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.documents_id_seq OWNED BY public.documents.id;


--
-- Name: employees; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.employees (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    employee_number character varying(255) NOT NULL,
    first_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    middle_name character varying(255),
    suffix character varying(255),
    full_name character varying(255),
    "position" character varying(255) NOT NULL,
    department character varying(255),
    ro_office character varying(255),
    sdo_office character varying(255),
    employment_type character varying(255) DEFAULT 'teaching'::character varying NOT NULL,
    email character varying(255),
    personal_email character varying(255),
    mobile_1 character varying(255),
    mobile_2 character varying(255),
    date_hired date,
    is_oic boolean DEFAULT false NOT NULL,
    oic_office character varying(255),
    is_non_deped_funded boolean DEFAULT false NOT NULL,
    source_of_funds character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    date_of_separation date,
    cause_of_separation character varying(255),
    detailed_from character varying(255),
    detailed_to character varying(255),
    photo character varying(255),
    is_inactive boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT employees_employment_type_check CHECK (((employment_type)::text = ANY ((ARRAY['teaching'::character varying, 'non-teaching'::character varying])::text[]))),
    CONSTRAINT employees_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'retired'::character varying])::text[])))
);


ALTER TABLE public.employees OWNER TO ieepis_user;

--
-- Name: employees_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.employees_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.employees_id_seq OWNER TO ieepis_user;

--
-- Name: employees_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.employees_id_seq OWNED BY public.employees.id;


--
-- Name: equipment; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.equipment (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    property_no character varying(255) NOT NULL,
    old_property_no character varying(255),
    serial_number character varying(255),
    item_type character varying(255),
    equipment_type character varying(255),
    brand character varying(255),
    model character varying(255),
    specifications text,
    unit_of_measure character varying(255) DEFAULT 'Unit'::character varying NOT NULL,
    category character varying(255) DEFAULT 'High-Value'::character varying NOT NULL,
    classification character varying(255) DEFAULT 'Machinery and Equipment for ICT'::character varying NOT NULL,
    gl_sl_code character varying(255),
    uacs_code character varying(255),
    is_dcp boolean DEFAULT false NOT NULL,
    dcp_package character varying(255),
    dcp_year integer,
    is_non_dcp boolean DEFAULT false NOT NULL,
    acquisition_cost numeric(12,2),
    acquisition_date date,
    received_date date,
    estimated_useful_life smallint,
    mode_of_acquisition character varying(255),
    source_of_acquisition character varying(255),
    donor character varying(255),
    source_of_funds character varying(255),
    pmp_reference_no character varying(255),
    supporting_doc_type_acquisition character varying(255),
    supporting_doc_no_acquisition character varying(255),
    supplier character varying(255),
    under_warranty boolean DEFAULT false NOT NULL,
    warranty_end_date date,
    equipment_location character varying(255),
    is_functional boolean DEFAULT true NOT NULL,
    condition character varying(255) DEFAULT 'Good'::character varying NOT NULL,
    accountability_status character varying(255) DEFAULT 'unassigned'::character varying NOT NULL,
    disposition_status character varying(255),
    remarks text,
    qr_code text,
    transaction_type character varying(255),
    supporting_doc_type_issuance character varying(255),
    supporting_doc_no_issuance character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT equipment_category_check CHECK (((category)::text = ANY ((ARRAY['High-Value'::character varying, 'Low-Value'::character varying])::text[]))),
    CONSTRAINT equipment_condition_check CHECK (((condition)::text = ANY ((ARRAY['Good'::character varying, 'Fair'::character varying, 'Poor'::character varying, 'Unserviceable'::character varying])::text[])))
);


ALTER TABLE public.equipment OWNER TO ieepis_user;

--
-- Name: equipment_assignments; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.equipment_assignments (
    id bigint NOT NULL,
    equipment_id bigint NOT NULL,
    employee_id bigint NOT NULL,
    new_accountable_id bigint,
    custodian_id bigint,
    assigned_at date NOT NULL,
    custodian_received_at date,
    returned_at date,
    new_accountable_received_at date,
    assigned_by character varying(255),
    transaction_type character varying(255) DEFAULT 'Issuance'::character varying NOT NULL,
    supporting_doc_type character varying(255),
    supporting_doc_no character varying(255),
    notes text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    school_id bigint NOT NULL
);


ALTER TABLE public.equipment_assignments OWNER TO ieepis_user;

--
-- Name: equipment_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.equipment_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.equipment_assignments_id_seq OWNER TO ieepis_user;

--
-- Name: equipment_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.equipment_assignments_id_seq OWNED BY public.equipment_assignments.id;


--
-- Name: equipment_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.equipment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.equipment_id_seq OWNER TO ieepis_user;

--
-- Name: equipment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.equipment_id_seq OWNED BY public.equipment.id;


--
-- Name: internet_connections; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.internet_connections (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    isp character varying(255) NOT NULL,
    account_number character varying(255),
    plan_name character varying(255),
    contracted_download_speed numeric(8,2),
    contracted_upload_speed numeric(8,2),
    actual_download_speed numeric(8,2),
    actual_upload_speed numeric(8,2),
    latency_ms smallint,
    speed_test_date date,
    ip_address character varying(255),
    connection_type character varying(255) DEFAULT 'Fiber'::character varying NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    monthly_cost numeric(10,2),
    subscription_start date,
    subscription_end date,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT internet_connections_connection_type_check CHECK (((connection_type)::text = ANY ((ARRAY['Fiber'::character varying, 'DSL'::character varying, 'Wireless'::character varying, 'LTE'::character varying, 'Satellite'::character varying, 'Others'::character varying])::text[]))),
    CONSTRAINT internet_connections_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'suspended'::character varying])::text[])))
);


ALTER TABLE public.internet_connections OWNER TO ieepis_user;

--
-- Name: internet_connections_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.internet_connections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.internet_connections_id_seq OWNER TO ieepis_user;

--
-- Name: internet_connections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.internet_connections_id_seq OWNED BY public.internet_connections.id;


--
-- Name: maintenance_logs; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.maintenance_logs (
    id bigint NOT NULL,
    equipment_id bigint NOT NULL,
    technician_id bigint NOT NULL,
    issue_description text NOT NULL,
    action_taken text NOT NULL,
    status character varying(255) DEFAULT 'resolved'::character varying NOT NULL,
    date_performed timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT maintenance_logs_status_check CHECK (((status)::text = ANY ((ARRAY['resolved'::character varying, 'repaired'::character varying, 'replaced'::character varying])::text[])))
);


ALTER TABLE public.maintenance_logs OWNER TO ieepis_user;

--
-- Name: maintenance_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.maintenance_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.maintenance_logs_id_seq OWNER TO ieepis_user;

--
-- Name: maintenance_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.maintenance_logs_id_seq OWNED BY public.maintenance_logs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO ieepis_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO ieepis_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_permissions OWNER TO ieepis_user;

--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO ieepis_user;

--
-- Name: notifications; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO ieepis_user;

--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO ieepis_user;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO ieepis_user;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO ieepis_user;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: reassignment_audits; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.reassignment_audits (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    actor_id bigint,
    before json,
    after json,
    notes text,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.reassignment_audits OWNER TO ieepis_user;

--
-- Name: reassignment_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.reassignment_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reassignment_audits_id_seq OWNER TO ieepis_user;

--
-- Name: reassignment_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.reassignment_audits_id_seq OWNED BY public.reassignment_audits.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO ieepis_user;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO ieepis_user;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO ieepis_user;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: schools; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.schools (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    school_code character varying(255) NOT NULL,
    school_id_number character varying(255),
    governance_level character varying(255) DEFAULT 'School'::character varying NOT NULL,
    district character varying(255),
    region character varying(255),
    division character varying(255),
    province character varying(255),
    city_municipality character varying(255),
    barangay character varying(255),
    street character varying(255),
    complete_address text,
    legislative_district character varying(255),
    psgc character varying(255),
    head_name character varying(255),
    head_email character varying(255),
    head_mobile character varying(255),
    admin_staff_name character varying(255),
    admin_staff_email character varying(255),
    admin_staff_mobile character varying(255),
    email character varying(255),
    landline character varying(255),
    mobile_1 character varying(255),
    mobile_2 character varying(255),
    logo character varying(255),
    latitude numeric(10,7),
    longitude numeric(10,7),
    travel_time_minutes integer,
    is_very_remote boolean DEFAULT false NOT NULL,
    is_gidca character varying(255) DEFAULT 'None'::character varying NOT NULL,
    recent_developments text,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    network_administrator_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    district_id bigint,
    CONSTRAINT schools_governance_level_check CHECK (((governance_level)::text = ANY ((ARRAY['Central'::character varying, 'Regional'::character varying, 'SDO'::character varying, 'School'::character varying])::text[]))),
    CONSTRAINT schools_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.schools OWNER TO ieepis_user;

--
-- Name: schools_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.schools_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.schools_id_seq OWNER TO ieepis_user;

--
-- Name: schools_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.schools_id_seq OWNED BY public.schools.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO ieepis_user;

--
-- Name: tickets; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.tickets (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    equipment_id bigint,
    reporter_id bigint,
    ticket_number character varying(255) NOT NULL,
    issue_title character varying(255) NOT NULL,
    description text NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    assigned_to_id bigint,
    resolution_notes text,
    resolved_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    ticket_type character varying(255) DEFAULT 'Support'::character varying NOT NULL,
    CONSTRAINT tickets_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying, 'critical'::character varying])::text[]))),
    CONSTRAINT tickets_status_check CHECK (((status)::text = ANY ((ARRAY['open'::character varying, 'in-progress'::character varying, 'pending'::character varying, 'resolved'::character varying, 'closed'::character varying])::text[]))),
    CONSTRAINT tickets_ticket_type_check CHECK (((ticket_type)::text = ANY ((ARRAY['Support'::character varying, 'Maintenance'::character varying, 'Repair'::character varying])::text[])))
);


ALTER TABLE public.tickets OWNER TO ieepis_user;

--
-- Name: tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tickets_id_seq OWNER TO ieepis_user;

--
-- Name: tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.tickets_id_seq OWNED BY public.tickets.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: ieepis_user
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    school_id bigint,
    approval_status character varying(255) DEFAULT 'approved'::character varying NOT NULL,
    division character varying(255),
    division_id bigint,
    google_id character varying(255),
    CONSTRAINT users_approval_status_check CHECK (((approval_status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO ieepis_user;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: ieepis_user
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO ieepis_user;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ieepis_user
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: approved_users id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.approved_users ALTER COLUMN id SET DEFAULT nextval('public.approved_users_id_seq'::regclass);


--
-- Name: districts id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.districts ALTER COLUMN id SET DEFAULT nextval('public.districts_id_seq'::regclass);


--
-- Name: divisions id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.divisions ALTER COLUMN id SET DEFAULT nextval('public.divisions_id_seq'::regclass);


--
-- Name: documents id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.documents ALTER COLUMN id SET DEFAULT nextval('public.documents_id_seq'::regclass);


--
-- Name: employees id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.employees ALTER COLUMN id SET DEFAULT nextval('public.employees_id_seq'::regclass);


--
-- Name: equipment id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment ALTER COLUMN id SET DEFAULT nextval('public.equipment_id_seq'::regclass);


--
-- Name: equipment_assignments id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment_assignments ALTER COLUMN id SET DEFAULT nextval('public.equipment_assignments_id_seq'::regclass);


--
-- Name: internet_connections id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.internet_connections ALTER COLUMN id SET DEFAULT nextval('public.internet_connections_id_seq'::regclass);


--
-- Name: maintenance_logs id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.maintenance_logs ALTER COLUMN id SET DEFAULT nextval('public.maintenance_logs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: reassignment_audits id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.reassignment_audits ALTER COLUMN id SET DEFAULT nextval('public.reassignment_audits_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: schools id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.schools ALTER COLUMN id SET DEFAULT nextval('public.schools_id_seq'::regclass);


--
-- Name: tickets id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.tickets ALTER COLUMN id SET DEFAULT nextval('public.tickets_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: activity_log; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.activity_log (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, created_at, updated_at, event, batch_uuid) FROM stdin;
\.


--
-- Data for Name: approved_users; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.approved_users (id, email, name, role, division, division_id, status, approved_by, actioned_at, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: districts; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.districts (id, name, division, region, code, is_active, created_at, updated_at, division_id) FROM stdin;
\.


--
-- Data for Name: divisions; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.divisions (id, name, region, code, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: documents; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.documents (id, school_id, equipment_id, employee_id, document_type, document_no, title, description, file_path, file_name, file_size, mime_type, uploaded_by_id, document_date, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: employees; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.employees (id, school_id, employee_number, first_name, last_name, middle_name, suffix, full_name, "position", department, ro_office, sdo_office, employment_type, email, personal_email, mobile_1, mobile_2, date_hired, is_oic, oic_office, is_non_deped_funded, source_of_funds, status, date_of_separation, cause_of_separation, detailed_from, detailed_to, photo, is_inactive, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: equipment; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.equipment (id, school_id, property_no, old_property_no, serial_number, item_type, equipment_type, brand, model, specifications, unit_of_measure, category, classification, gl_sl_code, uacs_code, is_dcp, dcp_package, dcp_year, is_non_dcp, acquisition_cost, acquisition_date, received_date, estimated_useful_life, mode_of_acquisition, source_of_acquisition, donor, source_of_funds, pmp_reference_no, supporting_doc_type_acquisition, supporting_doc_no_acquisition, supplier, under_warranty, warranty_end_date, equipment_location, is_functional, condition, accountability_status, disposition_status, remarks, qr_code, transaction_type, supporting_doc_type_issuance, supporting_doc_no_issuance, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: equipment_assignments; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.equipment_assignments (id, equipment_id, employee_id, new_accountable_id, custodian_id, assigned_at, custodian_received_at, returned_at, new_accountable_received_at, assigned_by, transaction_type, supporting_doc_type, supporting_doc_no, notes, is_active, created_at, updated_at, school_id) FROM stdin;
\.


--
-- Data for Name: internet_connections; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.internet_connections (id, school_id, isp, account_number, plan_name, contracted_download_speed, contracted_upload_speed, actual_download_speed, actual_upload_speed, latency_ms, speed_test_date, ip_address, connection_type, status, monthly_cost, subscription_start, subscription_end, remarks, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: maintenance_logs; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.maintenance_logs (id, equipment_id, technician_id, issue_description, action_taken, status, date_performed, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	2024_01_01_000001_create_schools_table	1
3	2024_01_01_000002_create_employees_table	1
4	2024_01_01_000003_create_equipment_table	1
5	2024_01_01_000004_create_related_tables	1
6	2026_03_06_152030_create_activity_log_table	1
7	2026_03_06_152031_add_event_column_to_activity_log_table	1
8	2026_03_06_152032_add_batch_uuid_column_to_activity_log_table	1
9	2026_03_06_153456_create_cache_table	1
10	2026_03_06_154350_create_notifications_table	1
11	2026_03_17_145753_add_school_id_to_users_table	1
12	2026_03_18_091719_create_permission_tables	1
13	2026_03_18_095656_add_school_id_to_equipment_assignments_table	1
14	2026_03_23_000000_add_ticket_type_to_tickets_table	1
15	2026_03_24_000001_create_districts_table	1
16	2026_03_24_000002_create_approved_users_table	1
17	2026_03_24_000003_add_district_id_and_approval_to_schools_and_users	1
18	2026_03_24_000004_create_divisions_table_and_link_to_districts	1
19	2026_03_24_000005_add_google_id_to_users_table	1
20	2026_03_25_114439_create_maintenance_logs_table	1
21	2026_03_26_000001_add_foreign_key_to_division_id_in_users_table	1
22	2026_03_30_000001_create_reassignment_audits_table	1
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.model_has_permissions (permission_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.model_has_roles (role_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reassignment_audits; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.reassignment_audits (id, user_id, actor_id, before, after, notes, ip_address, user_agent, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.roles (id, name, guard_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: schools; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.schools (id, name, school_code, school_id_number, governance_level, district, region, division, province, city_municipality, barangay, street, complete_address, legislative_district, psgc, head_name, head_email, head_mobile, admin_staff_name, admin_staff_email, admin_staff_mobile, email, landline, mobile_1, mobile_2, logo, latitude, longitude, travel_time_minutes, is_very_remote, is_gidca, recent_developments, status, network_administrator_id, created_at, updated_at, deleted_at, district_id) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: tickets; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.tickets (id, school_id, equipment_id, reporter_id, ticket_number, issue_title, description, priority, status, assigned_to_id, resolution_notes, resolved_at, closed_at, created_at, updated_at, deleted_at, ticket_type) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: ieepis_user
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, school_id, approval_status, division, division_id, google_id) FROM stdin;
\.


--
-- Name: activity_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.activity_log_id_seq', 1, false);


--
-- Name: approved_users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.approved_users_id_seq', 1, false);


--
-- Name: districts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.districts_id_seq', 1, false);


--
-- Name: divisions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.divisions_id_seq', 1, false);


--
-- Name: documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.documents_id_seq', 1, false);


--
-- Name: employees_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.employees_id_seq', 1, false);


--
-- Name: equipment_assignments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.equipment_assignments_id_seq', 1, false);


--
-- Name: equipment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.equipment_id_seq', 1, false);


--
-- Name: internet_connections_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.internet_connections_id_seq', 1, false);


--
-- Name: maintenance_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.maintenance_logs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 22, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.permissions_id_seq', 1, false);


--
-- Name: reassignment_audits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.reassignment_audits_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.roles_id_seq', 1, false);


--
-- Name: schools_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.schools_id_seq', 1, false);


--
-- Name: tickets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.tickets_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ieepis_user
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: approved_users approved_users_email_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.approved_users
    ADD CONSTRAINT approved_users_email_unique UNIQUE (email);


--
-- Name: approved_users approved_users_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.approved_users
    ADD CONSTRAINT approved_users_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: districts districts_code_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.districts
    ADD CONSTRAINT districts_code_unique UNIQUE (code);


--
-- Name: districts districts_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.districts
    ADD CONSTRAINT districts_pkey PRIMARY KEY (id);


--
-- Name: divisions divisions_code_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.divisions
    ADD CONSTRAINT divisions_code_unique UNIQUE (code);


--
-- Name: divisions divisions_name_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.divisions
    ADD CONSTRAINT divisions_name_unique UNIQUE (name);


--
-- Name: divisions divisions_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.divisions
    ADD CONSTRAINT divisions_pkey PRIMARY KEY (id);


--
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_pkey PRIMARY KEY (id);


--
-- Name: employees employees_employee_number_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_employee_number_unique UNIQUE (employee_number);


--
-- Name: employees employees_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_pkey PRIMARY KEY (id);


--
-- Name: equipment_assignments equipment_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment_assignments
    ADD CONSTRAINT equipment_assignments_pkey PRIMARY KEY (id);


--
-- Name: equipment equipment_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment
    ADD CONSTRAINT equipment_pkey PRIMARY KEY (id);


--
-- Name: equipment equipment_property_no_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment
    ADD CONSTRAINT equipment_property_no_unique UNIQUE (property_no);


--
-- Name: internet_connections internet_connections_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.internet_connections
    ADD CONSTRAINT internet_connections_pkey PRIMARY KEY (id);


--
-- Name: maintenance_logs maintenance_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.maintenance_logs
    ADD CONSTRAINT maintenance_logs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: reassignment_audits reassignment_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.reassignment_audits
    ADD CONSTRAINT reassignment_audits_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: schools schools_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.schools
    ADD CONSTRAINT schools_pkey PRIMARY KEY (id);


--
-- Name: schools schools_school_code_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.schools
    ADD CONSTRAINT schools_school_code_unique UNIQUE (school_code);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_ticket_number_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_ticket_number_unique UNIQUE (ticket_number);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_google_id_unique; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_google_id_unique UNIQUE (google_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: documents_school_id_document_type_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX documents_school_id_document_type_index ON public.documents USING btree (school_id, document_type);


--
-- Name: employees_employment_type_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX employees_employment_type_index ON public.employees USING btree (employment_type);


--
-- Name: employees_school_id_status_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX employees_school_id_status_index ON public.employees USING btree (school_id, status);


--
-- Name: equipment_assignments_employee_id_returned_at_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX equipment_assignments_employee_id_returned_at_index ON public.equipment_assignments USING btree (employee_id, returned_at);


--
-- Name: equipment_assignments_equipment_id_returned_at_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX equipment_assignments_equipment_id_returned_at_index ON public.equipment_assignments USING btree (equipment_id, returned_at);


--
-- Name: equipment_equipment_type_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX equipment_equipment_type_index ON public.equipment USING btree (equipment_type);


--
-- Name: equipment_is_dcp_condition_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX equipment_is_dcp_condition_index ON public.equipment USING btree (is_dcp, condition);


--
-- Name: equipment_school_id_accountability_status_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX equipment_school_id_accountability_status_index ON public.equipment USING btree (school_id, accountability_status);


--
-- Name: internet_connections_school_id_status_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX internet_connections_school_id_status_index ON public.internet_connections USING btree (school_id, status);


--
-- Name: maintenance_logs_equipment_id_status_date_performed_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX maintenance_logs_equipment_id_status_date_performed_index ON public.maintenance_logs USING btree (equipment_id, status, date_performed);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: reassignment_audits_actor_id_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX reassignment_audits_actor_id_index ON public.reassignment_audits USING btree (actor_id);


--
-- Name: reassignment_audits_created_at_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX reassignment_audits_created_at_index ON public.reassignment_audits USING btree (created_at);


--
-- Name: reassignment_audits_user_id_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX reassignment_audits_user_id_index ON public.reassignment_audits USING btree (user_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: tickets_school_id_status_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX tickets_school_id_status_index ON public.tickets USING btree (school_id, status);


--
-- Name: tickets_status_priority_index; Type: INDEX; Schema: public; Owner: ieepis_user
--

CREATE INDEX tickets_status_priority_index ON public.tickets USING btree (status, priority);


--
-- Name: approved_users approved_users_division_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.approved_users
    ADD CONSTRAINT approved_users_division_id_foreign FOREIGN KEY (division_id) REFERENCES public.districts(id) ON DELETE SET NULL;


--
-- Name: districts districts_division_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.districts
    ADD CONSTRAINT districts_division_id_foreign FOREIGN KEY (division_id) REFERENCES public.divisions(id) ON DELETE SET NULL;


--
-- Name: documents documents_employee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE SET NULL;


--
-- Name: documents documents_equipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_equipment_id_foreign FOREIGN KEY (equipment_id) REFERENCES public.equipment(id) ON DELETE SET NULL;


--
-- Name: documents documents_school_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_school_id_foreign FOREIGN KEY (school_id) REFERENCES public.schools(id) ON DELETE CASCADE;


--
-- Name: documents documents_uploaded_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_uploaded_by_id_foreign FOREIGN KEY (uploaded_by_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: employees employees_school_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_school_id_foreign FOREIGN KEY (school_id) REFERENCES public.schools(id) ON DELETE CASCADE;


--
-- Name: equipment_assignments equipment_assignments_custodian_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment_assignments
    ADD CONSTRAINT equipment_assignments_custodian_id_foreign FOREIGN KEY (custodian_id) REFERENCES public.employees(id) ON DELETE SET NULL;


--
-- Name: equipment_assignments equipment_assignments_employee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment_assignments
    ADD CONSTRAINT equipment_assignments_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE;


--
-- Name: equipment_assignments equipment_assignments_equipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment_assignments
    ADD CONSTRAINT equipment_assignments_equipment_id_foreign FOREIGN KEY (equipment_id) REFERENCES public.equipment(id) ON DELETE CASCADE;


--
-- Name: equipment_assignments equipment_assignments_new_accountable_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment_assignments
    ADD CONSTRAINT equipment_assignments_new_accountable_id_foreign FOREIGN KEY (new_accountable_id) REFERENCES public.employees(id) ON DELETE SET NULL;


--
-- Name: equipment_assignments equipment_assignments_school_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment_assignments
    ADD CONSTRAINT equipment_assignments_school_id_foreign FOREIGN KEY (school_id) REFERENCES public.schools(id) ON DELETE CASCADE;


--
-- Name: equipment equipment_school_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.equipment
    ADD CONSTRAINT equipment_school_id_foreign FOREIGN KEY (school_id) REFERENCES public.schools(id) ON DELETE CASCADE;


--
-- Name: internet_connections internet_connections_school_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.internet_connections
    ADD CONSTRAINT internet_connections_school_id_foreign FOREIGN KEY (school_id) REFERENCES public.schools(id) ON DELETE CASCADE;


--
-- Name: maintenance_logs maintenance_logs_equipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.maintenance_logs
    ADD CONSTRAINT maintenance_logs_equipment_id_foreign FOREIGN KEY (equipment_id) REFERENCES public.equipment(id) ON DELETE CASCADE;


--
-- Name: maintenance_logs maintenance_logs_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.maintenance_logs
    ADD CONSTRAINT maintenance_logs_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: reassignment_audits reassignment_audits_actor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.reassignment_audits
    ADD CONSTRAINT reassignment_audits_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reassignment_audits reassignment_audits_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.reassignment_audits
    ADD CONSTRAINT reassignment_audits_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: schools schools_district_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.schools
    ADD CONSTRAINT schools_district_id_foreign FOREIGN KEY (district_id) REFERENCES public.districts(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_assigned_to_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_assigned_to_id_foreign FOREIGN KEY (assigned_to_id) REFERENCES public.employees(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_equipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_equipment_id_foreign FOREIGN KEY (equipment_id) REFERENCES public.equipment(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_reporter_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_reporter_id_foreign FOREIGN KEY (reporter_id) REFERENCES public.employees(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_school_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_school_id_foreign FOREIGN KEY (school_id) REFERENCES public.schools(id) ON DELETE CASCADE;


--
-- Name: users users_division_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_division_id_foreign FOREIGN KEY (division_id) REFERENCES public.divisions(id) ON DELETE SET NULL;


--
-- Name: users users_school_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ieepis_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_school_id_foreign FOREIGN KEY (school_id) REFERENCES public.schools(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict lTYv7b0BlFnYFw9PxofiZfVyuHBTiYaCIOO64YawBzldMIJPhLgJF9hPGChFrBQ

