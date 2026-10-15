<?php
// Local development provisioning; secrets never printed.
require __DIR__.'/../vendor/autoload.php';
$root = dirname(__DIR__);
$server = Dotenv\Dotenv::parse(file_get_contents($root.'/.local/sqlsrv/server.env'));
$pdo = new PDO('sqlsrv:Server=127.0.0.1,1433;Database=master;Encrypt=1;TrustServerCertificate=1', 'sa', $server['MSSQL_SA_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("IF DB_ID(N'epita') IS NULL CREATE DATABASE [epita]");
$file=$root.'/.local/sqlsrv/app.json';
if (!file_exists($file)) {
    file_put_contents($file, json_encode(['password'=>bin2hex(random_bytes(24)).'aA1!']));
    chmod($file,0600);
}
$secret=json_decode(file_get_contents($file),true)['password'];
$quoted=$pdo->quote($secret);
$pdo->exec("IF NOT EXISTS (SELECT 1 FROM sys.sql_logins WHERE name=N'epita_app') CREATE LOGIN [epita_app] WITH PASSWORD=$quoted, CHECK_POLICY=ON");
$pdo->exec('USE [epita]');
$pdo->exec("IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name=N'epita_app') CREATE USER [epita_app] FOR LOGIN [epita_app]");
// Development account includes schema migrations; production must use a separate deploy account.
$pdo->exec("IF IS_ROLEMEMBER('db_owner','epita_app') <> 1 ALTER ROLE [db_owner] ADD MEMBER [epita_app]");
$original=file_get_contents($root.'/.env');
if (!file_exists($root.'/.local/sqlsrv/env.before-sqlsrv')) {
 file_put_contents($root.'/.local/sqlsrv/env.before-sqlsrv',$original); chmod($root.'/.local/sqlsrv/env.before-sqlsrv',0600);
}
$values=['DB_CONNECTION'=>'sqlsrv','DB_HOST'=>'127.0.0.1','DB_PORT'=>'1433','DB_DATABASE'=>'epita','DB_USERNAME'=>'epita_app','DB_PASSWORD'=>'"'.$secret.'"','DB_ENCRYPT'=>'yes','DB_TRUST_SERVER_CERTIFICATE'=>'true'];
foreach($values as $key=>$value){
 $line=$key.'='.$value;
 $original=preg_match('/^'.preg_quote($key,'/').'=/m',$original) ? preg_replace('/^'.preg_quote($key,'/').'=.*$/m',$line,$original) : $original."\n".$line;
}
file_put_contents($root.'/.env.sqlsrv.local',$original); chmod($root.'/.env.sqlsrv.local',0600);
echo "Database epita dan akun development siap. Konfigurasi tersimpan privat; aplikasi belum dialihkan.\n";
