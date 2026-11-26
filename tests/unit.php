<?php
require __DIR__.'/../app/Core/Security.php';
require __DIR__.'/../app/Services/InventoryImport.php';
function check($condition,$name) { if (!$condition) throw new RuntimeException($name); echo "OK $name\n"; }
check(validQuantity('12',0),'quantité valide');
check(!validQuantity('-1',0)&&!validQuantity('1.5',0),'quantités invalides');
check(extractMedicationData(['Panadol','12'])['quantite']===12,'import quantité');
check(extractMedicationData(['Panadol','-3'])===null,'rejet quantité négative');
check(!isProductAvailable(['quantite'=>2,'is_actif'=>0]),'produit inactif');
check(isProductAvailable(['quantite'=>2,'is_actif'=>1]),'produit actif');
check(password_verify('Test-Password-123',hashPassword('Test-Password-123')),'hachage');
$_SESSION=[]; check(strlen(csrfToken())===64&&csrfToken()===csrfToken(),'jeton CSRF');
$f=tempnam(sys_get_temp_dir(),'csv'); file_put_contents($f,"nom;quantite\nPanadol;12\n"); check(parseCsvRows($f)[1]===['Panadol','12'],'CSV point-virgule'); file_put_contents($f,"nom,quantite\nPanadol,12\n"); check(parseCsvRowsWithComma($f)[1]===['Panadol','12'],'CSV virgule'); unlink($f);

check(parsePrice('500,50')==='500.50'&&parsePrice('-1')===null&&parsePrice('1e3')===null,'validation prix');
$rows=inventoryPreview([['nom','quantite','prix'],['Test','2','500'],['Test','3','500'],['Bad','-2','']],true);check(count($rows)===3&&$rows[0]['prix']==='500'&&$rows[0]['line']===2&&$rows[1]['error']!==''&&$rows[2]['error']!=='','aperçu et doublons');
check(inventoryPreview([['Produit A','2','']],false)[0]['line']===1,'import sans entête');
