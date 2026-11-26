<?php
final class GuardPharmacy {
 public static function current(PDO $db): array { $s=$db->prepare('SELECT p.*,g.start_at,g.end_at FROM pharmacies p JOIN pharmacy_guards g ON g.pharmacy_id=p.id WHERE g.cancelled=0 AND g.start_at<=? AND g.end_at>? ORDER BY p.commune,p.name');$s->execute([time(),time()]);return $s->fetchAll(); }
 public static function parseDate(string $v): int { $d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$v,new DateTimeZone('Africa/Porto-Novo'));if(!$d||$d->format('Y-m-d\TH:i')!==$v)throw new DomainException('Date invalide.');return $d->getTimestamp(); }
 public static function date(int $v,string $format='d/m/Y H:i'): string {return (new DateTimeImmutable('@'.$v))->setTimezone(new DateTimeZone('Africa/Porto-Novo'))->format($format);}
 public static function key(array $data): string {return hash('sha256',strtolower(trim($data[0]))."\n".strtolower(trim($data[2]))."\n".strtolower(trim($data[1])));}
 public static function savePharmacy(PDO $db,array $input,?int $id): void {
  $data=[];foreach(['name'=>190,'address'=>255,'commune'=>100,'phone'=>30] as $k=>$max){$v=trim($input[$k]??'');if($v===''||strlen($v)>$max)throw new DomainException('Veuillez renseigner tous les champs.');$data[]=$v;}
  if(!preg_match('/^\+?[0-9 ()-]{8,25}$/D',$data[3])||strlen(preg_replace('/\D/','',$data[3]))<8)throw new DomainException('Téléphone invalide.');$data[]=self::key($data);
  if($id===null)$db->prepare('INSERT INTO pharmacies(name,address,commune,phone,identity_key) VALUES(?,?,?,?,?)')->execute($data);else{$data[]=$id;$db->prepare('UPDATE pharmacies SET name=?,address=?,commune=?,phone=?,identity_key=? WHERE id=?')->execute($data);}
 }
 public static function schedule(PDO $db,int $id,string $from,string $to): void {
  $start=self::parseDate($from);$end=self::parseDate($to);if($end<=$start)throw new DomainException('La fin doit être postérieure au début.');
  $db->beginTransaction();try{$db->prepare('UPDATE pharmacies SET name=name WHERE id=?')->execute([$id]);$s=$db->prepare('SELECT id FROM pharmacies WHERE id=?');$s->execute([$id]);if(!$s->fetchColumn())throw new DomainException('Pharmacie introuvable.');$s=$db->prepare('SELECT id FROM pharmacy_guards WHERE pharmacy_id=? AND cancelled=0 AND start_at<? AND end_at>?');$s->execute([$id,$end,$start]);if($s->fetchColumn())throw new DomainException('Une garde existe déjà sur cette période.');$db->prepare('INSERT INTO pharmacy_guards(pharmacy_id,start_at,end_at) VALUES(?,?,?)')->execute([$id,$start,$end]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }
 public static function migrate(PDO $db): void {
  $db->beginTransaction();try{$rows=$db->query('SELECT * FROM guard_pharmacies ORDER BY id')->fetchAll();foreach($rows as $r){$s=$db->prepare('SELECT id FROM pharmacy_guards WHERE legacy_id=?');$s->execute([$r['id']]);if($s->fetchColumn())continue;$data=[$r['name'],$r['address'],$r['commune'],$r['phone']];$key=self::key($data);$s=$db->prepare('SELECT id FROM pharmacies WHERE identity_key=?');$s->execute([$key]);$id=$s->fetchColumn();if(!$id){$db->prepare('INSERT INTO pharmacies(name,address,commune,phone,identity_key) VALUES(?,?,?,?,?)')->execute([...$data,$key]);$id=$db->lastInsertId();}$db->prepare('INSERT INTO pharmacy_guards(pharmacy_id,start_at,end_at,cancelled,legacy_id) VALUES(?,?,?,?,?)')->execute([$id,$r['start_at'],$r['end_at'],1-(int)$r['active'],$r['id']]);}$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }
}
