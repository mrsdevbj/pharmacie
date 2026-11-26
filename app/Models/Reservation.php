<?php
final class Reservation {
 public const LABELS=['pending'=>'En attente','confirmed'=>'Confirmée','collected'=>'Retirée','rejected'=>'Refusée','expired'=>'Expirée','cancelled'=>'Annulée'];
 public static function create(PDO $db,int $client,int $medicine,int $qty,string $phone): string {
  if ($qty<1 || $qty>100 || !preg_match('/^\+?[0-9 ()-]{8,25}$/D',$phone) || strlen(preg_replace('/\D/','',$phone))<8) throw new DomainException('Quantité ou téléphone invalide.');
  $db->beginTransaction();
  try {
   $s=$db->prepare('UPDATE medicament SET quantite=quantite-? WHERE id=? AND is_actif=1 AND quantite>=?'); $s->execute([$qty,$medicine,$qty]);
   if ($s->rowCount()!==1) throw new DomainException('Quantité indisponible.');
   $s=$db->prepare('SELECT nom FROM medicament WHERE id=?'); $s->execute([$medicine]); $name=$s->fetchColumn();
   $ref=strtoupper(bin2hex(random_bytes(8)));
   $db->prepare('INSERT INTO reservations(reference,client_id,medicament_id,medicament_name,quantity,phone,status,created_at,expires_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$ref,$client,$medicine,$name,$qty,$phone,'pending',time(),time()+86400]);
   $db->commit(); return $ref;
  } catch(Throwable $e) { if($db->inTransaction())$db->rollBack(); throw $e; }
 }
 public static function transition(PDO $db,int $id,string $target,?int $client=null): bool {
  $from=match($target){'confirmed','rejected'=>['pending'],'collected'=>['confirmed'],'expired','cancelled'=>['pending','confirmed'],default=>throw new DomainException('Action invalide.')};
  if ($client!==null && $target!=='cancelled') throw new DomainException('Action invalide.');
  // The medicine ID is immutable. Lock medicine before reservation in every transition.
  $s=$db->prepare('SELECT medicament_id FROM reservations WHERE id=?');$s->execute([$id]);$mid=$s->fetchColumn();if(!$mid)return false;
  $db->beginTransaction();
  try {
   $db->prepare('UPDATE medicament SET quantite=quantite WHERE id=?')->execute([$mid]);
   if($target==='confirmed' || $target==='collected'){
    $s=$db->prepare('SELECT is_actif FROM medicament WHERE id=?'.($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':''));$s->execute([$mid]);if((int)$s->fetchColumn()!==1){$db->rollBack();return false;}
   }
   $sql='UPDATE reservations SET status=? WHERE id=? AND status IN ('.implode(',',array_fill(0,count($from),'?')).') AND expires_at '.($target==='expired'?' <= ':' > ').'?';
   $params=[$target,$id,...$from,time()]; if($client!==null){$sql.=' AND client_id=?';$params[]=$client;}
   $s=$db->prepare($sql);$s->execute($params); if($s->rowCount()!==1){$db->rollBack();return false;}
   if(in_array($target,['expired','cancelled','rejected'],true)){
    $s=$db->prepare('SELECT medicament_id,quantity FROM reservations WHERE id=?');$s->execute([$id]);$r=$s->fetch();
    $db->prepare('UPDATE medicament SET quantite=quantite+? WHERE id=?')->execute([$r['quantity'],$r['medicament_id']]);
   }
   $db->commit();return true;
  } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }
 public static function expire(PDO $db): int {
  $s=$db->prepare("SELECT id FROM reservations WHERE status IN ('pending','confirmed') AND expires_at<=? ORDER BY id LIMIT 500");$s->execute([time()]);$n=0;foreach($s->fetchAll() as $r) if(self::transition($db,(int)$r['id'],'expired'))$n++;return $n;
 }
}
