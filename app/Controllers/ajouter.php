<?php
$title='Ajouter un médicament';$nav='/admin/medicaments/ajouter';$flash=null;$message=[];$nomSaisi='';$quantiteSaisie=0;$prixSaisi='';$isActifSaisi=true;
if($_SERVER['REQUEST_METHOD']==='POST'){
 $nomSaisi=trim($_POST['nom']??'');$quantiteSaisie=$_POST['quantite']??'';$prixSaisi=$_POST['prix']??'';$isActifSaisi=isset($_POST['is_actif']);$prix=parsePrice($prixSaisi);
 if($nomSaisi===''||strlen($nomSaisi)>190||!validQuantity($quantiteSaisie,0)||$prix===null) $message=['type'=>'error','text'=>'Nom, quantité ou prix invalide.'];
 else { $s=$pdo->prepare('SELECT id FROM medicament WHERE LOWER(TRIM(nom))=LOWER(TRIM(?))');$s->execute([$nomSaisi]);$id=$s->fetchColumn();if($id){$message=['type'=>'error','text'=>'Ce médicament existe déjà.'];}else{try{$pdo->prepare('INSERT INTO medicament(nom,quantite,prix,is_actif) VALUES(?,?,?,?)')->execute([$nomSaisi,$quantiteSaisie,$prix,(int)$isActifSaisi]);setFlash('Médicament ajouté.');header('Location: /admin');exit;}catch(PDOException $e){if($e->getCode()!=='23000')throw $e;$message=['type'=>'error','text'=>'Ce médicament existe déjà.'];}}}
}
require APP_ROOT.'/app/Views/ajouter.php';
