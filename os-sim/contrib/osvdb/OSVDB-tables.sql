-- -----------------------------------------------------------------------------
-- OSVDB Schema: $Revision: 1.2 $
-- (c)© Copyright 2005 Open Security Foundation (OSF) / Open Source Vulnerability Database (OSVDB), All Rights Reserved. 
-- -----------------------------------------------------------------------------


-- -----------------------------------------------------------------------------
-- Install:
-- 1. create a database called 'osvdb' (for example)
-- 2. (optional) create a user to manage the database
-- 3. Import the schema: mysql -u <username> -p osvdb < OSVDB-tables.mysql
-- -----------------------------------------------------------------------------


-- -----------------------------------------------------------------------------
-- Table: Vuln
-- Description: This is the main table of the schema.  This is where OVSBD IDs live.
-- Fields:
-- 	osvdb_id = This main identifier for a vulnerability
-- 	osvdb_title = This is the moderator approved Text Title for the vulnerability "Apache Chunked Encoding Buffer Overflow"
--	disclosure_date = If one is provided this field can be filled in with the time the vulnerability was disclosed
-- 	discovery_date = If one is provided this field can be filled in with the time the vulnerability was discovered
-- 	osvdb_create_date = This is the timestamp of when the vulnerability was created in OSVDB.
--	last_modified_date = This is the timestamp of when anything (references, txt, objects) was modified on this vulnerability
-- 	exploit_publish_date = This the date when a actual working exploit for this vulnerability was released
--	location_physical = Vuln Classification
-- 	location_local = Vuln Classification
-- 	location_remote = Vuln Classification
-- 	location_dialup = Vuln Classification
-- 	location_unknown = Vuln Classification
-- 	attack_type_auth_manage = Vuln Classification
-- 	attack_type_crypt = Vuln Classification
-- 	attack_type_dos = Vuln Classification
-- 	attack_type_hijack = Vuln Classification
-- 	attack_type_info_disclose = Vuln Classification
-- 	attack_type_infrastruct = Vuln Classification
-- 	attack_type_input_manip = Vuln Classification
-- 	attack_type_miss_config = Vuln Classification
-- 	attack_type_race = Vuln Classification
-- 	attack_type_other = Vuln Classification
-- 	attack_type_unknown = Vuln Classification
-- 	impact_confidential = Vuln Classification
-- 	impact_integrity = Vuln Classification
-- 	impact_available = Vuln Classification
-- 	impact_unknown = Vuln Classification
-- 	exploit_available = Vuln Classification
-- 	exploit_unavailable = Vuln Classification
-- 	exploit_rumored = Vuln Classification
-- 	exploit_unknown = Vuln Classification
-- 	vuln_verified = Vuln Classification
-- 	vuln_myth_fake = Vuln Classification
-- 	vuln_best_prac = Vuln Classification
-- 	vuln_concern = Vuln Classification
-- 	vuln_web_check = Vuln Classification
-- -----------------------------------------------------------------------------
CREATE TABLE vuln (
    osvdb_id integer DEFAULT '0' NOT NULL,
    osvdb_title character varying(255) DEFAULT '',
    disclosure_date datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
    discovery_date datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
    osvdb_create_date datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
    last_modified_date timestamp NOT NULL,
    exploit_publish_date datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
    location_physical tinyint default 0 NOT NULL,
    location_local tinyint default 0 NOT NULL,
    location_remote tinyint default 0 NOT NULL,
    location_dialup tinyint default 0 NOT NULL,
    location_unknown tinyint default 0 NOT NULL,
    attack_type_auth_manage tinyint default 0 NOT NULL,
    attack_type_crypt tinyint default 0 NOT NULL,
    attack_type_dos tinyint default 0 NOT NULL,
    attack_type_hijack tinyint default 0 NOT NULL,
    attack_type_info_disclose tinyint default 0 NOT NULL,
    attack_type_infrastruct tinyint default 0 NOT NULL,
    attack_type_input_manip tinyint default 0 NOT NULL,
    attack_type_miss_config tinyint default 0 NOT NULL,
    attack_type_race tinyint default 0 NOT NULL,
    attack_type_other tinyint default 0 NOT NULL,
    attack_type_unknown tinyint default 0 NOT NULL,
    impact_confidential tinyint default 0 NOT NULL,
    impact_integrity tinyint default 0 NOT NULL,
    impact_available tinyint default 0 NOT NULL,
    impact_unknown tinyint default 0 NOT NULL,
    exploit_available tinyint default 0 NOT NULL,
    exploit_unavailable tinyint default 0 NOT NULL,
    exploit_rumored tinyint default 0 NOT NULL,
    exploit_unknown tinyint default 0 NOT NULL,
    vuln_verified tinyint default 0 NOT NULL,
    vuln_myth_fake tinyint default 0 NOT NULL,
    vuln_best_prac tinyint default 0 NOT NULL,
    vuln_concern tinyint default 0 NOT NULL,
    vuln_web_check tinyint default 0 NOT NULL,
    PRIMARY KEY (osvdb_id)
);


