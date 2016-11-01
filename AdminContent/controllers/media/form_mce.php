<?php

	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}

	if ($_GET["CheckImage"]) {
		Page()->setType("application/json");
		$oImg = new Image($_GET["CheckImage"]);
		if ($oImg) {
			$imgData = array(
				"type" => "photo",
				"filename" => basename($_GET["CheckImage"]),
				"filepath" => $_GET["CheckImage"],
				"width" => $oImg->width,
				"height" => $oImg->height
			);
		}
		else $imgData = false;
		echo json_encode(array("isEditable" => (!!$oImg->image), "data" => $imgData));
		exit;
	}
?>
<div class="container-fluid">
	<div class="row">
		<div class="col-xs-4" id="previewImageContainer">
			<div class="wrap">
				<span class="helper"></span><img src="<?php print(Page()->host . Page()->getEmptyImage(320, 180)); ?>" id="previewImage">
			</div>
			<div class="margin-top">
				<button type="button" id="editPicture" class="btn btn-default pull-right disabled">Pielāgot attēlu</button>
			</div>
		</div>
		<div class="col-xs-8 form-horizontal">
			<div class="row">
				<div class="col-xs-3">
					<label class="control-label">No datora:</label>
				</div>
				<div class="col-xs-9">
					<button type="button" class="upload btn btn-default btn-upload block" id="imageFromDisk">
						Augšupielādēt attēlu
						<div class="progress">
							<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">
								<span></span>
							</div>
						</div>
					</button>
				</div>
			</div>
			<div class="row margin-top">
				<div class="col-xs-3">
					<label class="control-label">No datubāzes:</label>
				</div>
				<div class="col-xs-9">
					<button type="button" class="upload btn btn-default btn-upload block" id="imageFromDB">
						Izvēlēties attēlu
					</button>
				</div>
			</div>
			<div class="row margin-top">
				<div class="col-xs-3">
					<label class="control-label" for="imageFromUrl">No interneta:</label>
				</div>
				<div class="col-xs-7">
					<input type="text" class="form-control" id="imageFromUrl" placeholder="http://">
				</div>
				<div class="col-xs-2">
					<button type="button" class="btn btn-default" style="width: 100%; padding-left: 0; padding-right: 0; text-align: center;" id="imageFromUrlButton">Paņemt</button>
				</div>
			</div>
			<div class="row hidden">
				<div class="col-xs-12">
					<hr>
				</div>
			</div>
			<div class="row margin-top">
				<div class="col-xs-3">
					<label class="control-label" for="imageWidth">Platums (%):</label>
				</div>
				<div class="col-xs-9">
					<input type="number" class="form-control" id="imageWidth" value="100" min="0" max="100" style="width: 70px;">
				</div>
			</div>
			<div class="row margin-top hidden">
				<div class="col-xs-4">
					<label class="control-label">Teksta novietojums:</label>
				</div>
				<div class="col-xs-8">
					<div class="btn-group btn-group-justified" role="group" id="imagePosition" aria-label="...">
						<div class="btn-group">
							<button type="button" id="imagePositionLeft" class="btn btn-default disabled">Pa labi</button>
						</div>
						<div class="btn-group">
							<button type="button" id="imagePositionCenter" class="btn btn-default disabled active">Zem</button>
						</div>
						<div class="btn-group">
							<button type="button" id="imagePositionRight" class="btn btn-default disabled">Pa kreisi</button>
						</div>
					</div>
				</div>
			</div>
			<div class="row hidden">
				<div class="col-xs-12">
					<hr>
				</div>
			</div>
			<div class="row margin-top">
				<div class="col-xs-3">
					<label class="control-label" for="imageAltText">Alt. teksts:</label>
				</div>
				<div class="col-xs-9">
					<input type="text" class="form-control" id="imageAltText">
				</div>
			</div>
			<div class="row margin-top">
				<div class="col-xs-3">
					<label class="control-label" for="imageCaptionText">Apraksts:</label>
				</div>
				<div class="col-xs-9">
					<input type="text" class="form-control" id="imageCaptionText">
				</div>
			</div>
			<div class="row margin-top">
				<div class="col-xs-12">
					<div class="alert alert-info">
						<p>Lai bilde neizskatītos miglaina, tās platumam jābūt izmērā virs <?php print(Page()->defaultImageBox[0]); ?>px.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var editor = tinymce.activeEditor,
		imageDialog = $(imageUploadDialog),
		selectedImg = false;
	imageDialog.dialog("option", "title", <?php Page()->e(Page()->t("{{Insert/edit picture}}"),3); ?>);
	//console.log(imageDialog);

	imageDialog.find(".btn-group .btn").on("click", function (e) {
		$(this).addClass("active").parent().siblings().find(".btn").removeClass("active");
	});

	function oGetImageSettings() {
		var oSettings = {};
		switch ($(".btn.active", imagePosition).attr("id")) {
			case "imagePositionLeft":
				oSettings.sPosition = "left";
				break;
			case "imagePositionCenter":
				oSettings.sPosition = "center";
				break;
			case "imagePositionRight":
				oSettings.sPosition = "right";
				break;
			default:
				oSettings.sPosition = "left";
				break;
		}
/*
		switch ($(".btn.active", imageSize).attr("id")) {
			case "imageSizeLarge":
				oSettings.sSize = "lg";
				break;
			case "imageSizeMedium":
				oSettings.sSize = "md";
				break;
			case "imageSizeSmall":
				oSettings.sSize = "sm";
				break;
			default:
				oSettings.sSize = "lg";
				break;
		}
*/
		oSettings.sAltText = $(imageAltText).val();
		oSettings.sCaption = $(imageCaptionText).val();
		oSettings.sUrl = $(imageFromUrl).val();
		oSettings.nWidth = Math.round((parseFloat($(imageWidth).val()) + 0.00001) * 100) / 100;
		return oSettings;
	}

