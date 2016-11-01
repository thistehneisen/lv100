<?php
$nodeId = is_numeric(Page()->reqParams[0]) ? Page()->reqParams[0] : $_GET["sid"];
$formAssocs = Settings()->get("node_forms_assoc", "");
$selectedFormId = $formAssocs[$nodeId];
if ($selectedFormId) {
	$selectedForm = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->forms, $selectedFormId);
}

Page()->injectStart(".rightbar #settings .content");
?>
	<div>
		<p class="span"><label>Forma:</label><span style="padding: 6px 0;">
			<?php if ($selectedForm) { ?>
			<strong><?php echo $selectedForm["title"]?></strong>  [<a href="#" style="color: red;" id="remove-form">Noņemt</a>]
			<?php } else { ?>
			<a href="#" class="link" id="select-form"><em>Pievienot</em></a>
			<?php } ?>
		</span></p>
		<input type="hidden" name="custom_form_id" value="<?php echo $selectedFormId; ?>"/>
	</div>
<?php Page()->injectEnd(); ?>
<?php Page()->injectStart("body"); ?>
<script type="text/javascript">
$(function(){
	$(document).on("click","#select-form",function(e){
		e.preventDefault();
		$($.parseHTML('<div><\/div>')).attr({title:<?php echo json_encode("Izvēlies formu"); ?>}).load(<?php echo json_encode(Page()->aHost."forms/select/"); ?>).
			dialog({
				modal: true,
				resizable: false,
				draggable: false,
				close: function(){
					$(this).dialog("destroy").remove();
				},
				buttons: [
					{
						text: "Izvēlēties",
						"class": "btn btn-success",
						click: function(){
							$("[name=\"custom_form_id\"").val($("#selected-form").val());
							var form = $($.parseHTML('<strong>'+$("#selected-form option:selected").text()+'<\/strong>'));
							var remove = $($.parseHTML(' [<a href="#" style="color: red;" id="remove-form">Noņemt<\/a>]'));
							if ($("#selected-form").val()) $("#select-form").replaceWith(form); form.after(remove);
							$(this).dialog("close");
						}
					}
				]
			});
	}).on("click","#remove-form",function(e){
		e.preventDefault();
		var add = $($.parseHTML('<a href="#" class="link" id="select-form"><em>Pievienot<\/em><\/a>'));
		$(this).parent().empty().append(add);
		$("[name=\"custom_form_id\"").val('');
	});
});
</script>
<?php Page()->injectEnd(); ?>