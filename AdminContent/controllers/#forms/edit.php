<?php
	$form = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->forms, Page()->reqParams[0]);
	if ($form) {
		$fields = DataBase()->getJSON(0, "fields");
	} else {
		$fields = array();
	}

	if (Page()->reqParams[1] == "delete") {
		DataBase()->update("forms", array("deleted" => 1), array("id" => $form["id"]));

		Page()->addCmsInfotip("Forma dzēsta.", "green", "", "yes");
		header("Location: {$_GET["referer"]}");
		exit;
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$sql = array();
		$sql["title"] = $_POST["title"];
		$sql["incrementer"] = $_POST["incrementer"];
		$fields = array();
		foreach ($_POST["field"] as $fieldJSON) {
			$fields[] = json_decode($fieldJSON, true);
		}
		$sql["fields"] = json_encode($fields);
		if (!$form) $sql["added"] = date("Y-m-d H:i:s");
		$sql["enabled"] = $_POST["enabled"];
		$sql["submitlabel"] = $_POST["submitlabel"];
		$sql["deleted"] = 0;
		$sql["prepend"] = $_POST["form_prepend"];
		$sql["append"] = $_POST["form_append"];
		$sql["thankyou"] = $_POST["form_thankyou"];
		$method = ($form ? "update" : "insert");


		$fieldRows = DataBase()->getRows("SHOW COLUMNS FROM %s WHERE `Field` LIKE 'f\_%%'", DataBase()->forms_data);
		$existingFields = array();
		$newFieldsSQL = array();
		foreach ($fieldRows as $fieldRow) $existingFields[] = $fieldRow["Field"];

		foreach ($fields as $field) {
			$sf = "f_" . (int)$field["id"];
			if (!in_array($sf, $existingFields)) $newFieldsSQL[] = "ADD  `" . $sf . "` TEXT NOT NULL DEFAULT  ''";
		}
		DataBase()->queryf("ALTER TABLE %s " . join(", ", $newFieldsSQL), DataBase()->forms_data);

		DataBase()->$method("forms", $sql, ($form ? array("id" => $form["id"]) : true));

		Page()->addCmsInfotip("Forma saglabāta", "green", "", "yes");
		header("Location: {$_POST["referer"]}");
		exit;
	}

	Page()->addBreadcrumb("Formas", Page()->aHost . Page()->controller . "/");
	if ($form) {
		Page()->addBreadcrumb($form["title"], Page()->aHost . Page()->controller . "/edit/" . $form["id"] . "/");
	} else {
		Page()->addBreadcrumb("Jauna forma", Page()->aHost . Page()->controller . "/edit/");
	}

	Page()->header();
