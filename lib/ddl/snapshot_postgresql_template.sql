DROP TABLE IF EXISTS ss_tables_phpu_;
CREATE UNLOGGED TABLE ss_tables_phpu_ (
  tablename TEXT NOT NULL,
  nextid INTEGER NOT NULL,
  records INTEGER NOT NULL,
  modifications SMALLINT NOT NULL
) WITH (fillfactor=90);
CREATE INDEX ss_tables_phpu_tablename_idx ON ss_tables_phpu_ USING HASH (tablename);


CREATE OR REPLACE FUNCTION ss_trigger_phpu_() RETURNS TRIGGER AS $$
BEGIN
  EXECUTE 'UPDATE ss_tables_phpu_ SET modifications = 1 WHERE tablename = ''' || TG_TABLE_NAME || ''' AND modifications = 0';
  RETURN NULL;
END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION ss_create_phpu_() RETURNS INTEGER AS $$
DECLARE
  result RECORD;
  result2 RECORD;
  nextid INTEGER;
  tablecount INTEGER;
BEGIN

  tablecount = 0;

  EXECUTE 'DELETE FROM ss_tables_phpu_';

  FOR result IN

    SELECT c.relname, cc.relname IS NOT NULL AS hasid
    FROM pg_catalog.pg_class c
    JOIN pg_catalog.pg_namespace AS ns ON ns.oid = c.relnamespace
    LEFT JOIN pg_catalog.pg_class AS cc ON (ns.oid = cc.relnamespace AND cc.relkind = 'S' AND cc.relname = (c.relname || '_id_seq'))
    WHERE c.relkind = 'r' AND ns.nspname = current_schema() AND c.relname LIKE 'phpu\_%'
    ORDER BY c.relname ASC

  LOOP

    EXECUTE 'DROP TABLE IF EXISTS ss_t_' || result.relname;
    nextid = 0;
    IF result.hasid THEN
      EXECUTE 'SELECT last_value FROM ' || result.relname || '_id_seq'
      INTO result2;
      nextid = result2.last_value;
    END IF;
    EXECUTE 'SELECT COUNT(*) AS records FROM ' || result.relname
    INTO result2;
    IF result2.records > 0 THEN
      EXECUTE 'CREATE UNLOGGED TABLE ss_t_' || result.relname || ' (LIKE ' || result.relname || ')';
      EXECUTE 'INSERT INTO ss_t_' || result.relname || ' SELECT * FROM ' || result.relname;
    END IF;
    EXECUTE 'INSERT INTO ss_tables_phpu_ (tablename, nextid, records, modifications) VALUES ($1, $2, $3, 0)' USING result.relname, nextid, result2.records;
    EXECUTE 'CREATE TRIGGER ss_trigger_' || result.relname || ' AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON ' || result.relname || ' FOR EACH STATEMENT EXECUTE PROCEDURE ss_trigger_phpu_()';

    tablecount = tablecount + 1;

  END LOOP;

  RETURN tablecount;

END;
$$ LANGUAGE plpgsql;




CREATE OR REPLACE FUNCTION ss_reset_phpu_() RETURNS INTEGER AS $$
DECLARE
  result RECORD;
  tablecount INTEGER;
BEGIN

  -- No need to disable triggers and foreign keys because LMS does not use them yet,
  -- note that changing session_replication_role requires superuser privileges.
  --SET session_replication_role = replica;

  tablecount = 0;

  FOR result IN

    SELECT *
    FROM ss_tables_phpu_
    WHERE modifications = 1

  LOOP

    EXECUTE 'DELETE FROM ' || result.tablename;
    IF result.records > 0 THEN
      EXECUTE 'INSERT INTO ' || result.tablename || ' SELECT * FROM ss_t_' || result.tablename;
    END IF;
    IF result.nextid > 0 THEN
      EXECUTE 'ALTER SEQUENCE ' || result.tablename || '_id_seq RESTART WITH ' || result.nextid;
    END IF;

    tablecount = tablecount + 1;

  END LOOP;

  UPDATE ss_tables_phpu_ SET modifications = 0 WHERE modifications = 1;

  FOR result IN

    SELECT c.relname
    FROM pg_catalog.pg_class c
    JOIN pg_catalog.pg_namespace AS ns ON ns.oid = c.relnamespace
    LEFT JOIN ss_tables_phpu_ st ON st.tablename = c.relname
    WHERE c.relkind = 'r' AND ns.nspname = current_schema() AND st.tablename IS NULL AND c.relname LIKE 'phpu\_%'

  LOOP

    EXECUTE 'DROP TABLE ' || result.relname || ' CASCADE';

    tablecount = tablecount + 1;

  END LOOP;

  --SET session_replication_role = origin;

  RETURN tablecount;

END;
$$ LANGUAGE plpgsql;

