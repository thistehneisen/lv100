<?php
	if (!ActiveUser()->can(Page()->controller, "mainīt izvēlni")) {
		Page()->accessDenied();
	}

	$language = Page()->reqParams[0];
	if (!in_array($language, Page()->languages)) {
		if (Page()->method == "post") {
			Page()->setType("text/json");
			print(json_encode(array("status" => "err", "message" => "Valoda nav definēta.")));
		} else {
			print('<div class="alert alert-danger">Valoda nav definēta.</div>');
		}
		exit;
	}

	//Page()->debug(Page()->method);

	if (Page()->method == "POST") {
		Page()->setType("text/json");

		$data = (object)array();
		$data->title = $_POST["title"];
		$data->address = $_POST["address"];
		$address = new Address($data->address);
		$data->isNode = $address->isNode();

		if ($data->isNode) {
			$data->childs = Page()->getNode(array(
				"filter" => array(
					"parent" => $data->isNode,
					"created_by" => array("core","manual")
				),
				"returnFields" => "id,title,fullAddress,sort",
				"order" => array("sort" => "ASC", "title"=>"ASC")
			));
		}

		//Page()->debug($data);

		ob_start();
		?>
		<li class="root-element">
			<textarea title="" class="hidden" name="menu_item_data[<?php print($language); ?>][]"><?php Page()->e(json_encode($data), 1); ?></textarea>
			<a href="#" class="remove"><span class="glyphicon glyphicon-remove"></span></a>
			<div class="list-group-item list-group-item-info" title="<?php Page()->e($data->title,1); ?>"><?php print($data->title); ?></div>
			<ul class="list-group">
				<?php if ($data->isNode && $data->childs) {
					foreach ($data->childs as $child) { ?>
						<li class="list-group-item" title="<?php Page()->e($child->title, 1); ?>" data-id="<?php print($child->id); ?>"><?php print($child->title); ?></li>
					<?php }
				} ?>
			</ul>
		</li>
		<?php
		$element = ob_get_clean();
		print(json_encode(array("status" => "ok", "element" => $element)));
		exit;
	}

	function print_struct($parent_id, $deep = 1, $sid = 0) {
		$nodes = Page()->getNode(array(
			"filter" => array(
				"parent"     => $parent_id,
				"created_by" => array("core", "manual"),
				"type"       => array(1,2)
			),
			"order"  => array(
				"title" => "asc"
			)));
		if (is_array($nodes)) {
			foreach ($nodes as $node) {
				if ($node->id != $sid) echo '<li data-value="' . htmlspecialchars($node->address) . '" data-deep="' . $deep . '">' . $node->title . '</li>';
				print_struct($node->id, $deep + 1, $sid);
			}
		}
	}

	$root = Page()->roots[ array_search(Page()->reqParams[0], array_map(function ($n) { return $n->language; }, Page()->roots)) ];

?>
<div class="form-group">
	<label class="control-label" for="menu-item-title">Nosaukums:</label>
	<input type="text" id="menu-item-title" class="form-control">
</div>
<div class="form-group">
	<label class="control-label" for="menu-item-address">Adrese:</label>
	<div class="combobox">
		<input type="text" id="menu-item-address"/>
		<ul>
			<?php print_struct($root->id, 1, 0) ?>
		</ul>
	</div>
</div>

<script type="text/javascript">
	$(document).on("change", '#menu-item-address', function() {
		$(this).val($(this).val().replace((new RegExp('^' + Settings.Host)), ""));
	});
	$("#new-item").dialog("addbutton", {
		text   : "Pievienot",
		"class": "btn-primary",
		click  : function() {
			$.post("<?php print(Page()->getURL()); ?>", {
				title  : $("#menu-item-title").val(),
				address: $("#menu-item-address").val()
			}, function(response) {
				if (response.status == "ok") {
					$(".menu-list").filter(function(){
						return $(this).data("language") == "<?php print($language); ?>";
					}).find(".new").before(response.element);
					enableMainSubMenuSort($(".menu-list").filter(function(){
						return $(this).data("language") == "<?php print($language); ?>";
					}).find(".root-element:last ul"));
					changed = true;
					$("#save").prop("disabled",false);
					$("#new-item").dialog("close");
				}
				else if (response.status == "err") {
					alert(response.message);
				}
			}, "json");
		}
	});
</script>