<div class="amendement" id="L<?php echo $amendement->texteloi_id; ?>A<?php echo $amendement->numero; ?>">
<div class="source"><a href="<?php echo $amendement->source; ?>">source</a> - <a href="<?php echo $amendement->getLinkPDF(); ?>">PDF</a></div>
<h1><?php echo $amendement->getTitre().' ('.$amendement->getSort().')'; ?></h1>
<h2><?php echo link_to(ucfirst($section->titre), '@section?id='.$section->id); ?></h2>
<div class="identiques">
  
</div>
<?php if ($seance || count($identiques) > 1) { ?>
<div class="seance_amendements">
  <h3><?php if ($seance) echo 'Discuté en '.link_to('séance le '.myTools::displayDate($seance['date']), '@interventions_seance?seance='.$seance['id'].'#amend_'.$amendement->numero);
  if (count($identiques) > 1) {
    if (count($identiques) > 2)
      $ident_titre = " ( amendements identiques : ";
    else $ident_titre = " ( amendement identique : "; ?>
  <em><?php echo $ident_titre; foreach($identiques as $identique) if ($identique->numero != $amendement->numero)
      echo link_to($identique->numero, '@amendement?id='.$identique->id)." "; ?>)</em>
  <?php } ?></h3>
</div>
<?php } ?>
<div class="signataires">
  <p>Déposé le <?php echo myTools::displayDate($amendement->date); ?> par : <?php echo $amendement->getSignataires(1); ?>.</p>
  <?php 
  $deputes = $amendement->getParlementaires(); ?>
  <div class="photos"><p>
  <?php $n_auteurs = count($deputes); $line = floor($n_auteurs/(floor($n_auteurs/16)+1)); $ct = 0; foreach ($deputes as $depute) {
    $titre = $depute->nom.', '.$depute->groupe_acronyme;
    echo '<a href="'.url_for($depute->getPageLink()).'"><img width="50" height="64" title="'.$titre.'" alt="'.$titre.'" src="'.url_for('@resized_photo_parlementaire?height=70&slug='.$depute->slug).'" /></a>&nbsp;';
    if ($ct != 0 && !($ct % $line)) echo '<br/>'; $ct++;
  } ?></p></div>
</div>
<div class="sujet">
  <h3><?php echo $amendement->getSujet().' de la loi N° '.myTools::getLinkLoi($amendement->texteloi_id); ?></h3>
</div>
<div class="texte_intervention">
  <?php echo $amendement->getTexte(); ?>
</div>
<div class="expose_amendement">
  <h3>Exposé Sommaire :</h3>
  <?php echo $amendement->getExpose(); ?>
</div>
<div class="commentaires" id="commentaires">
 <h3>Commentaires</h3>
<?php echo include_component('commentaire', 'show', array('object' => $amendement)); ?>
<?php echo include_component('commentaire', 'form', array('object' => $amendement)); ?>
</div>
</div>