-- -----------------------------------------------------------------------------
-- Table: ext_txt_type
-- Description: This table defines the types of blobs we are storing in the ext_txt reference table.
-- Fields:
-- 	type_id = unique identifier for finding/deleting/updating a txt_type
--	type_name = Human readable identifier for the txt_type "Vulnerability Description"
-- -----------------------------------------------------------------------------
CREATE TABLE ext_txt_type (
    type_id integer AUTO_INCREMENT NOT NULL,
    type_name character varying(255) DEFAULT '',
    PRIMARY KEY (type_id)
);


-- -----------------------------------------------------------------------------
-- Table: language
-- Description: This table adds support for multiple language txt_type's in the database
-- Fields:
-- 	lang_id = unique identifier for finding/deleting/updating a lang_name
--	lang_name = Human readable identifier for the txt_type stored in ext_txt "English"
-- -----------------------------------------------------------------------------
CREATE TABLE language (
    lang_id integer AUTO_INCREMENT NOT NULL,
    lang_name character varying(255) DEFAULT '',
    PRIMARY KEY (lang_id)
);


-- -----------------------------------------------------------------------------
-- Table: Author
-- Description: This table adds support for identifing contributors for anything in ext_txt table.  
--	It is not a all encompasing table for every little contribution and does not allow for complete 
--	identification of every little text blob an author contributes.  All it allows for is a contributors 
--	line to any osvdb_id.  The authors is used to track the external text authors, as well as the creditee 
--	of each vulnerability.
-- Fields:
-- 	author_id = unique identifier for finding/deleting/updating a author_name.
-- 	author_name = txt string identifing an author/contributor.
-- 	author_company = txt string identifing an author/contributor's company.
-- 	author_email = txt string identifing an author/contributor's email.
--	company_url = txt string identifing an author/contributor's company url.
-- -----------------------------------------------------------------------------

CREATE TABLE author (
    author_id integer AUTO_INCREMENT NOT NULL,
    author_name character varying(255) DEFAULT '',
    author_company character varying(255) DEFAULT '',
    author_email character varying(255) DEFAULT '',
    company_url character varying(255) DEFAULT '',
    PRIMARY KEY (author_id)
);


-- -----------------------------------------------------------------------------
-- Table: ext_ref
-- Description: This table is binds external values to osvdb_ids.  It allows for an infinite number of bindings between the two.
-- Fields:
-- 	ref_id = unique identifier for finding/deleting/updating a reference.
-- 	osvdb_id = references vuln.osvdb_id
-- 	value_id = references ext_ref_value.value_id
-- 	indirect = indirect reference, 1 = true
-- -----------------------------------------------------------------------------

CREATE TABLE ext_ref (
    ref_id integer AUTO_INCREMENT NOT NULL,
    osvdb_id integer DEFAULT '0' NOT NULL,
    value_id integer DEFAULT '0' NOT NULL,
    indirect smallint DEFAULT '0' NOT NULL,
    PRIMARY KEY (ref_id)
);


-- -----------------------------------------------------------------------------
-- Table: ext_ref_type
-- Description: This tables holds information and descriptions of short external references.  Since not all 
--	external references belong in a blob this table along with ext_ref were created.  This allows things 
--	like Nessus IDs, Snort Sig IDs to be stored in a more sane manner.
-- Fields:
-- 	type_id = unique identifier for finding/deleting/updating a type.
-- 	type_name = Human readable identifier
-- -----------------------------------------------------------------------------

