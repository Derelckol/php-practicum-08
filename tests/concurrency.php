<?php
if (PHP_SAPI !== 'cli' || !str_starts_with(getenv('DB_NAME') ?: '', 'php08_test_')) exit('Set DB_NAME to a dedicated php08_test_* database');
$dsn='mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').';port='.(getenv('DB_PORT') ?: '3306').';dbname='.getenv('DB_NAME').';charset=utf8mb4';
function connection(): PDO { global $dsn; return new PDO($dsn,getenv('DB_USER') ?: 'root',getenv('DB_PASSWORD') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]); }
$p=connection();$p->exec("INSERT INTO events(title,event_date,venue,seats_left) VALUES('Concurrent fixture','2027-01-01','CONCURRENT',1)");$id=$p->lastInsertId();$p=null;
$children=[];
for($i=0;$i<8;$i++) {
 $pid=pcntl_fork();
 if($pid===0) { $db=connection(); $s=$db->prepare('UPDATE events SET seats_left=seats_left-1 WHERE id=? AND seats_left>0'); $s->execute([$id]);exit($s->rowCount()===1?10:0); }
 $children[]=$pid;
}
$wins=0;foreach($children as $pid){pcntl_waitpid($pid,$status); if(pcntl_wexitstatus($status)===10)$wins++;}
$p=connection();$s=$p->prepare('SELECT seats_left FROM events WHERE id=?');$s->execute([$id]);$left=$s->fetchColumn();
$s=$p->prepare('DELETE FROM events WHERE id=?');$s->execute([$id]);
if($wins!==1 || $left!==0)throw new RuntimeException('Concurrency failed');
echo "PASS: 8 separate MySQL connections, one purchase, seats_left=0\n";
