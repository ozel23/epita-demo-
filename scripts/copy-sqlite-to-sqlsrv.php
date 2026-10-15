<?php
require __DIR__.'/../vendor/autoload.php';
$root=dirname(__DIR__);
$source=new PDO('sqlite:'.$root.'/database/database.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$e=Dotenv\Dotenv::parse(file_get_contents($root.'/.env.sqlsrv.local'));
$target=new PDO('sqlsrv:Server=127.0.0.1,1433;Database=epita;Encrypt=1;TrustServerCertificate=1',$e['DB_USERNAME'],$e['DB_PASSWORD'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$tables=$source->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name <> 'migrations'")->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table){
 if(!preg_match('/^[a-z_][a-z0-9_]*$/',$table))throw new RuntimeException('Unexpected table');
 if((int)$target->query("SELECT COUNT(*) FROM [$table]")->fetchColumn()!==0)throw new RuntimeException("Target $table must be empty; no data overwritten.");
}
$target->beginTransaction();
try {
 foreach($tables as $table)$target->exec("ALTER TABLE [$table] NOCHECK CONSTRAINT ALL");
 foreach($tables as $table){
  $identity=(bool)$target->query("SELECT COUNT(*) FROM sys.identity_columns WHERE object_id=OBJECT_ID(N'$table')")->fetchColumn();
  if($identity)$target->exec("SET IDENTITY_INSERT [$table] ON");
  $rows=$source->query("SELECT * FROM [$table]");$count=0;
  while($row=$rows->fetch(PDO::FETCH_ASSOC)){
   $columns=implode(',',array_map(function($c){return '['.$c.']';},array_keys($row)));
   $stmt=$target->prepare("INSERT INTO [$table] ($columns) VALUES (".implode(',',array_fill(0,count($row),'?')).")");
   $stmt->execute(array_values($row));$count++;
  }
  if($identity)$target->exec("SET IDENTITY_INSERT [$table] OFF");
  if((int)$target->query("SELECT COUNT(*) FROM [$table]")->fetchColumn()!==$count)throw new RuntimeException("Count mismatch $table");
  echo "$table: $count records verified\n";
 }
 foreach($tables as $table)$target->exec("ALTER TABLE [$table] WITH CHECK CHECK CONSTRAINT ALL");
 $target->commit();echo "Transfer committed; source SQLite untouched.\n";
}catch(Throwable $error){$target->rollBack();throw $error;}
