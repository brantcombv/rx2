/* Database metadata that is used for texts translation model (for MySQL 5): */

CREATE TABLE text_sections (
  id varchar(50) NOT NULL,
  is_deleted tinyint(1) NOT NULL default 0,
  can_have_subids tinyint(1) NOT NULL default 0,
  can_have_patches tinyint(1) NOT NULL default 0,
  raw_keys_allowed tinyint(1) NOT NULL default 0,
  description varchar(255) default NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE texts (
  id int(11) NOT NULL auto_increment,
  is_deleted tinyint(1) NOT NULL default 0,
  section_id varchar(50) NOT NULL,
  sub_id varchar(50) default NULL,
  is_raw tinyint(1) NOT NULL default 0,
  name varchar(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_text (section_id,sub_id,name),
  KEY section_id (section_id),
  CONSTRAINT fk_texts_sections FOREIGN KEY (section_id) REFERENCES text_sections (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE text_translations (
  id int(11) NOT NULL auto_increment,
  text_id int(11) NOT NULL,
  is_deleted tinyint(1) NOT NULL default 0,
  language varchar(10) NOT NULL,
  is_content tinyint(1) NOT NULL default 0,
  t_value varchar(255) default NULL,
  t_content text,
  is_plural tinyint(1) NOT NULL default 0,
  plural_1 varchar(255) default NULL,
  plural_2 varchar(255) default NULL,
  PRIMARY KEY (id),
  KEY text_id (text_id),
  KEY idx_lang (language),
  CONSTRAINT fk_txttrans_texts FOREIGN KEY (text_id) REFERENCES texts (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE text_patches (
  id int(11) NOT NULL auto_increment,
  text_id int(11) NOT NULL,
  owner_id int(11) NOT NULL,
  is_deleted tinyint(1) NOT NULL default 0,
  language varchar(10) NOT NULL,
  is_content tinyint(1) NOT NULL default 0,
  t_value varchar(255) default NULL,
  t_content text,
  PRIMARY KEY (id),
  KEY text_id (text_id),
  KEY idx_lang (language),
  CONSTRAINT fk_text_ptc_texts FOREIGN KEY (text_id) REFERENCES texts (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
