--
-- PostgreSQL database dump
--

-- Dumped from database version 9.0.4
-- Dumped by pg_dump version 9.0.4
-- Started on 2013-08-28 20:12:04

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

--
-- TOC entry 2007 (class 0 OID 0)
-- Dependencies: 1718
-- Name: banco_bancos_id_banco_seq; Type: SEQUENCE SET; Schema: public; Owner: programa
--

SELECT pg_catalog.setval('banco_bancos_id_banco_seq', 5, true);


--
-- TOC entry 2004 (class 0 OID 53509)
-- Dependencies: 1719
-- Data for Name: banco_bancos; Type: TABLE DATA; Schema: public; Owner: programa
--

INSERT INTO banco_bancos (id_banco, nombre, status) VALUES (1, 'Afirme', 'ac');
INSERT INTO banco_bancos (id_banco, nombre, status) VALUES (2, 'Banamex', 'ac');
INSERT INTO banco_bancos (id_banco, nombre, status) VALUES (3, 'BanBajio', 'ac');
INSERT INTO banco_bancos (id_banco, nombre, status) VALUES (4, 'Banorte', 'ac');
INSERT INTO banco_bancos (id_banco, nombre, status) VALUES (5, 'Santander', 'ac');


-- Completed on 2013-08-28 20:12:04

--
-- PostgreSQL database dump complete
--

