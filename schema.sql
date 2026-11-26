BEGIN;
DO $$ BEGIN
 IF current_database() <> 'awd_li' THEN RAISE EXCEPTION 'Expected awd_li'; END IF;
END $$;
SET LOCAL search_path TO public;
CREATE TABLE IF NOT EXISTS departments (
 id serial PRIMARY KEY, name varchar(80) UNIQUE NOT NULL, description text NOT NULL
);
CREATE TABLE IF NOT EXISTS categories (
 id serial PRIMARY KEY, name varchar(50) UNIQUE NOT NULL, description text NOT NULL
);
CREATE TABLE IF NOT EXISTS users (
 id serial PRIMARY KEY, department_id int NOT NULL REFERENCES departments,
 username varchar(32) UNIQUE NOT NULL CHECK(username ~ '^[a-z0-9_]{3,32}$'),
 full_name varchar(100) NOT NULL, password_hash text NOT NULL,
 role text NOT NULL CHECK(role IN ('employee','admin')), created_at timestamp NOT NULL DEFAULT now()
);
ALTER TABLE users ADD COLUMN IF NOT EXISTS recovery_code_hash text;
CREATE TABLE IF NOT EXISTS budgets (
 id serial PRIMARY KEY, department_id int NOT NULL REFERENCES departments,
 month date NOT NULL CHECK(extract(day from month)=1), amount numeric(12,2) NOT NULL CHECK(amount BETWEEN 0.01 AND 9999999999.99),
 UNIQUE(department_id,month)
);
CREATE TABLE IF NOT EXISTS expenses (
 id serial PRIMARY KEY, user_id int NOT NULL REFERENCES users, category_id int NOT NULL REFERENCES categories,
 title varchar(80) NOT NULL CHECK(length(title)>=3), amount numeric(12,2) NOT NULL CHECK(amount BETWEEN 0.01 AND 9999999999.99),
 expense_date date NOT NULL, merchant varchar(80) NOT NULL CHECK(length(merchant)>=2),
 description varchar(500) NOT NULL CHECK(length(description)>=5), is_recurring boolean NOT NULL DEFAULT false,
 created_at timestamp NOT NULL DEFAULT now(), status text NOT NULL DEFAULT 'pending' CHECK(status IN ('approved','pending','rejected')),
 reviewed_by int REFERENCES users, reviewed_at timestamp, review_note text,
 CHECK ((status='pending' AND reviewed_by IS NULL AND reviewed_at IS NULL AND review_note IS NULL)
 OR (status<>'pending' AND reviewed_by IS NOT NULL AND reviewed_at IS NOT NULL AND reviewed_at>=created_at)),
 CHECK(status<>'rejected' OR coalesce(length(trim(review_note)),0)>0)
);
COMMIT;