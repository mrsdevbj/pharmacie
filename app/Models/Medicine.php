<?php
final class Medicine {
 public static function search(PDO $db,string $query,bool $public=false): array {
  $sql='SELECT id,nom,prix,quantite,is_actif FROM medicament'; $params=[];
  if ($query!=='') { $sql.=' WHERE nom LIKE ?'; $params[]='%'.$query.'%'; }
  $sql.=$public?' ORDER BY nom ASC':' ORDER BY id DESC';
  $s=$db->prepare($sql); $s->execute($params); return $s->fetchAll();
 }
 // Absolute inventory adjustments use physical stock including held units.
 public static function held(PDO $db,int $id,bool $lock=false): int {
  $sql="SELECT quantity FROM reservations WHERE medicament_id=? AND status IN ('pending','confirmed')";
  if($lock && $db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql')$sql.=' FOR UPDATE';
  $s=$db->prepare($sql);$s->execute([$id]);return array_sum(array_column($s->fetchAll(),'quantity'));
 }
 public static function setPhysicalStock(PDO $db,int $id,int $physical,int $active,?string $name=null,?string $price=null): bool {
  $own=!$db->inTransaction();if($own)$db->beginTransaction();
  try {
   $db->prepare('UPDATE medicament SET quantite=quantite WHERE id=?')->execute([$id]);
   $held=self::held($db,$id,true);if($physical<$held){if($own)$db->rollBack();return false;}
   $sql='UPDATE medicament SET quantite=?,is_actif=?';$params=[$physical-$held,$active];if($name!==null){$sql.=',nom=?';$params[]=$name;}if($price!==null){$sql.=',prix=?';$params[]=$price;}$sql.=' WHERE id=?';$params[]=$id;$db->prepare($sql)->execute($params);
   if($own)$db->commit();return true;
  }catch(Throwable $e){if($own && $db->inTransaction())$db->rollBack();throw $e;}
 }
 public static function addStock(PDO $db,int $id,int $qty): bool { if($qty<1||$qty>100000000)return false; $s=$db->prepare('UPDATE medicament SET quantite=quantite+? WHERE id=? AND quantite<=?');$s->execute([$qty,$id,100000000-$qty]);return $s->rowCount()===1; }
 public static function stats(PDO $db): array { return $db->query('SELECT COUNT(*) AS total, SUM(CASE WHEN quantite>0 AND is_actif=1 THEN 1 ELSE 0 END) AS disponible, SUM(CASE WHEN quantite=0 OR is_actif=0 THEN 1 ELSE 0 END) AS rupture FROM medicament')->fetch(); }
}
