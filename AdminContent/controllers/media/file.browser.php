<?php
	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}

	if ($_GET["type"] == "photo") {
		$where = " AND `type`='photo'";
	}

	DataBase()->countResults = true;
	$files = DataBase()->getRows("SELECT * FROM %s WHERE `original`=0{$where} ORDER BY `created` DESC LIMIT %d,%d", DataBase()->media, Page()->pageCurrent*60, 60);
	$results = DataBase()->resultsFound;

?>
<style type="text/css">
	#fileBrowserDialog .media-left {
		display: table-cell;
		vertical-align: middle;
		width: 64px;
		height: 64px;
	}

	#fileBrowserDialog .media-left img {
		max-width: 100%;
		max-height: 100%;
		margin: auto;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
	}

	#fileBrowserDialog .media-left div {
		width: 64px;
		text-align: center;
		height: 64px;
		position: relative;
	}

	#fileBrowserDialog .media-left div.svg {
		height: 56px;
	}

	#fileBrowserDialog .media-left svg {
		max-width: 100%;
		max-height: 100%;
		margin: auto;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
	}

	#fileBrowserDialog .media-left .svg span {
		position: absolute;
		top: 15px;
		color: #fff;
		text-transform: uppercase;
		display: inline-block;
		width: 26px;
		text-align: center;
		font-size: 10px;
		left: 10px;
		z-index: 2;
	}

	#fileBrowserDialog .media-body {
		vertical-align: middle;
	}
</style>
<div class="media-container">
	<div class="row">
		<?php foreach ($files as $file) {
			?>
			<div class="col-xs-6">
				<a href="#" class="thumbnail" data-file="<?php Page()->e($file["filepath"], 1); ?>" data-filedata="<?php Page()->e(json_encode($file), 1); ?>">
					<div class="media">
						<div class="media-left">
							<?php if ($file["type"] == "photo") { ?>
								<div class="media-object">
									<img class="" src="<?php print(Page()->host . FS()->getThumb($file["filepath"], 64)); ?>">
								</div>
							<?php } else { ?>
								<div class="media-object svg">
									<span><?php print($file["ext"]); ?></span>
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.5 75">
										<path d="M14 44.5v28h49v-55l-15-15h-34v14" stroke="#000" stroke-miterlimit="10" stroke-width="5" fill="none"/>
										<path d="M0 20.5h40v20h-40z"/>
									</svg>
								</div>
							<?php } ?>
						</div>
						<div class="media-body">
							<h4 class="media-heading"><?php print($file["filename"]); ?></h4>
						</div>

					</div>
				</a>

			</div>
			<?php
		}
		?>
	</div>
	<?php if (ceil($results / 60) > 1) { ?>
			<nav>
				<ul class="pagination">
					<?php Page()->paging(array(
						"pages"            => ceil($results / 60),
						"delta"            => 5,
						"echo"             => true,
						"page"             => '<li><a href="%1$s">%2$s</a></li>',
						"active"           => '<li class="active"><a href="%1$s">%2$d</a></li>',
						"prev"             => '<li class="%3$s"><a href="%1$s" aria-label="Iepriekšējā"><span aria-hidden="true">&laquo;</span></a></li>',
						"next"             => '<li class="%3$s"><a href="%1$s" aria-label="Nākamā"><span aria-hidden="true">&raquo;</span></a></li>',
						"dontShowInactive" => false
					)) ?>
				</ul>
			</nav>
	<?php } ?>
</div>
<script type="text/javascript">
		$(fileBrowserDialog).off("click").on("click", ".thumbnail", function() {
			$(this).toggleClass("active").blur();
			$(this).parent().siblings().find(".thumbnail").removeClass("active");
			$(fileBrowserSelectButton).prop("disabled", !$(this).is(".active"));
		}).on('click','nav .pagination a',function(e){
			e.preventDefault();
			if (!$(this).parent().is(".disabled")) {
				$(fileBrowserDialog).load(this.href);
				$(fileBrowserSelectButton).prop("disabled", true);
			}
		});
</script>