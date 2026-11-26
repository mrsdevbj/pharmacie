<?php
require __DIR__.'/../app/Core/Security.php';
require __DIR__.'/../app/Models/Medicine.php';
if (!in_array('sqlite',PDO::getAvailableDrivers(),true)) { fwrite(STDERR,"Pilote PDO SQLite absent.\n"); exit(1); }
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$sql=file_get_contents(__DIR__.'/../database/migrations/sqlite.sql'); $db->exec($sql); $db->exec($sql);
function check($ok,$name) { if (!$ok) throw new RuntimeException($name); echo "OK $name\n"; }
check((int)$db->query('SELECT COUNT(*) FROM pharmacy_settings')->fetchColumn()===1,'init idempotente');
$db->prepare('INSERT INTO medicament(nom,quantite,prix,is_actif) VALUES(?,?,?,?)')->execute(['Panadol',2,500,1]);
check(count(Medicine::search($db,'Panadol',true))===1,'recherche');
check((int)Medicine::stats($db)['disponible']===1,'statistiques');
$db->prepare('UPDATE medicament SET quantite=?,is_actif=? WHERE id=?')->execute([3,0,1]);
$m=Medicine::search($db,'Panadol')[0]; check((int)$m['prix']===500&&!isProductAvailable($m),'prix préservé, désactivation');
try { $db->exec("INSERT INTO medicament(nom,quantite) VALUES('Bad',-1)"); throw new RuntimeException('contrainte absente'); } catch(PDOException $e) { echo "OK contrainte stock\n"; }
