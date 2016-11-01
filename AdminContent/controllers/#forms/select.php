<?php
	$forms = DataBase()->getRows("SELECT * FROM %s WHERE `deleted`=0 ORDER BY `title` ASC", DataBase()->forms);
?><select id="selected-form" style="width: 100%;" class="form-control">
	<option></option>
	<?php foreach ($forms as $form) { ?>
	<option value="<?php echo $form["id"]; ?>"><?php echo $form["title"]; ?></option>
	<?php } ?>
</select>