/*
	$(".btn", imageSize).on("click", function (e) {
		if ($(this).is("#imageSizeLarge")) {
			$(".btn", imagePosition).addClass("disabled").removeClass("active");
			$(imagePositionCenter).addClass("active");
		} else {
			$(".btn", imagePosition).removeClass("disabled");
		}
	});

*/
	$(imageFromUrlButton).on("click", function (e) {
		e.preventDefault();
		$(previewImage).attr({src: $(imageFromUrl).val()});
		$.get("<?php print(Page()->getURL()); ?>", {CheckImage: $(imageFromUrl).val()}, function (response) {
			if (typeof editPicture == "undefined") return;
			if (response.isEditable) $(editPicture).removeClass("disabled");
			else $(editPicture).addClass("disabled");
			$(editPicture).data("opts",response.data)
		});
		$(imagePopupInsertButton).prop("disabled",false);
	});

	if (editor.selection.getNode().nodeName == "IMG" && !$(editor.selection.getNode()).is(".mce-object")) {
		selectedImg = editor.selection.getNode();
		$(imagePopupInsertButton).data("update-text","Atjaunot").button("update").prop("disabled",false);
		$(imageFromUrl).val(selectedImg.src);
		$(imageAltText).val(selectedImg.alt);
		$(imageWidth).val(Math.round((($(selectedImg).width()/$(selectedImg).parent().width()*100) + 0.00001) * 100) / 100);
		$(imageCaptionText).val(selectedImg.getAttribute("data-caption"));
		$(imageFromUrlButton).click();
	}

	$(editPicture).on("click", function () {
	});

	$(imagePopupInsertButton).on("click",function(){
		var oSettings = oGetImageSettings();
		var oImg = $($.parseHTML('<img src="'+oSettings.sUrl+'" alt="'+oSettings.sAltText+'">'));
		oImg.attr("data-caption",oSettings.sCaption);
		//oImg.addClass("img-"+oSettings.sPosition);
		oImg.css({width:oSettings.nWidth+"%"});
		if (selectedImg && $(selectedImg).css("float")) {
			oImg.css("float",$(selectedImg).css("float")).addClass($(selectedImg).attr("class"));
		}
		editor.selection.setContent(oImg[0].outerHTML);


		rng = editor.dom.createRng();
		rng.setStartBefore($(editor.selection.getNode()).find("img")[0]);
		rng.setEndAfter($(editor.selection.getNode()).find("img")[0]);
		editor.selection.setRng(rng);

		imageDialog.dialog("close");
	});

	ImageUploaderSingle("#imageFromDisk", function (response) {
		$(imageFromUrl).val(Settings.Host + response.file);
		$(imageFromUrlButton).click();
	}, "mce", []);


	$(imageFromDB).on("click", function (e) {
		e.preventDefault();
		selectFile(function(file){
			$(imageFromUrl).val(Settings.Host + file);
			$(imageFromUrlButton).click();
		},"photo");
	});
	$(editPicture).on("click",function(e){
		e.preventDefault();
		var article = $(this),
			imgOptions = article.data().opts;
		var imgEditor = new imgEditTool(imgOptions).open(function(){
			this.cropToolInit({cancel:function(){
				this.close();
			},save: function(data){
				that = this;
				$.getJSON(<?php Page()->e(Page()->host."media.upload/?raw_crop=1",3)?>+'&session='+Settings.session_id,{i:data.i,x:data.x,y:data.y,w:data.w,h:data.h,r:data.r},function(response) {
					that.close();
					$(imageFromUrl).val(Settings.Host + response.fileThumb);
					$(imageFromUrlButton).click();
				});
			}});
		});
	});

</script>

<style type="text/css">
	#previewImageContainer .wrap {
		border: 1px solid #ccc;
		white-space: nowrap;
		text-align: center;
		height: 231px;
	}

	#previewImageContainer .helper {
		display: inline-block;
		height: 100%;
		vertical-align: middle;
	}

	#previewImage {
		vertical-align: middle;
		max-height: 229px;
		max-width: 221px;
	}

	#editPicture {
		outline: none;
	}
</style>