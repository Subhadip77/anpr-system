# New Life Nursing Home Billing

PHP and MariaDB billing application for New Life Nursing Home. A bill is stored in the database, then viewed or printed from that saved record. No separate local print copy is needed.

## Setup

1. Create/import the database. For a new installation, import [database/schema.sql](database/schema.sql). If you have already imported the SQL dump in the request, run [database/migrate_existing_dump.sql](database/migrate_existing_dump.sql) once instead.
2. Configure the database credentials with environment variables, or edit [config/database.php](config/database.php):

	```sh
	export DB_HOST=127.0.0.1
	export DB_NAME=nursing_billing
	export DB_USER=root
	export DB_PASSWORD='your-password'
	```

3. Ensure PHP has the `pdo_mysql` extension enabled.
4. Start PHP's local server from the project root:

	```sh
	php -S localhost:8000
	```

5. Open `http://localhost:8000/auth/login.php`. The supplied dump contains the `admin` login record. If its password is unknown, replace its hash with one generated using `password_hash()`.

## Workflow

1. Sign in, select **New Bill**, and complete the patient details.
2. Add medicine lines and any room, doctor, or other charge lines. Amounts and totals calculate automatically.
3. Select **Save Bill** to view the saved record, or **Save & Print** to open the formatted printable bill.
4. Use **History** to reopen and print any previous bill.

`save.php` recalculates line-item totals and payment figures on the server inside a database transaction. The client-side form calculations are only for the operator's preview.