CREATE TABLE ext_ref_type (
    type_id integer AUTO_INCREMENT NOT NULL,
    type_name character varying(255) DEFAULT '',
    PRIMARY KEY (type_id)
);


-- -----------------------------------------------------------------------------
-- Table: ext_ref_value
-- Description: This table was created to keep the number of ext_ref values collisions to a minimum.  Now it 
--	is possible to bind a single value to multiple osvdb_ids.
-- Fields:
-- 	value_id = unique identifer
-- 	type_id = references ext_ref_type.type_id
--	ref_value = text value for ext_ref_type values.
-- -----------------------------------------------------------------------------

CREATE TABLE ext_ref_value (
    value_id integer AUTO_INCREMENT NOT NULL,
    type_id integer DEFAULT '0' NOT NULL,
    ref_value character varying(255) DEFAULT '',
    PRIMARY KEY (value_id)
);


-- -----------------------------------------------------------------------------
-- Table: ext_txt
-- Description: This tables stores txt blobs for any type of text that is larger than 1024 characters
-- Fields:
-- 	ext_id = unique identifier
-- 	osvdb_id = reference to vuln.osvdb_id
--	lang_id = reference to languauge.lang_id
--	type_id = reference to txt_type.type_id
--	author_id = reference to author.author_id
--	revision = When txt blobs are updated/fixed/modified the new text is reinserted into this 
--		tables and the revision number is incremented.
--	text = text blob for storing textual information.
-- -----------------------------------------------------------------------------

CREATE TABLE ext_txt (
    ext_id integer AUTO_INCREMENT NOT NULL,
    osvdb_id integer DEFAULT '0' NOT NULL,
    lang_id integer DEFAULT '0' NOT NULL,
    type_id integer DEFAULT '0' NOT NULL,
    author_id integer DEFAULT '0' NOT NULL,
    revision integer DEFAULT '0' NOT NULL,
    text blob,
    PRIMARY KEY (ext_id)
);


-- -----------------------------------------------------------------------------
-- Table: object
-- Description: This table binds vendor, base, version and vulnerability together.
-- Fields:
-- 	object_id = unique identifier
-- 	vendor_id = references object_vendor.vendor_id
--	base_id = references object_base.base_id
--	version_id = references object_version.version_id
--	osvdb_id = reference to vuln.osvdb_id
-- -----------------------------------------------------------------------------

CREATE TABLE object (
    object_id integer AUTO_INCREMENT NOT NULL,
    osvdb_id integer DEFAULT '0' NOT NULL,
    corr_id integer DEFAULT '0' NOT NULL,
    type_id integer DEFAULT '0' NOT NULL,
    PRIMARY KEY (object_id)
);


-- -----------------------------------------------------------------------------
-- Table: object_correlation
-- Description: This table binds vendor, base, version and vulnerability together.
-- Fields:
-- 	corr_id = unique identifier
-- 	vendor_id = references object_vendor.vendor_id
--	base_id = references object_base.base_id
--	version_id = references object_version.version_id
-- -----------------------------------------------------------------------------

CREATE TABLE object_correlation (
    corr_id integer AUTO_INCREMENT NOT NULL,
    vendor_id integer DEFAULT '0' NOT NULL,
    base_id integer DEFAULT '0' NOT NULL,
    version_id integer DEFAULT '0' NOT NULL,
    PRIMARY KEY (corr_id)
);


-- -----------------------------------------------------------------------------
-- Table: object_affect_type
-- Description: This table stores the types of affections, like 'might be affected'.
-- Fields:
-- 	type_id = unique identifier
-- 	type_name = Text field for name of affect.
-- -----------------------------------------------------------------------------

CREATE TABLE object_affect_type (
    type_id integer AUTO_INCREMENT NOT NULL,
    type_name character varying(255) DEFAULT '',
    PRIMARY KEY (type_id)
);


-- -----------------------------------------------------------------------------
-- Table: object_base
-- Description: object_base contains the name of a product.  ("Windows", "Exchange")
-- Fields:
-- 	base_id = unique identifier
-- 	base_name = Text field for root cardinal name of product.
-- -----------------------------------------------------------------------------

