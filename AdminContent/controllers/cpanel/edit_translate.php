<?php //ā
if (!Page()->permTo("manage")) die("Access Denied!");

if ($_GET["action"] = "get_translate") {
	if (in_array($_POST["lang"], Page()->languages)) {
		$t = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->translate, Page()->reqParams[0]);
		$translate = DataBase()->getVar("SELECT `translate` FROM %s WHERE `file`='%s' AND `language`='%s' AND `text`='%s'", DataBase()->translate, $t['file'], $_POST['lang'], $t['text']);
		die(json_encode(array("text"=>$translate)));
	}
}

$t = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->translate, Page()->reqParams[0]);
$t = DataBase()->getRows("SELECT * FROM %s WHERE `text`='%s' AND `file`='%s'", DataBase()->translate, $t['text'], $t['file']);

foreach ($t as $d) {
	$translates[$d["language"]]=$d;
	if ($d["force"]) $KEEP = true;
}

if ($_POST) {

	if (!$_POST["new"]) {
		foreach ($_POST['translate'] as $ID => $translate) {
			DataBase()->queryf("UPDATE %s SET `translate`='%s', `force`='%d' WHERE `id`='%d'", DataBase()->translate, $translate, $_POST["keep"], $ID);
		}
	} else {
		$tmpTrnslte = array();
		foreach ($_POST['translate'] as $Lng => $translate) {
			DataBase()->insert("translate",array(
				"file" => $_POST["file"],
				"text" => $_POST["tagname"],
				"language" => $Lng,
				"translate" => $translate,
				"force" => $_POST["keep"]
			),true);
			$tmpTrnslte[DataBase()->insertid] = $translate;
		}
		$_POST["translate"] = $tmpTrnslte;
	}
		
		$t = DataBase()->getRows("SELECT * FROM %s WHERE `id` IN (%s)",DataBase()->translate,join(",",array_keys($_POST["translate"])));
	?>
	<?php if ($_POST["new"]) { $_t = array_keys($tmpTrnslte); ?>
	<div class="controls">
		 <a  href="<?php echo Page()->adminHost?>cpanel/edit_translate/<?php echo end($_t)?>" class="actionbutton ajax">{{Edit}}</a>
	</div>
	<div class="content" id="trns-<?php echo end($_t)?>">
	<?php } ?>
	<h1><?php echo str_replace(array("{", "}"), array("", ""), $t[0]["text"]);?></h1>
	<?php foreach ($t as $y) { ?>
	<p><b>[<?php echo $y["language"]?>]</b> <?php echo htmlspecialchars($y["translate"])?></p>
	<?php } ?>
	<?php if ($_POST["new"]) { ?></div><?php } ?>
	<?php
	exit;
}


if (!$translates) {
	$KEEP = true;
	// assume we have new entry...
	?>
		<form action="<?php echo Page()->fullRequestUri?>" method="post" class="ajaxify" style="width: 450px;" id="translate_edit_form">
			<input type="hidden" name="keep" value="1"/>
			<input type="hidden" name="new" value="1"/>
			<input type="hidden" name="file" value="<?php echo $_GET["file"]; ?>" />
			<input type="text" name="tagname" value="" placeholder="{{Tagname}}" />
		<?php foreach (Page()->languages as $l) { ?>
		<fieldset>
			<label for="translate[<?php echo $l?>]">{{Translate}} (<?php echo strtoupper($l)?>):</label>
			<textarea name="translate[<?php echo $l?>]" id="translate" data-id="<?php echo $l?>"></textarea>
		</fieldset>
		<?php } ?>
	<?php
} else {
?>
	<h1><?php echo str_replace(array("{", "}"), array("", ""), $t[0]['text'])?></h1>
	<form action="<?php echo Page()->fullRequestUri?>" method="post" class="ajaxify" style="width: 450px;" id="translate_edit_form">
	<input type="hidden" name="keep" value="<?php echo($KEEP ? "1" : "0");?>"/>
	<?php foreach (Page()->languages as $l) { $d = $translates[$l]; ?>
	<fieldset>
		<label for="translate[<?php echo $d['id']?>]">{{Translate}} (<?php echo strtoupper($d['language'])?>):</label>
		<textarea name="translate[<?php echo $d['id']?>]" id="translate" data-id="<?php echo $d['id']?>"><?php echo htmlspecialchars($d['translate'])?></textarea>
	</fieldset>
	<?php } ?>
<?php } ?>
<script type="text/javascript">
		var checkbox = $($.parseHTML("<input\/>")).addClass("selector").attr({type:"checkbox"});
		<?php if ($KEEP) { ?>checkbox.attr("checked",true);<?php } ?>
		var labelText = <?php echo json_encode(Page()->t("{{Keep}}"));?>;
		var label = $($.parseHTML("<label/>")).css({"float":"left",position:"relative",top:-6,left:-5}).append(labelText).append(checkbox);
		$("#modal footer").prepend(label);
		setTimeout(function(){checkbox.prev().on("click",function(){
			if (!$(this).is(".enabled")) translate_edit_form.keep.value="1";
			else translate_edit_form.keep.value="0";
		});},100);
	$(function(){
		$("textarea.autosizejs").remove();
		$('textarea').autosize();
		$("form.ajaxify").on("submit",function(e){
		<?php if (!$translates) { ?>
			var opts = {
				success: function(resp){
					$("#records").prepend('<li>'+resp+'<\/li>');
				}	
			};
		<?php } else { ?>
			var opts = {
				target: "#trns-<?=Page()->reqParams[0]?>"
			};
		<?php } ?>
			$(this).ajaxSubmit(opts);
			return false;
		});
	});
</script>

</form>