?>
	<style type="text/css">
		form.addbody .shadedli {
			background-color: #f5f5f5;
			border: 1px solid #e0e0e0;
			padding: 0px 10px 0px 50px;
			margin-bottom: 5px;
			line-height: 30px;
			cursor: move;
		}

		form.addbody .shadedli a {
			cursor: pointer !important;
			margin-top: 1px;
			margin-right: -2px;
		}
		form.addbody .shadedli strong {
			max-width: 331px;
			overflow: hidden;
			display: inline-block;
			white-space: nowrap;
			vertical-align: bottom;
			text-overflow: ellipsis;
		}
		form a.delete:hover {
			color: red;
		}
	</style>
	<form class="addbody new" action="<?php echo Page()->fullRequestUri ?>" method="post">
		<input type="hidden" name="referer" value="<?php echo $_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"] ?>" />
		<input type="hidden" name="incrementer" value="<?php echo (int)$form["incrementer"] ?>" />
		<header>
			<a href="<?php echo $_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"] ?>" class="btn btn-lg btn-back btn-primary pull-left">Atpakaļ</a>
			<h1 class="icon page"><?php echo($form ? $form["title"] : "Jauna forma"); ?></h1>
		</header>

		<div class="col-content">
			<input type="text" class="form-control input-lg form-group" name="title" value="<?php Page()->e($form["title"], 1); ?>" placeholder="<?php Page()->e("Formas nosaukums", 1); ?>" /><br />
			<section>
				<h1>Formas lauki</h1>
				<ol class="sortable" id="fields">
					<?php
						$tmpFields = array();
						foreach (Page()->formFieldTypes as $field) {
							$tmpFields[ $field["type"] ][ $field["subtype"] ] = $field;
						}
					?>
					<?php foreach ((array)$fields as $field) { ?>
						<li class="shadedli field"<?php echo($field["deleted"] ? ' style="display: none;"' : ""); ?>>
							<strong><?php echo $field["title"]; ?></strong>
							<em>(<?php echo $tmpFields[ $field["type"] ][ $field["subtype"] ]["label"] ?>)</em>
							<a href="#" style="float: right;" class="btn btn-default btn-sm small edit-field">Labot</a>
							<textarea style="display: none;" name="field[]"><?php Page()->e($field, 3); ?></textarea>
						</li>
					<?php } ?>
				</ol>
				<a href="#" class="addbutton" id="add-field">Pievieno lauku</a>
			</section>
			<section>
				<h1>Teksta bloki</h1>
				<section class="infotip yellow">
					<p>Šeit var norādīt informatīvus teksta blokus, kas tiek rādīti līdzās lapā ievietotajai formai pirms tās, aiz tās vai tās vietā, ja lietotājs ir aizpildījis formu un nospiedis pogu "Iesniegt".</p>
				</section>
				<br />
				<fieldset>
					<label for="form_prepend">Teksts pirms formas:</label><textarea id="form_prepend" class="tinymce_big" name="form_prepend"><?php Page()->e($form["prepend"], 1); ?></textarea>
				</fieldset>
				<fieldset>
					<label for="form_append">Teksts pēc formas:</label><textarea id="form_append" class="tinymce_big" name="form_append"><?php Page()->e($form["append"], 1); ?></textarea>
				</fieldset>
				<fieldset>
					<label for="form_thankyou">Teksts, kas parādās, kad forma nosūtīta:</label><textarea id="form_thankyou" class="tinymce_big" name="form_thankyou"><?php Page()->e($form["thankyou"], 1); ?></textarea>
				</fieldset>
			</section>
		</div>
		<div class="col-sidebar">
			<aside class="rightbar">
				<section id="settings">
					<h1>Uzstādījumi</h1>
					<div class="content">
						<div class="form-group form-horizontal">
							<label for="enabled" class="control-label">Publicēta:</label>
							<span class="pull-right"><input type="checkbox" id="enabled" name="enabled" value="1" class="selector" <?php echo($form["enabled"] ? 'checked ' : ''); ?>/></span>
						</div>
						<div class="form-group">
							<label for="submitlabel">Pogas teksts:</label>
							<input type="text" class="form-control" id="submitlabel" name="submitlabel" value="<?php Page()->e($form["submitlabel"], 1); ?>">
						</div>
					</div>
					<p class="form-actions">
						<a href="<?php echo $_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"] ?>" class="btn btn-default" role="button">Atcelt</a>
						<button type="submit" class="btn btn-success pull-right">Saglabāt</button>
						<?php if ($form) { ?>
						<a href="<?= Page()->aHost . Page()->controller . '/edit/' . $form["id"] . '/delete/?referer=' ?><?php Page()->e($_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"], 2); ?>" data-confirm="Vai tiešām vēlies dzēst šo formu?" class="btn-danger btn">Dzēst formu</a><?php } ?>
					</p>
				</section>
			</aside>
		</div>
	</form>
	<script type="text/javascript">
		//<![CDATA[
		$(function () {
		var fieldTypes = <?php echo json_encode(Page()->formFieldTypes); ?>;
		function openFieldSettings(add) {
			var form = $($.parseHTML("<form\/>")).attr({id: "add-field-form"}).append('<input type="hidden" name="incrementer" value="' + currentFieldData.incrementer + '"\/>'),
				type = $($.parseHTML('<select class="form-control"\/>')).on("change", function () {
					var ft = fieldTypes[$(this).val()];
					if (ft.subtype == "checkbox" || ft.subtype == "radio" || ft.subtype == "custom1" || ft.type == "select") values.show();
					else values.hide();
					if (ft.subtype == "checkbox") {
						checkboxfield = true;
						radiofield = false;
						values.find('[name="checked"]').show().next().css({width: 540});
						values.find('[type="radio"]').attr({type: "checkbox"});
						values.find('.radiocheckonly').show();
					}
					else if (ft.subtype == "radio") {
						checkboxfield = false;
						radiofield = true;
						values.find('[name="checked"]').show().next().css({width: 540});
						values.find('[type="checkbox"]').attr({type: "radio"});
						values.find('.radiocheckonly').show();
					}
					else {
						checkboxfield = false;
						radiofield = false;
						values.find('[name="checked"]').hide().next().css({width: 563});
						values.find('.radiocheckonly').hide();
					}
				}).css({width: "100%"});
			var radiofield = false, checkboxfield = false;
			var addValue = $($.parseHTML('<a href="#" class="addbutton">Pievienot vērtību<\/a>'
		)).
			css({"float": "none"}).on("click", function (e) {
				e.preventDefault();
				var id = parseInt(form[0].incrementer.value) + 1;
				var newValue = $($.parseHTML('<li class="clear" style="margin-top: 2px;"><input style="width: 23px; height: 27px; float: left; position: relative; top: -2px;' + (!radiofield && !checkboxfield ? ' display: none;' : '') + '" type="' + (radiofield ? 'radio' : 'checkbox') + '" name="checked" id="checked-' + id + '" value="1"\/><input type="text" class="form-control" name="value" value="" style="float: left; width: ' + (radiofield || checkboxfield ? '540' : '563') + 'px; margin-right: 3px;"\/><\/li>')).data("value", {
					id: id,
					text: "",
					deleted: false
				});
				form[0].incrementer.value = id;
				newValue.append($($.parseHTML('<a href="#" style="float: left; margin-top: 3px;" class="delete"><span class="glyphicon glyphicon-remove" style="font-size: 23px;"><\/span><\/a>')).on("click", function (e) {
					e.preventDefault();
					$(this).parent().hide().data().value.deleted = true;
					$(dialog).dialog('option', 'position', 'center');
				}));
				newValue.prepend('<span class="glyphicon glyphicon-sort" style="cursor: move; float: left; font-size: 22px;line-height: 28px;margin: 0 6px 0 3px;color: #666;"></span>');
				values.find("ol").append(newValue);
				values[0].scrollIntoView(false);
				$(dialog).dialog('option', 'position', 'center');
				$(".sortable2").sortable({
					tolerance: 'pointer',
					revert: 100,
					axis: 'y',
					handle: '.glyphicon-sort',
					containment: "parent"
				});
			});
			var showDirection = $($.parseHTML("<div\/>")).addClass("col-xs-6 radiocheckonly form-horizontal").css({float: "right"}).append(<?php echo json_encode('<label class="control-label">Vienā rindā:</label>'); ?>).append('<div class="pull-right"><input type="checkbox" name="inline" value="1" class="selector"' + (currentFieldData.inline ? ' checked ' : '') + '\/><\/div>');
			var additionalTextField = $($.parseHTML("<div\/>")).addClass("col-xs-6 form-horizontal").append(<?php echo json_encode('<label class="control-label">Pievienot pielāgotu lauku:</label>'); ?>).append('<div class="pull-right"><input type="checkbox" name="addtext" value="1" class="selector"' + (currentFieldData.addtext ? ' checked ' : '') + '\/><\/div>').append('<input type="text" class="form-control" name="addtextlabel" style="display: ' + (currentFieldData.addtext ? 'block' : 'none') + ';" value="' + currentFieldData.addtextlabel + '">');
			var values = $($.parseHTML('<div\/>')).append('<hr\/><ol class="sortable2"><\/ol>').append($($.parseHTML('<section class="row"\/>')).append($($.parseHTML('<div class="col-xs-12"\/>')).append(addValue)).append(showDirection)).hide();
			values.on('selector.change', '[name="addtext"]', function () {
				if ($(this).is(":checked")) $('[name="addtextlabel"]').show();
				else $('[name="addtextlabel"]').hide();
			});
			if (currentFieldData.values && typeof currentFieldData.values == "object") {
				for (i in currentFieldData.values) {
					var eValue = currentFieldData.values[i];
					var id = eValue.id;
					if (currentFieldData.subtype == "radio") radiofield = true;
					if (currentFieldData.subtype == "checkbox") checkboxfield = true;
					var newValue = $($.parseHTML('<li class="clear" style="margin-top: 2px;"><input style="width: 23px; height: 27px; float: left; position: relative; top: -2px;" type="' + (radiofield ? 'radio' : 'checkbox') + '" name="checked" id="checked-' + id + '" value="1"' + (eValue.checked ? 'checked' : '') + '\/><input type="text" class="pull-left form-control" name="value" value="' + eValue.text + '" style="width: ' + (radiofield || checkboxfield ? '540' : '563') + 'px; margin-right: 3px;"\/><\/li>')).data("value", {
						id: id,
						text: eValue.text,
						deleted: eValue.deleted
					});
					newValue.append($($.parseHTML('<a href="#" style="float: left; margin-top: 3px;" class="delete"><span class="glyphicon glyphicon-remove" style="font-size: 23px;"><\/span><\/a>')).on("click", function (e) {
						e.preventDefault();
						$(this).parent().hide().data().value.deleted = true;
						$(dialog).dialog('option', 'position', 'center');
					}));
					newValue.prepend('<span class="glyphicon glyphicon-sort" style="cursor: move; float: left; font-size: 22px;line-height: 28px;margin: 0 6px 0 3px;color: #666;"></span>');
					if (eValue.deleted) newValue.hide();
					values.find("ol").append(newValue);
				}
			}
			for (i in fieldTypes) {
				var option = $($.parseHTML("<option\/>")).attr({value: i}).text(fieldTypes[i].label);
				type.append(option);
				if (fieldTypes[i].type == currentFieldData.type && fieldTypes[i].subtype == currentFieldData.subtype) {
					option.attr('selected', 'selected');
					type.change();
				}
			}
			if (typeof currentFieldData.placeholder == "undefined") currentFieldData.placeholder = "";
			form.append($($.parseHTML('<div class="clear row form-group"\/>'))
				.append($($.parseHTML("<div\/>")).addClass("col-xs-6").append(<?php echo json_encode('<label>Nosaukums:</label>'); ?>).append('<input type="text" class="form-control" name="title" value="' + currentFieldData.title + '"/>'))
				.append($($.parseHTML("<div\/>")).addClass("col-xs-6").append(<?php echo json_encode('<label>Tips:</label>'); ?>).append(type)))
				.append($($.parseHTML('<div class="clear row form-group"\/>'))
					.append($($.parseHTML("<div\/>")).addClass("col-xs-12").append(<?php echo json_encode('<label>Vietturis:</label>'); ?>).append('<input type="text" class="form-control" name="placeholder" value="' + currentFieldData.placeholder + '"/>')))
				.append($($.parseHTML('<div class="clear row"\/>'))
					.append($($.parseHTML("<div\/>")).addClass("col-xs-6 form-horizontal").append(<?php echo json_encode('<label class="control-label">Obligāts:</label>'); ?>).append('<div class="pull-right"><input type="checkbox" name="required" value="1" class="selector"' + (currentFieldData.required ? ' checked ' : '') + '\/><\/div>'))
					.append($($.parseHTML("<div\/>")).addClass("col-xs-6 form-horizontal").append(<?php echo json_encode('<label class="control-label">Rādīt rezultātos:</label>'); ?>).append('<div class="pull-right"><input type="checkbox" name="show" value="1" class="selector"' + (currentFieldData.show ? ' checked ' : '') + '\/><\/div>')))
				.append($($.parseHTML('<div class="clear row form-group"\/>'))
					.append($($.parseHTML("<div\/>")).addClass("col-xs-6 form-horizontal").append(<?php echo json_encode('<label class="control-label">Rādīt sarakstā:</label>'); ?>).append('<div class="pull-right"><input type="checkbox" name="showlist" value="1" class="selector"' + (currentFieldData.showlist ? ' checked ' : '') + '\/><\/div>'))
					.append($($.parseHTML("<div\/>")).addClass("col-xs-6 form-horizontal").append(<?php echo json_encode('<label class="control-label">Rādīt nosaukumu:</label>'); ?>).append('<div class="pull-right"><input type="checkbox" name="showtitle" value="1" class="selector"' + (currentFieldData.showtitle || typeof currentFieldData.showtitle == "undefined" ? ' checked ' : '') + '\/><\/div>')))
				.append(values);
			var dialog = $($.parseHTML("<div\/>")).append(form).dialog({
				modal: true,
				draggable: false,
				resizable: false,
				close: function () {
					$(window).off("resize", function () {
						dialog.dialog('option', 'maxHeight', $(window).height() - 100)
					});
					$(this).dialog("destroy").remove();
				},
				open: function () {
					$(".sortable2").sortable({
						tolerance: 'pointer',
						revert: 100,
						axis: 'y',
						handle: 'i',
						containment: "parent"
					});
					$(window).on("resize", function () {
						dialog.dialog('option', 'maxHeight', $(window).height() - 100)
					});
					replaceSelectorInputs();
				},
				width: 660,
				maxHeight: $(window).height() - 100
			});
			var btns = [
				{
					"text": add ? "Pievienot" : "Mainīt",
					"click": function () {
						var fdata = form.serializeObject(), ft = fieldTypes[type.val()];
						currentFieldData.incrementer = parseInt(fdata.incrementer);
						currentFieldData.title = fdata.title;
						currentFieldData.placeholder = fdata.placeholder;
						currentFieldData.addtextlabel = fdata.addtextlabel;
						currentFieldData.type = ft.type;
						currentFieldData.subtype = ft.subtype;
						currentFieldData.show = false;
						currentFieldData.showlist = false;
						currentFieldData.showtitle = false;
						currentFieldData.required = false;
						currentFieldData.inline = false;
						currentFieldData.addtext = false;
						if (typeof fdata.show != 'undefined') currentFieldData.show = true;
						if (typeof fdata.showlist != 'undefined') currentFieldData.showlist = true;
						if (typeof fdata.showtitle != 'undefined') currentFieldData.showtitle = true;
						if (typeof fdata.required != 'undefined') currentFieldData.required = true;
						if (typeof fdata.inline != 'undefined') currentFieldData.inline = true;
						if (typeof fdata.addtext != 'undefined') currentFieldData.addtext = true;
						currentFieldData.values = [];
						if (typeof fdata.value == "object") {
							$.each(fdata.value, function (k, v) {
								var vl = values.find("li").eq(k).data("value");
								vl.text = v;
								if ($("#checked-" + vl.id).is(":checked")) vl.checked = true;
								else vl.checked = false;
								currentFieldData.values.push(vl);
							});
						} else if (fdata.value) {
							var vl = values.find("li").eq(0).data("value");
							vl.text = fdata.value;
							if ($("#checked-" + vl.id).is(":checked")) vl.checked = true;
							else vl.checked = false;
							currentFieldData.values.push(vl);
						}
						updateCurrentField();
						$(this).dialog("close");
					},
					"class": "btn btn-success"
				}
			];
			if (!add) {
				btns.unshift({
					"text": "Noņemt",
					"click": function () {
						currentFieldData.deleted = true;
						updateCurrentField();
						$(this).dialog("close");
					},
					"class": "btn btn-danger"
				});
			}
			dialog.dialog("option", "buttons", btns);
			dialog.css({overflowX: "hidden"});
		}
		function updateCurrentField() {
			var listItem;
			if (typeof currentField != 'undefined' && currentField && currentField.length) {
				listItem = currentField.parents(".field");
			}
			else {
				window.currentField = $($.parseHTML('<textarea name="field[]" style="display: none;"><\/textarea>'));
				var inc = parseInt($('form.addbody [name="incrementer"]').val()) + 1;
				$('form.addbody [name="incrementer"]').val(inc);
				currentFieldData.id = inc;
				listItem = $($.parseHTML('<li class="shadedli field"><strong><\/strong> <em><\/em> <a href="#" style="float: right;" class="btn btn-default btn-sm edit-field">Labot<\/a>'
			))
				;
				listItem.append(currentField);
				$('#fields').append(listItem);
			}
			currentField.val($.toJSON(currentFieldData));
			listItem.find("strong").text(currentFieldData.title);
			for (i in fieldTypes) {
				if (fieldTypes[i].type == currentFieldData.type && fieldTypes[i].subtype == currentFieldData.subtype) {
					listItem.find("em").text('(' + fieldTypes[i].label + ')');
					break;
				}
			}
			if (currentFieldData.deleted) {
				listItem.hide();
			}
			currentField = null;
			currentFieldData = null;
		}
			$(document).on("click", ".edit-field", function (e) {
				e.preventDefault();
				window.currentField = $(this).parents(".field").find("textarea");
				window.currentFieldData = $.parseJSON(currentField.val());
				openFieldSettings(false);
			});
			$(".sortable").sortable({
				tolerance: 'pointer',
				revert: 100,
				cancel: 'a.btn',
				axis: 'y',
				containment: "parent"
			});
			$("#add-field").on("click", function (e) {
				e.preventDefault();
				window.currentFieldData = {
					values: [],
					incrementer: 0,
					show: true,
					showtitle: true,
					type: "input",
					subtype: "text",
					title: "",
					placeholder: "",
					addtextlabel: ""
				};
				openFieldSettings(true);
			});
			/*$(document).on('click', '[type="radio"]', function () {
				var wasChange = $(this).data('waschange');
				if (!wasChange && $(this).is(':checked')) $(this).prop('checked', false);
				$(this).data('waschange', false);
			}).on('change', '[type="radio"]', function () {
				$(this).data('waschange', true);
			});*/
		});
		//]]>
	</script>
<?php Page()->footer(); ?>