CREATE TABLE object_base (
    base_id integer AUTO_INCREMENT NOT NULL,
    base_name character varying(255) DEFAULT '',
    PRIMARY KEY (base_id)
);


-- -----------------------------------------------------------------------------
-- Table: object_vendor
-- Description: object_vendor contains the name of the vendor.  ("Microsoft", "Sun")
-- Fields:
-- 	vendor_id = unique identifier
-- 	vendor_name = Text field for name of vendor.
-- -----------------------------------------------------------------------------

CREATE TABLE object_vendor (
    vendor_id integer AUTO_INCREMENT NOT NULL,
    vendor_name character varying(255) DEFAULT '',
    PRIMARY KEY (vendor_id)
);


-- -----------------------------------------------------------------------------
-- Table: object_version
-- Description: object_vendor contains the name of the version.  ("1.0", "2.0")
-- Fields:
-- 	version_id = unique identifier
-- 	version_name = Text field for name of version.
-- -----------------------------------------------------------------------------

CREATE TABLE object_version (
    version_id integer AUTO_INCREMENT NOT NULL,
    version_name character varying(255) DEFAULT '',
    PRIMARY KEY (version_id)
);


-- -----------------------------------------------------------------------------
-- Table: score
-- Description: This table is used to bind a scoring weight to a vulnerability.  It was intended to 
--	allow every vulnerability in the database to be associated with one scoring weight.  Currently 
--	this table is not used, but will be used in the future.  Also, this could be used by other 
--	organizations to store vulnerability scores without having to modify the core osvdb tables
-- Fields:
-- 	score_id = unique identifier
-- 	weight_id = reference to score_weight.weight_id
--	osvdb_id = reference to vuln.osvdb_id
--	osvdb_id2 = reservied field for future use.
-- -----------------------------------------------------------------------------

CREATE TABLE score (
    score_id integer AUTO_INCREMENT NOT NULL,
    weight_id integer DEFAULT '0' NOT NULL,
    osvdb_id integer DEFAULT '0' NOT NULL,
    osvdb_id2 integer DEFAULT '0' NOT NULL,
    PRIMARY KEY (score_id)
);


-- -----------------------------------------------------------------------------
-- Table: score_weight
-- Description: This table is not used by the osvdb development team.  It was added so other organizations 
--	using this database have a place to store scoring information without having to modify the core osvdb tables
-- Fields:
-- 	weight_id = unique identifier
-- 	weight_name = Human Identifier for a type of weight ("Remote Root Vulnerability")
--	weight = small field to store any type of scoring information needed for scoring calculations (20%, .20, 5*2) etc.
-- -----------------------------------------------------------------------------

CREATE TABLE score_weight (
    weight_id integer AUTO_INCREMENT NOT NULL,
    weight_name character varying(255) DEFAULT '',
    weight character varying(10) DEFAULT '0',
    PRIMARY KEY (weight_id)
);


-- -----------------------------------------------------------------------------
-- Table: credit
-- Description: This table adds support for identifing credit for discovering a vuln.  Instead of storing '
--	author "LIKE" information, we just reference the author table, as the information is extremely simular.
-- Fields:
-- 	credit_id = unique identifier
--	osvdb_id = reference to vuln.osvdb_id
--	author_id = reference to author.author_id
-- -----------------------------------------------------------------------------

CREATE TABLE credit (
    credit_id integer AUTO_INCREMENT NOT NULL,
    osvdb_id integer NOT NULL,
    author_id integer NOT NULL,
    PRIMARY KEY (credit_id)
);



-- -----------------------------------------------------------------------------
-- Permissions.
-- -----------------------------------------------------------------------------
GRANT INSERT,SELECT,UPDATE,DELETE ON vuln TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON ext_txt_type TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON language TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON author TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON ext_ref TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON ext_ref_type TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON ext_ref_value TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON ext_txt TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON object TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON object_base TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON object_vendor TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON object_version TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON score TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON score_weight TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON credit TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON object_affect_type TO "osvdb";
GRANT INSERT,SELECT,UPDATE,DELETE ON object_correlation TO "osvdb";
