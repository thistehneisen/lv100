<?php

	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}

	$nodeId = Page()->reqParams[0];
$formAssocs = Page()->getOption("node_galleries_assoc", array());
$selectedGalleryId = $formAssocs[$nodeId];


	$galleries = Page()->getNode(array(
		"filter" => array(
			"controller" => "galleries",
			"view" => "gallery",
			"enabled" => 1,
			"language" => Page()->getNode(array(
				"filter" => array("id" => $_GET["sid"]),
				"returnResults" => "first",
				"returnFields" => "language"
			))
		),
		"order" => array("time_added"=>"DESC")
	));

Page()->injectStart(".rightbar #settings .content");
?>
	<fieldset class="jui">
		<label>Galerija:</label>
		<select name="custom_gallery_id" style="width: 100%;" class="form-control">
			<option value="0">--Nav izvēlēta--</option>
			<?php foreach ($galleries as $gallery) { ?>
				<option value="<?php print($gallery->id); ?>"<?php print($selectedGalleryId == $gallery->id ? ' selected' : ''); ?>><?php Page()->e($gallery->title,1); ?></option>
			<?php } ?>
		</select>
	</fieldset>
<?php Page()->injectEnd(); ?>
