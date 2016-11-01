<?php
	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}
	$galleries = Page()->getNode(array(
			"filter" => array(
					"controller" => "galleries",
					"view" => "gallery",
					"enabled" => 1,
					"language" => Page()->reqParams[0]
			),
			"order" => array("time_added"=>"DESC")
	));


	if (false) {
		die("404 - Not Found");
	} else {
		?>
		<select style="width: 100%;" class="form-control" id="custom_gallery_mce">
			<?php foreach ($galleries as $gallery) { ?>
				<option value="<?php print($gallery->id); ?>"><?php Page()->e($gallery->title,1); ?></option>
			<?php } ?>
		</select>
		<script type="text/javascript">
			var editor = tinymce.activeEditor,
					galleryDialog = $(galleryAddDialog);
			$(galleryPopupInsertButton).prop("disabled",false);
			$('#custom_gallery_mce').selectize({
				create: false,
				createOnBlur: false,
				allowEmptyOption: false,
				sortField: 'text',
				labelField: 'text',
				onChange: function(val){
					if (val) {
						$(galleryPopupInsertButton).button("enable");
					}
					else {
						$(galleryPopupInsertButton).button("disable");
					}
				},
				searchField: 'text'/*,
				render: {
					option_create: function (data, escape) {
						return '<div class="create">Pievienot <strong>' + escape(data.input) + '<\/strong>&hellip;<\/div>';
					}
				},
				load: function (query, callback) {
					$.ajax({
						url: "",
						type: 'GET',
						dataType: 'json',
						data: {
							cat: query
						},
						error: function () {
							callback();
						},
						success: function (data) {
							callback(data);
						}
					});
				}*/
			});
			$(galleryPopupInsertButton).on("click",function(){
				var oImg = $($.parseHTML('<p><span class="gallery mceNonEditable" data-id="'+$(custom_gallery_mce).val()+'">'+$(custom_gallery_mce).find('option:selected').text()+'<\/span></p>'));
				editor.insertContent(oImg[0].outerHTML);
				galleryDialog.dialog("close");
			});

		</script>

		<?php
	}
?>